<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Customer Ledger" class="p-0">
                <template #extra>
                    <a-button @click="print" style="border-radius:8px;">
                        <PrinterOutlined /> Print
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="/" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Customer Ledger</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- Filter Card -->
    <div class="cl-filter-card">
        <div class="cl-filter-inner">
            <div class="cl-filter-icon"><UserOutlined /></div>
            <div class="cl-filter-group">
                <label class="cl-label">Customer</label>
                <a-select
                    v-model:value="filters.user_id"
                    class="cl-select"
                    show-search
                    :filter-option="filterOption"
                    placeholder="All Customers"
                    allow-clear
                >
                    <a-select-option v-for="c in customers" :key="c.xid" :value="c.xid">{{ c.name }}</a-select-option>
                </a-select>
            </div>
            <div class="cl-filter-group">
                <label class="cl-label">From Date</label>
                <a-date-picker v-model:value="filters.date_from" class="cl-date-picker" />
            </div>
            <div class="cl-filter-group">
                <label class="cl-label">To Date</label>
                <a-date-picker v-model:value="filters.date_to" class="cl-date-picker" />
            </div>
            <div class="cl-filter-actions">
                <a-button type="primary" class="cl-btn-generate" @click="load" :loading="loading">
                    <SearchOutlined /> Generate Report
                </a-button>
                <a-button v-if="generated" class="cl-btn-backfill" @click="backfillJEs" :loading="backfilling" title="Generate missing Journal Entries">
                    <SyncOutlined /> Backfill JEs
                </a-button>
            </div>
        </div>
    </div>

    <a-spin :spinning="loading">
        <!-- Empty state before generation -->
        <div v-if="!generated && !loading" class="cl-empty-state">
            <FileTextOutlined class="cl-empty-icon" />
            <p class="cl-empty-title">No Report Generated</p>
            <p class="cl-empty-sub">Select filters above and click <b>Generate Report</b> to view the customer ledger.</p>
        </div>

        <div v-if="generated" id="printable-area">

            <!-- Report Header -->
            <div class="cl-report-header">
                <div class="cl-report-header-left">
                    <div class="cl-report-badge"><AccountBookOutlined /> Customer Ledger</div>
                    <h2 class="cl-report-title">{{ reportData.customer ? reportData.customer.name : 'All Customers' }}</h2>
                    <p class="cl-report-period"><CalendarOutlined /> {{ formatDate(reportData.date_from) }} &nbsp;→&nbsp; {{ formatDate(reportData.date_to) }}</p>
                </div>
                <div class="cl-report-header-right">
                    <div class="cl-stat-chip" :class="closingBalance >= 0 ? 'cl-chip-dr' : 'cl-chip-cr'">
                        <span class="cl-chip-label">Closing Balance</span>
                        <span class="cl-chip-value">PKR {{ fmt(Math.abs(closingBalance)) }}</span>
                        <span class="cl-chip-tag">{{ closingBalance >= 0 ? 'Receivable' : 'Advance Paid' }}</span>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="cl-cards">
                <div class="cl-card cl-card-ob">
                    <div class="cl-card-icon"><DollarOutlined /></div>
                    <div>
                        <div class="cl-card-label">Opening Balance</div>
                        <div class="cl-card-value">PKR {{ fmt(Math.abs(openingBalance)) }}</div>
                        <div class="cl-card-sub">{{ openingBalance >= 0 ? 'Receivable b/f' : 'Advance b/f' }}</div>
                    </div>
                </div>
                <div class="cl-card cl-card-sales">
                    <div class="cl-card-icon"><ShoppingCartOutlined /></div>
                    <div>
                        <div class="cl-card-label">Total Sales</div>
                        <div class="cl-card-value">PKR {{ fmt(totalDebit) }}</div>
                        <div class="cl-card-sub">{{ reportData.rows.filter(r=>r.type==='Sale').length }} invoice(s)</div>
                    </div>
                </div>
                <div class="cl-card cl-card-payments">
                    <div class="cl-card-icon"><CheckCircleOutlined /></div>
                    <div>
                        <div class="cl-card-label">Total Received</div>
                        <div class="cl-card-value">PKR {{ fmt(totalCredit) }}</div>
                        <div class="cl-card-sub">{{ reportData.rows.filter(r=>r.type==='Payment Received').length }} payment(s)</div>
                    </div>
                </div>
                <div class="cl-card" :class="closingBalance >= 0 ? 'cl-card-due' : 'cl-card-advance'">
                    <div class="cl-card-icon"><WalletOutlined /></div>
                    <div>
                        <div class="cl-card-label">{{ closingBalance >= 0 ? 'Amount Receivable' : 'Advance Paid' }}</div>
                        <div class="cl-card-value">PKR {{ fmt(Math.abs(closingBalance)) }}</div>
                        <div class="cl-card-sub">Net closing balance</div>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="cl-table-wrapper">
                <a-table
                    :dataSource="tableRows"
                    :columns="columns"
                    :pagination="false"
                    size="middle"
                    :rowKey="(r, i) => i"
                    :scroll="{ x: 860 }"
                    :rowClassName="rowClass"
                    :expandable="expandable"
                    class="cl-table"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'date'">
                            <span class="cl-date-cell">{{ record.date }}</span>
                        </template>
                        <template v-if="column.key === 'reference'">
                            <span v-if="record._is_opening" class="cl-ob-ref">Opening Balance</span>
                            <span
                                v-else-if="record.order_xid && record.type === 'Sale'"
                                class="cl-ref-link"
                                @click="openSaleDetail(record)"
                            >
                                <FileTextOutlined style="margin-right:4px;font-size:11px;" />
                                {{ record.reference }}
                                <a-badge v-if="record.items && record.items.length" :count="record.items.length" :number-style="{ backgroundColor:'#0ea5e9', fontSize:'9px', height:'16px', lineHeight:'16px', minWidth:'16px' }" />
                            </span>
                            <span v-else class="cl-ref-plain">{{ record.reference }}</span>
                        </template>
                        <template v-if="column.key === 'type'">
                            <span v-if="record._is_opening" class="cl-type-ob"><span class="cl-type-dot cl-dot-gray"></span>b/f</span>
                            <span v-else class="cl-type-pill" :class="typeClass(record.type)">
                                <span class="cl-type-dot" :class="typeDot(record.type)"></span>
                                {{ record.type }}
                            </span>
                        </template>
                        <template v-if="column.key === 'debit'">
                            <span v-if="+record.debit !== 0" class="cl-amount-dr">{{ fmt(record.debit) }}</span>
                            <span v-else class="cl-amount-nil">—</span>
                        </template>
                        <template v-if="column.key === 'credit'">
                            <span v-if="+record.credit !== 0" class="cl-amount-cr">{{ fmt(record.credit) }}</span>
                            <span v-else class="cl-amount-nil">—</span>
                        </template>
                        <template v-if="column.key === 'running_balance'">
                            <span class="cl-balance" :class="record.running_balance >= 0 ? 'cl-balance-dr' : 'cl-balance-cr'">
                                {{ fmt(Math.abs(record.running_balance)) }}
                                <small>{{ record.running_balance >= 0 ? 'Dr' : 'Cr' }}</small>
                            </span>
                        </template>
                    </template>

                    <!-- Expandable item rows -->
                    <template #expandedRowRender="{ record }">
                        <div v-if="record.items && record.items.length" class="cl-expanded">
                            <div class="cl-expanded-header">
                                <ShoppingOutlined /> Items in <b>{{ record.reference }}</b>
                            </div>
                            <table class="cl-items-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Code</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Unit Price</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in record.items" :key="idx">
                                        <td class="cl-idx">{{ idx + 1 }}</td>
                                        <td class="cl-pname">{{ item.name }}</td>
                                        <td><code v-if="item.item_code" class="cl-code">{{ item.item_code }}</code></td>
                                        <td class="text-right cl-qty">{{ item.qty }}</td>
                                        <td class="text-right">PKR {{ fmt(item.unit_price) }}</td>
                                        <td class="text-right cl-sub">PKR {{ fmt(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="cl-foot-total">
                                        <td colspan="5" class="text-right">Total</td>
                                        <td class="text-right">PKR {{ fmt(record.debit) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-else class="cl-no-items">No item details available.</div>
                    </template>

                    <!-- Summary footer row -->
                    <template #summary>
                        <a-table-summary-row class="cl-summary-row">
                            <a-table-summary-cell :index="0" :col-span="3">
                                <span class="cl-summary-label">CLOSING TOTALS</span>
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="3" align="right">
                                <span class="cl-summary-dr">{{ fmt(openingBalance > 0 ? openingBalance + totalDebit : totalDebit) }}</span>
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="4" align="right">
                                <span class="cl-summary-cr">{{ fmt(openingBalance < 0 ? Math.abs(openingBalance) + totalCredit : totalCredit) }}</span>
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="5" align="right">
                                <span class="cl-summary-bal" :class="closingBalance >= 0 ? 'cl-summary-bal-dr' : 'cl-summary-bal-cr'">
                                    PKR {{ fmt(Math.abs(closingBalance)) }} {{ closingBalance >= 0 ? 'Dr' : 'Cr' }}
                                </span>
                            </a-table-summary-cell>
                        </a-table-summary-row>
                    </template>
                </a-table>

                <a-empty v-if="reportData.rows.length === 0" description="No transactions found for this period" class="mt-30" />
            </div>
        </div>
    </a-spin>

    <!-- Invoice Detail Modal -->
    <a-modal v-model:open="saleModalVisible" width="680px" :footer="null" :bodyStyle="{ padding: '0' }" class="cl-invoice-modal">
        <template #title>
            <div class="cl-modal-title">
                <FileTextOutlined style="color:#2563eb;margin-right:8px;" />
                Invoice &mdash; {{ selectedSale ? selectedSale.reference : '' }}
            </div>
        </template>
        <div v-if="selectedSale" class="cl-modal-body">
            <div class="cl-modal-meta">
                <div class="cl-meta-item">
                    <span class="cl-meta-label">Invoice No.</span>
                    <span class="cl-meta-value cl-meta-bold">{{ selectedSale.reference }}</span>
                </div>
                <div class="cl-meta-item">
                    <span class="cl-meta-label">Date</span>
                    <span class="cl-meta-value">{{ selectedSale.date }}</span>
                </div>
                <div class="cl-meta-item">
                    <span class="cl-meta-label">Total Amount</span>
                    <span class="cl-meta-value cl-meta-total">PKR {{ fmt(selectedSale.debit) }}</span>
                </div>
            </div>
            <table class="cl-modal-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Code</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in selectedSale.items" :key="idx">
                        <td class="cl-idx">{{ idx + 1 }}</td>
                        <td class="cl-pname">{{ item.name }}</td>
                        <td><code v-if="item.item_code" class="cl-code">{{ item.item_code }}</code></td>
                        <td class="text-right cl-qty">{{ item.qty }}</td>
                        <td class="text-right">PKR {{ fmt(item.unit_price) }}</td>
                        <td class="text-right cl-sub">PKR {{ fmt(item.subtotal) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="cl-foot-total">
                        <td colspan="5" class="text-right">Grand Total</td>
                        <td class="text-right">PKR {{ fmt(selectedSale.debit) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </a-modal>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import {
    PrinterOutlined, SearchOutlined, ShoppingOutlined, SyncOutlined,
    FileTextOutlined, UserOutlined, CalendarOutlined, AccountBookOutlined,
    DollarOutlined, ShoppingCartOutlined, CheckCircleOutlined, WalletOutlined,
} from '@ant-design/icons-vue';
import { message, notification } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: {
        AdminPageHeader, PrinterOutlined, SearchOutlined, ShoppingOutlined, SyncOutlined,
        FileTextOutlined, UserOutlined, CalendarOutlined, AccountBookOutlined,
        DollarOutlined, ShoppingCartOutlined, CheckCircleOutlined, WalletOutlined,
    },
    setup() {
        const axiosAdmin  = window.axiosAdmin;
        const loading     = ref(false);
        const generated   = ref(false);
        const backfilling = ref(false);
        const customers   = ref([]);
        const filters     = ref({ user_id: null, date_from: dayjs().startOf('year'), date_to: dayjs() });
        const reportData  = ref({ rows: [], opening_balance: 0, customer: null, date_from: '', date_to: '' });

        const saleModalVisible = ref(false);
        const selectedSale     = ref(null);
        const openSaleDetail   = (record) => { selectedSale.value = record; saleModalVisible.value = true; };

        const expandedRowKeys = ref([]);
        const expandable = computed(() => ({
            expandedRowKeys: expandedRowKeys.value,
            onExpand: (expanded, record) => {
                const key = tableRows.value.indexOf(record);
                expandedRowKeys.value = expanded
                    ? [...expandedRowKeys.value, key]
                    : expandedRowKeys.value.filter(k => k !== key);
            },
            rowExpandable: (record) => record.type === 'Sale' && record.items?.length > 0,
        }));

        const columns = [
            { title: 'Date',      dataIndex: 'date', key: 'date',   width: 110 },
            { title: 'Reference', key: 'reference',                  width: 200 },
            { title: 'Type',      key: 'type',                       width: 155 },
            { title: 'Debit (PKR)',  key: 'debit',  width: 145, align: 'right' },
            { title: 'Credit (PKR)', key: 'credit', width: 145, align: 'right' },
            { title: 'Balance',   key: 'running_balance',            align: 'right' },
        ];

        const fmt         = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate  = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const filterOption = (input, option) => option.children?.()[0]?.children?.toLowerCase().includes(input.toLowerCase());

        const typeClass = (t) => ({ 'Sale': 'cl-pill-sale', 'Sales Return': 'cl-pill-return', 'Payment Received': 'cl-pill-payment' })[t] || '';
        const typeDot   = (t) => ({ 'Sale': 'cl-dot-blue', 'Sales Return': 'cl-dot-orange', 'Payment Received': 'cl-dot-green' })[t] || '';
        const rowClass  = (r) => r._is_opening ? 'cl-row-ob' : (r.type === 'Sale' ? 'cl-row-sale' : r.type === 'Payment Received' ? 'cl-row-payment' : '');

        const openingBalance = computed(() => reportData.value.opening_balance ?? 0);
        const totalDebit     = computed(() => reportData.value.rows.reduce((s, r) => s + +r.debit,  0));
        const totalCredit    = computed(() => reportData.value.rows.reduce((s, r) => s + +r.credit, 0));
        const closingBalance = computed(() => openingBalance.value + totalDebit.value - totalCredit.value);

        const tableRows = computed(() => {
            const ob = openingBalance.value;
            return [{
                date: reportData.value.date_from, reference: '—', type: 'Opening Balance',
                debit: ob > 0 ? ob : 0, credit: ob < 0 ? Math.abs(ob) : 0,
                running_balance: ob, items: [], _is_opening: true,
            }, ...reportData.value.rows];
        });

        const loadCustomers = async () => {
            const res = await axiosAdmin.get('customers?limit=10000');
            customers.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
        };

        const load = async () => {
            loading.value = true; generated.value = true; expandedRowKeys.value = [];
            try {
                const res = await axiosAdmin.get('accounting/reports/customer-ledger', {
                    params: {
                        user_id:   filters.value.user_id || undefined,
                        date_from: filters.value.date_from?.format('YYYY-MM-DD'),
                        date_to:   filters.value.date_to?.format('YYYY-MM-DD'),
                    }
                });
                reportData.value = res.data;
            } catch (e) { message.error('Failed to load ledger'); }
            finally { loading.value = false; }
        };

        const backfillJEs = async () => {
            backfilling.value = true;
            try {
                const res = await axiosAdmin.post('accounting/backfill-jes');
                const d = res.data;
                notification.success({ message: 'JE Backfill Complete', description: `Generated: ${d.generated} | Skipped: ${d.skipped} | Failed: ${d.failed?.length ?? 0}`, duration: 8 });
                if (d.warnings?.length) notification.warning({ message: 'Backfill Warnings', description: d.warnings.slice(0,5).join('\n'), duration: 0, style: { whiteSpace:'pre-line' } });
            } catch (e) { message.error('Backfill failed: ' + (e.response?.data?.message || e.message)); }
            finally { backfilling.value = false; }
        };

        const print = () => window.print();
        onMounted(loadCustomers);

        return {
            loading, generated, backfilling, customers, filters, reportData, tableRows, columns,
            expandable, expandedRowKeys, fmt, formatDate, filterOption, typeClass, typeDot, rowClass,
            openingBalance, totalDebit, totalCredit, closingBalance,
            load, print, backfillJEs, saleModalVisible, selectedSale, openSaleDetail,
        };
    }
});
</script>

<style scoped>
/* ─── Filter Bar ────────────────────────────────────────────── */
.cl-filter-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.cl-filter-inner { display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
.cl-filter-icon {
    font-size: 22px; color: #2563eb;
    background: #eff6ff; border-radius: 10px;
    width: 44px; height: 44px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-bottom: 2px;
}
.cl-filter-group { display: flex; flex-direction: column; gap: 4px; min-width: 180px; flex: 1; }
.cl-label { font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.cl-select, .cl-date-picker { width: 100%; border-radius: 8px !important; }
.cl-filter-actions { display: flex; gap: 10px; padding-bottom: 1px; }
.cl-btn-generate { border-radius: 8px !important; background: linear-gradient(135deg,#2563eb,#1d4ed8) !important; border: none !important; font-weight: 600 !important; height: 36px !important; padding: 0 20px !important; }
.cl-btn-backfill { border-radius: 8px !important; height: 36px !important; }

/* ─── Empty State ───────────────────────────────────────────── */
.cl-empty-state { text-align: center; padding: 70px 20px; }
.cl-empty-icon { font-size: 56px; color: #cbd5e1; display: block; margin-bottom: 16px; }
.cl-empty-title { font-size: 17px; font-weight: 700; color: #475569; margin: 0 0 6px; }
.cl-empty-sub { color: #94a3b8; font-size: 13.5px; margin: 0; }

/* ─── Report Header ─────────────────────────────────────────── */
.cl-report-header {
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 60%, #3b82f6 100%);
    border-radius: 14px;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    color: #fff;
}
.cl-report-badge { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; opacity: .75; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
.cl-report-title { font-size: 22px; font-weight: 800; margin: 0 0 4px; color: #fff; }
.cl-report-period { font-size: 13px; opacity: .8; margin: 0; display: flex; align-items: center; gap: 6px; }
.cl-stat-chip { text-align: right; background: rgba(255,255,255,.15); border-radius: 12px; padding: 12px 18px; backdrop-filter: blur(4px); }
.cl-chip-label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; opacity: .75; margin-bottom: 4px; }
.cl-chip-value { display: block; font-size: 24px; font-weight: 800; line-height: 1; }
.cl-chip-tag { display: inline-block; margin-top: 6px; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.cl-chip-dr .cl-chip-tag { background: #fee2e2; color: #b91c1c; }
.cl-chip-cr .cl-chip-tag { background: #dcfce7; color: #15803d; }

/* ─── Summary Cards ─────────────────────────────────────────── */
.cl-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
.cl-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.cl-card-icon { font-size: 22px; border-radius: 10px; width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cl-card-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
.cl-card-value { font-size: 17px; font-weight: 800; color: #1e293b; line-height: 1.2; }
.cl-card-sub { font-size: 11px; color: #94a3b8; margin-top: 3px; }
.cl-card-ob     .cl-card-icon { background: #f1f5f9; color: #64748b; }
.cl-card-sales  .cl-card-icon { background: #eff6ff; color: #2563eb; }
.cl-card-payments .cl-card-icon { background: #f0fdf4; color: #16a34a; }
.cl-card-due    .cl-card-icon { background: #fff7ed; color: #ea580c; }
.cl-card-advance .cl-card-icon { background: #f0fdf4; color: #15803d; }
.cl-card-sales .cl-card-value { color: #2563eb; }
.cl-card-payments .cl-card-value { color: #16a34a; }
.cl-card-due .cl-card-value { color: #ea580c; }
.cl-card-advance .cl-card-value { color: #15803d; }

/* ─── Table ─────────────────────────────────────────────────── */
.cl-table-wrapper { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
.cl-date-cell { font-size: 12.5px; color: #64748b; }
.cl-ob-ref { color: #94a3b8; font-style: italic; font-size: 12px; }
.cl-ref-link { color: #2563eb; font-weight: 600; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 6px; }
.cl-ref-link:hover { color: #1d4ed8; text-decoration: underline; }
.cl-ref-plain { color: #475569; font-size: 13px; }

/* type pills */
.cl-type-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.cl-type-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.cl-type-ob { color: #94a3b8; font-style: italic; font-size: 12px; display: flex; align-items: center; gap: 5px; }
.cl-pill-sale     { background: #eff6ff; color: #1d4ed8; }
.cl-pill-return   { background: #fff7ed; color: #c2410c; }
.cl-pill-payment  { background: #f0fdf4; color: #15803d; }
.cl-dot-blue   { background: #2563eb; }
.cl-dot-orange { background: #f97316; }
.cl-dot-green  { background: #16a34a; }
.cl-dot-gray   { background: #94a3b8; }

/* amounts */
.cl-amount-dr  { color: #1d4ed8; font-weight: 700; font-size: 13px; }
.cl-amount-cr  { color: #15803d; font-weight: 700; font-size: 13px; }
.cl-amount-nil { color: #cbd5e1; }
.cl-balance    { font-weight: 800; font-size: 13.5px; }
.cl-balance small { font-size: 10px; margin-left: 3px; font-weight: 600; }
.cl-balance-dr { color: #b91c1c; }
.cl-balance-cr { color: #15803d; }

/* row tints */
:deep(.cl-row-ob > td)      { background: #fefce8 !important; }
:deep(.cl-row-payment > td) { background: #f0fdf4 !important; }

/* summary row */
:deep(.cl-summary-row > td) { background: #1e293b !important; }
.cl-summary-label { color: #cbd5e1; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; }
.cl-summary-dr  { color: #93c5fd; font-weight: 700; font-size: 13px; }
.cl-summary-cr  { color: #86efac; font-weight: 700; font-size: 13px; }
.cl-summary-bal { font-size: 14px; font-weight: 800; }
.cl-summary-bal-dr { color: #fca5a5; }
.cl-summary-bal-cr { color: #86efac; }

/* ─── Expandable Panel ──────────────────────────────────────── */
.cl-expanded { background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; padding: 14px 16px; }
.cl-expanded-header { font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
.cl-no-items { color: #94a3b8; font-size: 12px; padding: 8px; }

/* ─── Items Table (shared) ──────────────────────────────────── */
.cl-items-table, .cl-modal-table {
    width: 100%; border-collapse: collapse; font-size: 12.5px;
}
.cl-items-table thead tr, .cl-modal-table thead tr { background: #f1f5f9; }
.cl-items-table th, .cl-modal-table th {
    padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 700;
    color: #64748b; text-transform: uppercase; letter-spacing: .4px;
    border-bottom: 2px solid #e2e8f0;
}
.cl-items-table td, .cl-modal-table td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.cl-items-table tbody tr:hover td, .cl-modal-table tbody tr:hover td { background: #f8fafc; }
.cl-foot-total td { background: #1e293b; color: #f1f5f9; font-weight: 700; padding: 10px 12px; }
.cl-idx    { color: #94a3b8; width: 28px; font-size: 11px; }
.cl-pname  { font-weight: 600; color: #1e293b; }
.cl-code   { background: #f1f5f9; border-radius: 4px; padding: 1px 7px; font-size: 11px; color: #475569; font-family: monospace; }
.cl-qty    { font-weight: 700; color: #2563eb; }
.cl-sub    { font-weight: 700; color: #15803d; }
.text-right { text-align: right; }

/* ─── Invoice Modal ─────────────────────────────────────────── */
.cl-modal-title { font-size: 15px; font-weight: 700; color: #1e293b; }
.cl-modal-body { padding: 0 }
.cl-modal-meta {
    display: flex; gap: 0; border-bottom: 1px solid #e2e8f0;
    padding: 16px 24px; background: #f8fafc;
}
.cl-meta-item { flex: 1; }
.cl-meta-label { display: block; font-size: 10.5px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
.cl-meta-value { display: block; font-size: 14px; color: #1e293b; font-weight: 500; }
.cl-meta-bold  { font-weight: 700; }
.cl-meta-total { color: #2563eb; font-weight: 800; font-size: 16px; }
.cl-modal-table { border-radius: 0; }
.cl-modal-table thead tr { background: #f8fafc; }

@media print {
    .cl-filter-card, .cl-btn-generate, .cl-btn-backfill { display: none !important; }
    .cl-report-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
