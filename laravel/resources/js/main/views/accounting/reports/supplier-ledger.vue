<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Supplier Ledger" class="p-0">
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
                <a-breadcrumb-item>Supplier Ledger</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- Filter Card -->
    <div class="sl-filter-card">
        <div class="sl-filter-inner">
            <div class="sl-filter-icon"><ShopOutlined /></div>
            <div class="sl-filter-group">
                <label class="sl-label">Supplier</label>
                <a-select
                    v-model:value="filters.user_id"
                    class="sl-select"
                    show-search
                    :filter-option="filterOption"
                    placeholder="All Suppliers"
                    allow-clear
                >
                    <a-select-option v-for="s in suppliers" :key="s.xid" :value="s.xid">{{ s.name }}</a-select-option>
                </a-select>
            </div>
            <div class="sl-filter-group">
                <label class="sl-label">From Date</label>
                <a-date-picker v-model:value="filters.date_from" class="sl-date-picker" />
            </div>
            <div class="sl-filter-group">
                <label class="sl-label">To Date</label>
                <a-date-picker v-model:value="filters.date_to" class="sl-date-picker" />
            </div>
            <div class="sl-filter-actions">
                <a-button type="primary" class="sl-btn-generate" @click="load" :loading="loading">
                    <SearchOutlined /> Generate Report
                </a-button>
                <a-button v-if="generated" class="sl-btn-backfill" @click="backfillJEs" :loading="backfilling" title="Generate missing Journal Entries for all past purchases">
                    <SyncOutlined /> Backfill JEs
                </a-button>
            </div>
        </div>
    </div>

    <a-spin :spinning="loading">
        <!-- Empty State -->
        <div v-if="!generated && !loading" class="sl-empty-state">
            <FileTextOutlined class="sl-empty-icon" />
            <p class="sl-empty-title">No Report Generated</p>
            <p class="sl-empty-sub">Select filters above and click <b>Generate Report</b> to view the supplier ledger.</p>
        </div>

        <div v-if="generated" id="printable-area">

            <!-- Report Header -->
            <div class="sl-report-header">
                <div class="sl-report-header-left">
                    <div class="sl-report-badge"><AuditOutlined /> Supplier Ledger</div>
                    <h2 class="sl-report-title">{{ reportData.supplier ? reportData.supplier.name : 'All Suppliers' }}</h2>
                    <p class="sl-report-period"><CalendarOutlined /> {{ formatDate(reportData.date_from) }} &nbsp;→&nbsp; {{ formatDate(reportData.date_to) }}</p>
                </div>
                <div class="sl-report-header-right">
                    <div class="sl-stat-chip" :class="closingBalance >= 0 ? 'sl-chip-payable' : 'sl-chip-advance'">
                        <span class="sl-chip-label">Closing Balance</span>
                        <span class="sl-chip-value">PKR {{ fmt(Math.abs(closingBalance)) }}</span>
                        <span class="sl-chip-tag">{{ closingBalance >= 0 ? 'Payable' : 'Advance Paid' }}</span>
                    </div>
                </div>
            </div>

            <!-- Estimated amounts warning banner -->
            <div v-if="hasZeroTotalGrns" class="sl-warning-banner">
                <WarningOutlined class="sl-warning-icon" />
                <span>
                    Some GRN entries had <b>PKR 0</b> recorded unit prices — amounts shown are estimated from product purchase prices.
                    Enter unit prices on the GRN (or update product purchase prices) for accurate payable figures.
                </span>
            </div>

            <!-- Summary Cards -->
            <div class="sl-cards">
                <div class="sl-card sl-card-ob">
                    <div class="sl-card-icon"><DollarOutlined /></div>
                    <div>
                        <div class="sl-card-label">Opening Balance</div>
                        <div class="sl-card-value">PKR {{ fmt(Math.abs(openingBalance)) }}</div>
                        <div class="sl-card-sub">{{ openingBalance >= 0 ? 'Payable b/f' : 'Advance b/f' }}</div>
                    </div>
                </div>
                <div class="sl-card sl-card-purchases">
                    <div class="sl-card-icon"><ShoppingCartOutlined /></div>
                    <div>
                        <div class="sl-card-label">Total Purchases</div>
                        <div class="sl-card-value">PKR {{ fmt(totalCredit) }}</div>
                        <div class="sl-card-sub">{{ reportData.rows.filter(r => r.type === 'GRN' || r.type === 'Purchase').length }} GRN/order(s)</div>
                    </div>
                </div>
                <div class="sl-card sl-card-payments">
                    <div class="sl-card-icon"><CheckCircleOutlined /></div>
                    <div>
                        <div class="sl-card-label">Total Paid</div>
                        <div class="sl-card-value">PKR {{ fmt(totalDebit) }}</div>
                        <div class="sl-card-sub">{{ reportData.rows.filter(r => r.type === 'Payment Made').length }} payment(s)</div>
                    </div>
                </div>
                <div class="sl-card" :class="closingBalance >= 0 ? 'sl-card-payable' : 'sl-card-advance'">
                    <div class="sl-card-icon"><WalletOutlined /></div>
                    <div>
                        <div class="sl-card-label">{{ closingBalance >= 0 ? 'Amount Payable' : 'Advance Paid' }}</div>
                        <div class="sl-card-value">PKR {{ fmt(Math.abs(closingBalance)) }}</div>
                        <div class="sl-card-sub">Net closing balance</div>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="sl-table-wrapper">
                <a-table
                    :dataSource="tableRows"
                    :columns="columns"
                    :pagination="false"
                    size="middle"
                    :rowKey="(r, i) => i"
                    :scroll="{ x: 860 }"
                    :rowClassName="rowClass"
                    :expandable="expandable"
                    class="sl-table"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'date'">
                            <span class="sl-date-cell">{{ record.date }}</span>
                        </template>
                        <template v-if="column.key === 'reference'">
                            <span v-if="record._is_opening" class="sl-ob-ref">Opening Balance</span>
                            <span v-else class="sl-ref-wrap">
                                <FileTextOutlined style="margin-right:4px;font-size:11px;color:#7c3aed;" />
                                <span class="sl-ref-text">{{ record.reference }}</span>
                                <a-badge v-if="record.items && record.items.length" :count="record.items.length" :number-style="{ backgroundColor:'#7c3aed', fontSize:'9px', height:'16px', lineHeight:'16px', minWidth:'16px' }" />
                                <a-tooltip v-if="record.recorded_amt == 0 && +record.credit > 0" title="Amount estimated from product purchase prices — GRN had no unit prices">
                                    <span class="sl-est-badge">~est.</span>
                                </a-tooltip>
                            </span>
                        </template>
                        <template v-if="column.key === 'type'">
                            <span v-if="record._is_opening" class="sl-type-ob"><span class="sl-type-dot sl-dot-gray"></span>b/f</span>
                            <span v-else class="sl-type-pill" :class="typeClass(record.type)">
                                <span class="sl-type-dot" :class="typeDot(record.type)"></span>
                                {{ record.type }}
                            </span>
                        </template>
                        <template v-if="column.key === 'debit'">
                            <span v-if="+record.debit !== 0" class="sl-amount-dr">{{ fmt(record.debit) }}</span>
                            <span v-else class="sl-amount-nil">—</span>
                        </template>
                        <template v-if="column.key === 'credit'">
                            <span v-if="+record.credit !== 0" class="sl-amount-cr">
                                {{ fmt(record.credit) }}
                                <span v-if="record.recorded_amt == 0 && +record.credit > 0" class="sl-est-inline">~est</span>
                            </span>
                            <span v-else class="sl-amount-nil">—</span>
                        </template>
                        <template v-if="column.key === 'running_balance'">
                            <span class="sl-balance" :class="record.running_balance >= 0 ? 'sl-balance-pay' : 'sl-balance-adv'">
                                {{ fmt(Math.abs(record.running_balance)) }}
                                <small>{{ record.running_balance >= 0 ? 'Pay' : 'Adv' }}</small>
                            </span>
                        </template>
                    </template>

                    <!-- Expandable item rows -->
                    <template #expandedRowRender="{ record }">
                        <div v-if="record.items && record.items.length" class="sl-expanded">
                            <div class="sl-expanded-header">
                                <ShoppingCartOutlined /> Items received &mdash; <b>{{ record.reference }}</b>
                                <span v-if="record.recorded_amt == 0 && +record.credit > 0" class="sl-expanded-est-tag">
                                    ⚠ Estimated from purchase prices
                                </span>
                            </div>
                            <div v-if="record.recorded_amt == 0 && +record.credit > 0" class="sl-est-notice">
                                This GRN was recorded with no unit prices. The payable amount (PKR {{ fmt(record.credit) }}) is estimated from product purchase prices. Edit the GRN and enter unit prices to correct this.
                            </div>
                            <table class="sl-items-table">
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
                                        <td class="sl-idx">{{ idx + 1 }}</td>
                                        <td class="sl-pname">{{ item.name }}</td>
                                        <td><code v-if="item.item_code" class="sl-code">{{ item.item_code }}</code></td>
                                        <td class="text-right sl-qty">{{ item.qty }}</td>
                                        <td class="text-right" :class="item.unit_price == 0 ? 'sl-price-missing' : ''">
                                            {{ item.unit_price > 0 ? 'PKR ' + fmt(item.unit_price) : '—' }}
                                            <span v-if="item.unit_price == 0" class="sl-see-product">see product</span>
                                        </td>
                                        <td class="text-right sl-sub">{{ item.subtotal > 0 ? 'PKR ' + fmt(item.subtotal) : '—' }}</td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="+record.credit > 0">
                                    <tr class="sl-foot-total">
                                        <td colspan="5" class="text-right">{{ record.recorded_amt == 0 ? 'Estimated Total' : 'Total' }}</td>
                                        <td class="text-right">PKR {{ fmt(record.credit) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-else class="sl-no-items">No item details available.</div>
                    </template>

                    <!-- Summary footer -->
                    <template #summary>
                        <a-table-summary-row class="sl-summary-row">
                            <a-table-summary-cell :index="0" :col-span="3">
                                <span class="sl-summary-label">CLOSING TOTALS</span>
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="3" align="right">
                                <span class="sl-summary-dr">{{ fmt(openingBalance < 0 ? Math.abs(openingBalance) + totalDebit : totalDebit) }}</span>
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="4" align="right">
                                <span class="sl-summary-cr">{{ fmt(openingBalance > 0 ? openingBalance + totalCredit : totalCredit) }}</span>
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="5" align="right">
                                <span class="sl-summary-bal" :class="closingBalance >= 0 ? 'sl-summary-bal-pay' : 'sl-summary-bal-adv'">
                                    PKR {{ fmt(Math.abs(closingBalance)) }} {{ closingBalance >= 0 ? 'Pay' : 'Adv' }}
                                </span>
                            </a-table-summary-cell>
                        </a-table-summary-row>
                    </template>
                </a-table>

                <a-empty v-if="reportData.rows.length === 0" description="No transactions found for this period" class="mt-30" />
            </div>
        </div>
    </a-spin>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import {
    PrinterOutlined, SearchOutlined, ShoppingCartOutlined, SyncOutlined,
    FileTextOutlined, ShopOutlined, CalendarOutlined, AuditOutlined,
    DollarOutlined, CheckCircleOutlined, WalletOutlined, WarningOutlined,
} from '@ant-design/icons-vue';
import { message, notification } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: {
        AdminPageHeader, PrinterOutlined, SearchOutlined, ShoppingCartOutlined, SyncOutlined,
        FileTextOutlined, ShopOutlined, CalendarOutlined, AuditOutlined,
        DollarOutlined, CheckCircleOutlined, WalletOutlined, WarningOutlined,
    },
    setup() {
        const axiosAdmin  = window.axiosAdmin;
        const loading     = ref(false);
        const generated   = ref(false);
        const backfilling = ref(false);
        const suppliers   = ref([]);
        const filters     = ref({ user_id: null, date_from: dayjs().startOf('year'), date_to: dayjs() });
        const reportData  = ref({ rows: [], opening_balance: 0, supplier: null, date_from: '', date_to: '' });

        const expandedRowKeys = ref([]);
        const expandable = computed(() => ({
            expandedRowKeys: expandedRowKeys.value,
            onExpand: (expanded, record) => {
                const key = tableRows.value.indexOf(record);
                expandedRowKeys.value = expanded
                    ? [...expandedRowKeys.value, key]
                    : expandedRowKeys.value.filter(k => k !== key);
            },
            rowExpandable: (record) => (record.type === 'Purchase' || record.type === 'GRN') && record.items?.length > 0,
        }));

        const columns = [
            { title: 'Date',         dataIndex: 'date', key: 'date',  width: 110 },
            { title: 'Reference',    key: 'reference',                 width: 210 },
            { title: 'Type',         key: 'type',                      width: 155 },
            { title: 'Debit (PKR)',  key: 'debit',  width: 145, align: 'right' },
            { title: 'Credit (PKR)', key: 'credit', width: 165, align: 'right' },
            { title: 'Balance',      key: 'running_balance',           align: 'right' },
        ];

        const fmt          = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate   = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const filterOption = (input, option) => option.children?.()[0]?.children?.toLowerCase().includes(input.toLowerCase());

        const typeClass = (t) => ({
            'Purchase': 'sl-pill-purchase', 'GRN': 'sl-pill-grn',
            'Purchase Return': 'sl-pill-return', 'Payment Made': 'sl-pill-payment',
        })[t] || '';
        const typeDot = (t) => ({
            'Purchase': 'sl-dot-purple', 'GRN': 'sl-dot-violet',
            'Purchase Return': 'sl-dot-orange', 'Payment Made': 'sl-dot-green',
        })[t] || '';
        const rowClass = (r) => r._is_opening ? 'sl-row-ob'
            : r.type === 'Payment Made' ? 'sl-row-payment'
            : (r.type === 'GRN' || r.type === 'Purchase') ? 'sl-row-purchase' : '';

        const openingBalance = computed(() => reportData.value.opening_balance ?? 0);
        const totalDebit     = computed(() => reportData.value.rows.reduce((s, r) => s + +r.debit,  0));
        const totalCredit    = computed(() => reportData.value.rows.reduce((s, r) => s + +r.credit, 0));
        const closingBalance = computed(() => openingBalance.value + totalCredit.value - totalDebit.value);
        const hasZeroTotalGrns = computed(() =>
            reportData.value.rows.some(r => (r.type === 'GRN' || r.type === 'Purchase') && r.recorded_amt == 0 && +r.credit > 0)
        );

        const tableRows = computed(() => {
            const ob = openingBalance.value;
            return [{
                date: reportData.value.date_from, reference: '—', type: 'Opening Balance',
                debit: ob < 0 ? Math.abs(ob) : 0, credit: ob > 0 ? ob : 0,
                running_balance: ob, items: [], _is_opening: true,
            }, ...reportData.value.rows];
        });

        const loadSuppliers = async () => {
            const res = await axiosAdmin.get('suppliers?limit=10000');
            suppliers.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
        };

        const load = async () => {
            loading.value = true; generated.value = true; expandedRowKeys.value = [];
            try {
                const res = await axiosAdmin.get('accounting/reports/supplier-ledger', {
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
        onMounted(loadSuppliers);

        return {
            loading, generated, backfilling, suppliers, filters, reportData, tableRows, columns,
            expandable, expandedRowKeys, hasZeroTotalGrns, fmt, formatDate, filterOption,
            typeClass, typeDot, rowClass, openingBalance, totalDebit, totalCredit, closingBalance,
            load, print, backfillJEs,
        };
    }
});
</script>

<style scoped>
/* ─── Filter Bar ────────────────────────────────────────────── */
.sl-filter-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.sl-filter-inner { display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
.sl-filter-icon {
    font-size: 22px; color: #7c3aed;
    background: #f5f3ff; border-radius: 10px;
    width: 44px; height: 44px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-bottom: 2px;
}
.sl-filter-group { display: flex; flex-direction: column; gap: 4px; min-width: 180px; flex: 1; }
.sl-label { font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.sl-select, .sl-date-picker { width: 100%; border-radius: 8px !important; }
.sl-filter-actions { display: flex; gap: 10px; padding-bottom: 1px; }
.sl-btn-generate { border-radius: 8px !important; background: linear-gradient(135deg,#7c3aed,#6d28d9) !important; border: none !important; font-weight: 600 !important; height: 36px !important; padding: 0 20px !important; }
.sl-btn-backfill { border-radius: 8px !important; height: 36px !important; }

/* ─── Empty State ───────────────────────────────────────────── */
.sl-empty-state { text-align: center; padding: 70px 20px; }
.sl-empty-icon { font-size: 56px; color: #cbd5e1; display: block; margin-bottom: 16px; }
.sl-empty-title { font-size: 17px; font-weight: 700; color: #475569; margin: 0 0 6px; }
.sl-empty-sub { color: #94a3b8; font-size: 13.5px; margin: 0; }

/* ─── Report Header ─────────────────────────────────────────── */
.sl-report-header {
    background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 60%, #8b5cf6 100%);
    border-radius: 14px;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    color: #fff;
}
.sl-report-badge { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; opacity: .75; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
.sl-report-title { font-size: 22px; font-weight: 800; margin: 0 0 4px; color: #fff; }
.sl-report-period { font-size: 13px; opacity: .8; margin: 0; display: flex; align-items: center; gap: 6px; }
.sl-stat-chip { text-align: right; background: rgba(255,255,255,.15); border-radius: 12px; padding: 12px 18px; backdrop-filter: blur(4px); }
.sl-chip-label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; opacity: .75; margin-bottom: 4px; }
.sl-chip-value { display: block; font-size: 24px; font-weight: 800; line-height: 1; }
.sl-chip-tag { display: inline-block; margin-top: 6px; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.sl-chip-payable .sl-chip-tag { background: #fee2e2; color: #b91c1c; }
.sl-chip-advance .sl-chip-tag { background: #dcfce7; color: #15803d; }

/* ─── Warning Banner ────────────────────────────────────────── */
.sl-warning-banner {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-left: 4px solid #f59e0b;
    border-radius: 10px;
    padding: 12px 18px;
    margin-bottom: 16px;
    font-size: 13px;
    color: #92400e;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.5;
}
.sl-warning-icon { font-size: 16px; color: #f59e0b; flex-shrink: 0; margin-top: 1px; }

/* ─── Summary Cards ─────────────────────────────────────────── */
.sl-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
.sl-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.sl-card-icon { font-size: 22px; border-radius: 10px; width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sl-card-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
.sl-card-value { font-size: 17px; font-weight: 800; color: #1e293b; line-height: 1.2; }
.sl-card-sub { font-size: 11px; color: #94a3b8; margin-top: 3px; }
.sl-card-ob       .sl-card-icon { background: #f1f5f9; color: #64748b; }
.sl-card-purchases .sl-card-icon { background: #f5f3ff; color: #7c3aed; }
.sl-card-payments  .sl-card-icon { background: #f0fdf4; color: #16a34a; }
.sl-card-payable   .sl-card-icon { background: #fff7ed; color: #ea580c; }
.sl-card-advance   .sl-card-icon { background: #f0fdf4; color: #15803d; }
.sl-card-purchases .sl-card-value { color: #7c3aed; }
.sl-card-payments  .sl-card-value { color: #16a34a; }
.sl-card-payable   .sl-card-value { color: #ea580c; }
.sl-card-advance   .sl-card-value { color: #15803d; }

/* ─── Table ─────────────────────────────────────────────────── */
.sl-table-wrapper { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
.sl-date-cell { font-size: 12.5px; color: #64748b; }
.sl-ob-ref { color: #94a3b8; font-style: italic; font-size: 12px; }
.sl-ref-wrap { display: flex; align-items: center; gap: 5px; font-size: 13px; color: #334155; }
.sl-ref-text { font-weight: 600; }
.sl-est-badge { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 4px; font-size: 9.5px; font-weight: 700; color: #92400e; padding: 1px 5px; flex-shrink: 0; }

/* type pills */
.sl-type-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.sl-type-dot  { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.sl-type-ob   { color: #94a3b8; font-style: italic; font-size: 12px; display: flex; align-items: center; gap: 5px; }
.sl-pill-purchase { background: #f5f3ff; color: #6d28d9; }
.sl-pill-grn      { background: #ede9fe; color: #7c3aed; }
.sl-pill-return   { background: #fff7ed; color: #c2410c; }
.sl-pill-payment  { background: #f0fdf4; color: #15803d; }
.sl-dot-purple { background: #7c3aed; }
.sl-dot-violet { background: #8b5cf6; }
.sl-dot-orange { background: #f97316; }
.sl-dot-green  { background: #16a34a; }
.sl-dot-gray   { background: #94a3b8; }

/* amounts */
.sl-amount-dr  { color: #1d4ed8; font-weight: 700; font-size: 13px; }
.sl-amount-cr  { color: #7c3aed; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.sl-amount-nil { color: #cbd5e1; }
.sl-est-inline { background: #fef3c7; border: 1px solid #fde68a; border-radius: 3px; font-size: 9px; font-weight: 700; color: #92400e; padding: 0 4px; }
.sl-balance     { font-weight: 800; font-size: 13.5px; }
.sl-balance small { font-size: 10px; margin-left: 3px; font-weight: 600; }
.sl-balance-pay { color: #dc2626; }
.sl-balance-adv { color: #15803d; }

/* row tints */
:deep(.sl-row-ob > td)       { background: #fefce8 !important; }
:deep(.sl-row-payment > td)  { background: #f0fdf4 !important; }
:deep(.sl-row-purchase > td) { background: #faf5ff !important; }

/* summary */
:deep(.sl-summary-row > td) { background: #1e293b !important; }
.sl-summary-label  { color: #cbd5e1; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; }
.sl-summary-dr     { color: #93c5fd; font-weight: 700; font-size: 13px; }
.sl-summary-cr     { color: #c4b5fd; font-weight: 700; font-size: 13px; }
.sl-summary-bal    { font-size: 14px; font-weight: 800; }
.sl-summary-bal-pay { color: #fca5a5; }
.sl-summary-bal-adv { color: #86efac; }

/* ─── Expandable Panel ──────────────────────────────────────── */
.sl-expanded { background: #faf5ff; border-radius: 8px; border: 1px solid #ede9fe; padding: 14px 16px; }
.sl-expanded-header { font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.sl-expanded-est-tag { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 4px; font-size: 10px; font-weight: 700; color: #92400e; padding: 1px 7px; }
.sl-est-notice { font-size: 12px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 8px 12px; margin-bottom: 10px; line-height: 1.5; }
.sl-no-items { color: #94a3b8; font-size: 12px; padding: 8px; }

/* ─── Items Table ───────────────────────────────────────────── */
.sl-items-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.sl-items-table thead tr { background: #ede9fe; }
.sl-items-table th {
    padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 700;
    color: #6d28d9; text-transform: uppercase; letter-spacing: .4px;
    border-bottom: 2px solid #ddd6fe;
}
.sl-items-table td { padding: 9px 12px; border-bottom: 1px solid #f5f3ff; color: #334155; }
.sl-items-table tbody tr:hover td { background: #f5f3ff; }
.sl-foot-total td { background: #1e293b; color: #f1f5f9; font-weight: 700; padding: 10px 12px; }
.sl-idx  { color: #94a3b8; width: 28px; font-size: 11px; }
.sl-pname { font-weight: 600; color: #1e293b; }
.sl-code { background: #ede9fe; border-radius: 4px; padding: 1px 7px; font-size: 11px; color: #6d28d9; font-family: monospace; }
.sl-qty  { font-weight: 700; color: #7c3aed; }
.sl-sub  { font-weight: 700; color: #dc2626; }
.sl-price-missing { color: #f59e0b !important; }
.sl-see-product { font-size: 10px; color: #f59e0b; margin-left: 4px; }
.text-right { text-align: right; }

@media print {
    .sl-filter-card, .sl-btn-generate, .sl-btn-backfill { display: none !important; }
    .sl-report-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
