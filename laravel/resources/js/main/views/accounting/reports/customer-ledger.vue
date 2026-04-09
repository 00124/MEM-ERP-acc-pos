<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Customer Ledger" class="p-0">
                <template #extra>
                    <a-button class="ldr-print-btn" @click="print"><PrinterOutlined /> Print</a-button>
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

    <!-- ── Hero filter bar ── -->
    <div class="ldr-hero ldr-hero-blue">
        <div class="ldr-hero-glow ldr-glow-1"></div>
        <div class="ldr-hero-glow ldr-glow-2"></div>

        <div class="ldr-hero-top">
            <div class="ldr-hero-title">
                <span class="ldr-hero-icon"><UserOutlined /></span>
                <div>
                    <h1 class="ldr-h1">Customer Ledger</h1>
                    <p class="ldr-sub">Receivables &amp; payment history</p>
                </div>
            </div>
            <div v-if="generated" class="ldr-hero-kpi-row">
                <div class="ldr-kpi ldr-kpi-glass">
                    <div class="ldr-kpi-val">PKR {{ fmt(totalDebit) }}</div>
                    <div class="ldr-kpi-lbl">Total Billed</div>
                </div>
                <div class="ldr-kpi ldr-kpi-glass">
                    <div class="ldr-kpi-val">PKR {{ fmt(totalCredit) }}</div>
                    <div class="ldr-kpi-lbl">Total Received</div>
                </div>
                <div class="ldr-kpi ldr-kpi-glass ldr-kpi-accent" :class="closingBalance >= 0 ? 'ldr-kpi-due' : 'ldr-kpi-ok'">
                    <div class="ldr-kpi-val">PKR {{ fmt(Math.abs(closingBalance)) }}</div>
                    <div class="ldr-kpi-lbl">{{ closingBalance >= 0 ? 'Receivable' : 'Advance Paid' }}</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="ldr-filters">
            <div class="ldr-filter-field">
                <label class="ldr-filter-label"><UserOutlined /> Customer</label>
                <a-select v-model:value="filters.user_id" class="ldr-input" show-search :filter-option="filterOption" placeholder="All Customers" allow-clear>
                    <a-select-option v-for="c in customers" :key="c.xid" :value="c.xid">{{ c.name }}</a-select-option>
                </a-select>
            </div>
            <div class="ldr-filter-field ldr-filter-sm">
                <label class="ldr-filter-label"><CalendarOutlined /> From</label>
                <a-date-picker v-model:value="filters.date_from" class="ldr-input" />
            </div>
            <div class="ldr-filter-field ldr-filter-sm">
                <label class="ldr-filter-label"><CalendarOutlined /> To</label>
                <a-date-picker v-model:value="filters.date_to" class="ldr-input" />
            </div>
            <div class="ldr-filter-actions">
                <button class="ldr-gen-btn" @click="load" :disabled="loading">
                    <span v-if="loading" class="ldr-spinner"></span>
                    <SearchOutlined v-else />
                    {{ loading ? 'Loading…' : 'Generate' }}
                </button>
            </div>
        </div>
    </div>

    <!-- ── Empty state ── -->
    <div v-if="!generated && !loading" class="ldr-idle">
        <div class="ldr-idle-ring">
            <BarChartOutlined class="ldr-idle-ico" />
        </div>
        <h3 class="ldr-idle-h">Ready to generate</h3>
        <p class="ldr-idle-p">Choose a customer and date range above, then click <b>Generate</b>.</p>
    </div>

    <a-spin :spinning="loading">
        <div v-if="generated" id="printable-area" class="ldr-body">

            <!-- Summary strip -->
            <div class="ldr-stat-strip">
                <div class="ldr-stat ldr-stat-neutral">
                    <div class="ldr-stat-icon"><DollarOutlined /></div>
                    <div class="ldr-stat-info">
                        <div class="ldr-stat-num">PKR {{ fmt(Math.abs(openingBalance)) }}</div>
                        <div class="ldr-stat-lbl">Opening Balance</div>
                        <div class="ldr-stat-tag">{{ openingBalance >= 0 ? 'Receivable b/f' : 'Advance b/f' }}</div>
                    </div>
                </div>
                <div class="ldr-stat ldr-stat-blue">
                    <div class="ldr-stat-icon"><FileTextOutlined /></div>
                    <div class="ldr-stat-info">
                        <div class="ldr-stat-num">PKR {{ fmt(totalDebit) }}</div>
                        <div class="ldr-stat-lbl">Total Sales</div>
                        <div class="ldr-stat-tag">{{ reportData.rows.filter(r=>r.type==='Sale').length }} invoice(s)</div>
                    </div>
                </div>
                <div class="ldr-stat ldr-stat-green">
                    <div class="ldr-stat-icon"><CheckCircleOutlined /></div>
                    <div class="ldr-stat-info">
                        <div class="ldr-stat-num">PKR {{ fmt(totalCredit) }}</div>
                        <div class="ldr-stat-lbl">Total Received</div>
                        <div class="ldr-stat-tag">{{ reportData.rows.filter(r=>r.type==='Payment Received').length }} payment(s)</div>
                    </div>
                </div>
                <div class="ldr-stat" :class="closingBalance >= 0 ? 'ldr-stat-red' : 'ldr-stat-emerald'">
                    <div class="ldr-stat-icon"><WalletOutlined /></div>
                    <div class="ldr-stat-info">
                        <div class="ldr-stat-num">PKR {{ fmt(Math.abs(closingBalance)) }}</div>
                        <div class="ldr-stat-lbl">{{ closingBalance >= 0 ? 'Receivable' : 'Advance Paid' }}</div>
                        <div class="ldr-stat-tag">Net closing</div>
                    </div>
                </div>
            </div>

            <!-- Table card -->
            <div class="ldr-table-card">
                <div class="ldr-table-header">
                    <div class="ldr-table-title">
                        <span class="ldr-table-dot ldr-dot-blue"></span>
                        Transaction Ledger
                        <span class="ldr-table-count">{{ tableRows.length }} rows</span>
                    </div>
                    <div class="ldr-table-meta">
                        {{ formatDate(reportData.date_from) }} — {{ formatDate(reportData.date_to) }}
                        <span v-if="reportData.customer" class="ldr-table-customer"> · {{ reportData.customer.name }}</span>
                    </div>
                </div>

                <a-table
                    :dataSource="tableRows"
                    :columns="columns"
                    :pagination="false"
                    size="middle"
                    :rowKey="(r,i)=>i"
                    :scroll="{ x: 860 }"
                    :rowClassName="rowClass"
                    :expandable="expandable"
                    class="ldr-table"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'date'">
                            <span class="ldr-date">{{ record.date }}</span>
                        </template>
                        <template v-if="column.key === 'reference'">
                            <span v-if="record._is_opening" class="ldr-ob-ref">— Opening Balance —</span>
                            <span v-else-if="record.order_xid && record.type==='Sale'" class="ldr-ref-link" @click="openSaleDetail(record)">
                                <span class="ldr-ref-icon"><FileTextOutlined /></span>
                                {{ record.reference }}
                                <span class="ldr-ref-badge" v-if="record.items?.length">{{ record.items.length }}</span>
                            </span>
                            <span v-else class="ldr-ref-plain">{{ record.reference }}</span>
                        </template>
                        <template v-if="column.key === 'type'">
                            <span v-if="record._is_opening" class="ldr-pill ldr-pill-ob">b/f</span>
                            <span v-else class="ldr-pill" :class="typePill(record.type)">{{ record.type }}</span>
                        </template>
                        <template v-if="column.key === 'debit'">
                            <span v-if="+record.debit" class="ldr-num ldr-num-dr">{{ fmt(record.debit) }}</span>
                            <span v-else class="ldr-nil">—</span>
                        </template>
                        <template v-if="column.key === 'credit'">
                            <span v-if="+record.credit" class="ldr-num ldr-num-cr">{{ fmt(record.credit) }}</span>
                            <span v-else class="ldr-nil">—</span>
                        </template>
                        <template v-if="column.key === 'running_balance'">
                            <span class="ldr-bal" :class="record.running_balance >= 0 ? 'ldr-bal-dr' : 'ldr-bal-cr'">
                                {{ fmt(Math.abs(record.running_balance)) }}
                                <em>{{ record.running_balance >= 0 ? 'Dr' : 'Cr' }}</em>
                            </span>
                        </template>
                    </template>

                    <template #expandedRowRender="{ record }">
                        <div v-if="record.items?.length" class="ldr-expand">
                            <div class="ldr-expand-hd"><ShoppingOutlined /> {{ record.reference }} &mdash; Item Breakdown</div>
                            <table class="ldr-itm-tbl">
                                <thead><tr><th>#</th><th>Product</th><th>Code</th><th class="r">Qty</th><th class="r">Unit Price</th><th class="r">Subtotal</th></tr></thead>
                                <tbody>
                                    <tr v-for="(it,i) in record.items" :key="i">
                                        <td class="ldr-idx">{{ i+1 }}</td>
                                        <td class="ldr-pn">{{ it.name }}</td>
                                        <td><code v-if="it.item_code" class="ldr-code">{{ it.item_code }}</code></td>
                                        <td class="r ldr-qty">{{ it.qty }}</td>
                                        <td class="r">PKR {{ fmt(it.unit_price) }}</td>
                                        <td class="r ldr-sub">PKR {{ fmt(it.subtotal) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot><tr class="ldr-foot"><td colspan="5" class="r">Total</td><td class="r">PKR {{ fmt(record.debit) }}</td></tr></tfoot>
                            </table>
                        </div>
                        <div v-else class="ldr-no-items">No item details available.</div>
                    </template>

                </a-table>
                <a-empty v-if="!reportData.rows.length" description="No transactions in this period" class="ldr-empty-tbl" />
            </div>

            <!-- ── Closing Totals Card ── -->
            <div class="ldr-closing-card">
                <div class="ldr-closing-label">
                    <span class="ldr-closing-line"></span>
                    <span class="ldr-closing-title">Closing Totals</span>
                    <span class="ldr-closing-line"></span>
                </div>
                <div class="ldr-closing-grid">
                    <div class="ldr-closing-block ldr-closing-dr">
                        <div class="ldr-closing-icon"><ArrowUpOutlined /></div>
                        <div class="ldr-closing-info">
                            <div class="ldr-closing-val">PKR {{ fmt(openingBalance > 0 ? openingBalance + totalDebit : totalDebit) }}</div>
                            <div class="ldr-closing-sub">Total Debit</div>
                        </div>
                    </div>
                    <div class="ldr-closing-divider"></div>
                    <div class="ldr-closing-block ldr-closing-cr">
                        <div class="ldr-closing-icon"><ArrowDownOutlined /></div>
                        <div class="ldr-closing-info">
                            <div class="ldr-closing-val">PKR {{ fmt(openingBalance < 0 ? Math.abs(openingBalance) + totalCredit : totalCredit) }}</div>
                            <div class="ldr-closing-sub">Total Credit</div>
                        </div>
                    </div>
                    <div class="ldr-closing-divider"></div>
                    <div class="ldr-closing-block ldr-closing-bal" :class="closingBalance >= 0 ? 'ldr-closing-bal-dr' : 'ldr-closing-bal-cr'">
                        <div class="ldr-closing-icon">
                            <RiseOutlined v-if="closingBalance >= 0" />
                            <FallOutlined v-else />
                        </div>
                        <div class="ldr-closing-info">
                            <div class="ldr-closing-val ldr-closing-val-lg">PKR {{ fmt(Math.abs(closingBalance)) }}</div>
                            <div class="ldr-closing-sub">
                                <span class="ldr-closing-badge" :class="closingBalance >= 0 ? 'ldr-badge-dr' : 'ldr-badge-cr'">
                                    {{ closingBalance >= 0 ? 'Receivable' : 'Advance Paid' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </a-spin>

    <!-- ── Invoice Modal ── -->
    <a-modal v-model:open="saleModalVisible" :footer="null" width="700px" class="ldr-modal">
        <template #title>
            <div class="ldr-modal-hd">
                <span class="ldr-modal-ico"><FileTextOutlined /></span>
                Invoice — {{ selectedSale?.reference }}
            </div>
        </template>
        <div v-if="selectedSale" class="ldr-modal-body">
            <div class="ldr-modal-meta">
                <div class="ldr-meta-blk"><span class="ldr-meta-k">Invoice</span><span class="ldr-meta-v ldr-meta-ref">{{ selectedSale.reference }}</span></div>
                <div class="ldr-meta-blk"><span class="ldr-meta-k">Date</span><span class="ldr-meta-v">{{ selectedSale.date }}</span></div>
                <div class="ldr-meta-blk"><span class="ldr-meta-k">Total</span><span class="ldr-meta-v ldr-meta-total">PKR {{ fmt(selectedSale.debit) }}</span></div>
            </div>
            <table class="ldr-itm-tbl ldr-modal-tbl">
                <thead><tr><th>#</th><th>Product</th><th>Code</th><th class="r">Qty</th><th class="r">Unit Price</th><th class="r">Subtotal</th></tr></thead>
                <tbody>
                    <tr v-for="(it,i) in selectedSale.items" :key="i">
                        <td class="ldr-idx">{{ i+1 }}</td>
                        <td class="ldr-pn">{{ it.name }}</td>
                        <td><code v-if="it.item_code" class="ldr-code">{{ it.item_code }}</code></td>
                        <td class="r ldr-qty">{{ it.qty }}</td>
                        <td class="r">PKR {{ fmt(it.unit_price) }}</td>
                        <td class="r ldr-sub">PKR {{ fmt(it.subtotal) }}</td>
                    </tr>
                </tbody>
                <tfoot><tr class="ldr-foot"><td colspan="5" class="r">Grand Total</td><td class="r">PKR {{ fmt(selectedSale.debit) }}</td></tr></tfoot>
            </table>
        </div>
    </a-modal>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import {
    PrinterOutlined, SearchOutlined, ShoppingOutlined,
    FileTextOutlined, UserOutlined, CalendarOutlined, DollarOutlined,
    CheckCircleOutlined, WalletOutlined, BarChartOutlined,
    ArrowUpOutlined, ArrowDownOutlined, RiseOutlined, FallOutlined,
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: {
        AdminPageHeader, PrinterOutlined, SearchOutlined, ShoppingOutlined,
        FileTextOutlined, UserOutlined, CalendarOutlined, DollarOutlined,
        CheckCircleOutlined, WalletOutlined, BarChartOutlined,
        ArrowUpOutlined, ArrowDownOutlined, RiseOutlined, FallOutlined,
    },
    setup() {
        const axiosAdmin  = window.axiosAdmin;
        const loading     = ref(false);
        const generated   = ref(false);
        const customers   = ref([]);
        const filters     = ref({ user_id: null, date_from: dayjs().startOf('year'), date_to: dayjs() });
        const reportData  = ref({ rows: [], opening_balance: 0, customer: null, date_from: '', date_to: '' });
        const saleModalVisible = ref(false);
        const selectedSale     = ref(null);
        const openSaleDetail   = (r) => { selectedSale.value = r; saleModalVisible.value = true; };

        const expandedRowKeys = ref([]);
        const expandable = computed(() => ({
            expandedRowKeys: expandedRowKeys.value,
            onExpand: (expanded, record) => {
                const key = tableRows.value.indexOf(record);
                expandedRowKeys.value = expanded ? [...expandedRowKeys.value, key] : expandedRowKeys.value.filter(k => k !== key);
            },
            rowExpandable: (r) => r.type === 'Sale' && r.items?.length > 0,
        }));

        const columns = [
            { title: 'Date',         dataIndex: 'date', key: 'date',  width: 108 },
            { title: 'Reference',    key: 'reference',                 width: 200 },
            { title: 'Type',         key: 'type',                      width: 152 },
            { title: 'Debit (PKR)',  key: 'debit',  width: 140, align: 'right' },
            { title: 'Credit (PKR)', key: 'credit', width: 140, align: 'right' },
            { title: 'Balance',      key: 'running_balance',           align: 'right' },
        ];

        const fmt          = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate   = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const filterOption = (input, option) => option.children?.()[0]?.children?.toLowerCase().includes(input.toLowerCase());
        const typePill     = (t) => ({ 'Sale': 'ldr-pill-sale', 'Sales Return': 'ldr-pill-return', 'Payment Received': 'ldr-pill-pay' })[t] || '';
        const rowClass     = (r) => r._is_opening ? 'ldr-row-ob' : r.type === 'Payment Received' ? 'ldr-row-pay' : '';

        const openingBalance = computed(() => reportData.value.opening_balance ?? 0);
        const totalDebit     = computed(() => reportData.value.rows.reduce((s,r) => s + +r.debit, 0));
        const totalCredit    = computed(() => reportData.value.rows.reduce((s,r) => s + +r.credit, 0));
        const closingBalance = computed(() => openingBalance.value + totalDebit.value - totalCredit.value);

        const tableRows = computed(() => {
            const ob = openingBalance.value;
            return [{ date: reportData.value.date_from, reference:'—', type:'Opening Balance', debit: ob>0?ob:0, credit: ob<0?Math.abs(ob):0, running_balance: ob, items:[], _is_opening:true }, ...reportData.value.rows];
        });

        const loadCustomers = async () => {
            const res = await axiosAdmin.get('customers?limit=10000');
            customers.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
        };

        const load = async () => {
            loading.value = true; generated.value = true; expandedRowKeys.value = [];
            try {
                const res = await axiosAdmin.get('accounting/reports/customer-ledger', { params: { user_id: filters.value.user_id||undefined, date_from: filters.value.date_from?.format('YYYY-MM-DD'), date_to: filters.value.date_to?.format('YYYY-MM-DD') } });
                reportData.value = res.data;
            } catch { message.error('Failed to load ledger'); }
            finally { loading.value = false; }
        };

        const print = () => window.print();
        onMounted(loadCustomers);

        return {
            loading, generated, customers, filters, reportData, tableRows, columns, expandable, expandedRowKeys,
            fmt, formatDate, filterOption, typePill, rowClass, openingBalance, totalDebit, totalCredit, closingBalance,
            load, print, saleModalVisible, selectedSale, openSaleDetail,
        };
    }
});
</script>

<style scoped>
/* ══ SHARED BASE ══════════════════════════════════════════════════ */
.r { text-align: right; }

/* ══ HERO SECTION ════════════════════════════════════════════════ */
.ldr-hero {
    position: relative; overflow: hidden;
    border-radius: 0; margin-bottom: 0;
    padding: 24px 32px 0;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
}
.ldr-hero-glow { display: none; }
.ldr-glow-1 { display: none; }
.ldr-glow-2 { display: none; }

.ldr-hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }

.ldr-hero-title { display: flex; align-items: center; gap: 18px; }
.ldr-hero-icon {
    font-size: 24px; color: #fff;
    background: linear-gradient(135deg, #1d4ed8, #1e3a8a);
    border: none;
    border-radius: 14px;
    width: 54px; height: 54px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(29,78,216,.3);
}
.ldr-h1 { font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 4px; letter-spacing: -.4px; }
.ldr-sub { font-size: 13px; color: #64748b; margin: 0; }

.ldr-hero-kpi-row { display: flex; gap: 10px; flex-wrap: wrap; }
.ldr-kpi {
    padding: 10px 18px; border-radius: 12px;
    text-align: center; min-width: 130px;
}
.ldr-kpi-glass {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.ldr-kpi-due  { background: #fef2f2 !important; border-color: #fecaca !important; }
.ldr-kpi-ok   { background: #f0fdf4 !important; border-color: #bbf7d0 !important; }
.ldr-kpi-val  { font-size: 15px; font-weight: 800; color: #1e293b; }
.ldr-kpi-lbl  { font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }

/* ── Filters ── */
.ldr-filters {
    display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 18px 0 20px;
    margin: 0 -32px; padding-left: 32px; padding-right: 32px;
}
.ldr-filter-field { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 170px; }
.ldr-filter-sm { max-width: 180px; }
.ldr-filter-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .6px; display: flex; align-items: center; gap: 5px; }
.ldr-input { border-radius: 10px !important; }

.ldr-filter-actions { display: flex; gap: 8px; padding-bottom: 1px; }
.ldr-gen-btn {
    display: flex; align-items: center; gap: 7px;
    background: #1677ff; color: #fff;
    border: none; border-radius: 10px;
    font-size: 13.5px; font-weight: 700;
    padding: 0 22px; height: 36px; cursor: pointer;
    box-shadow: 0 4px 12px rgba(22,119,255,.3);
    transition: all .2s;
}
.ldr-gen-btn:hover:not(:disabled) { background: #0958d9; transform: translateY(-1px); }
.ldr-gen-btn:disabled { opacity: .6; cursor: not-allowed; }
.ldr-back-btn {
    display: flex; align-items: center; gap: 6px;
    background: #f1f5f9; color: #475569;
    border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: 13px; font-weight: 600;
    padding: 0 16px; height: 36px; cursor: pointer;
    transition: all .2s;
}
.ldr-back-btn:hover:not(:disabled) { background: #e2e8f0; }
.ldr-back-btn:disabled { opacity: .5; cursor: not-allowed; }

.ldr-print-btn { border-radius: 8px !important; }

/* ── Spinner ── */
.ldr-spinner {
    width: 14px; height: 14px; border: 2px solid #1e3a8a;
    border-top-color: transparent; border-radius: 50%;
    animation: spin .7s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ══ IDLE STATE ══════════════════════════════════════════════════ */
.ldr-idle { text-align: center; padding: 80px 20px; }
.ldr-idle-ring {
    width: 90px; height: 90px; border-radius: 50%;
    background: linear-gradient(135deg,#eff6ff,#dbeafe);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 0 0 12px rgba(59,130,246,.08);
}
.ldr-idle-ico { font-size: 40px; color: #3b82f6; }
.ldr-idle-h { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 8px; }
.ldr-idle-p { font-size: 14px; color: #94a3b8; margin: 0; }

/* ══ BODY ════════════════════════════════════════════════════════ */
.ldr-body { display: flex; flex-direction: column; gap: 16px; }

/* ── Stat strip ── */
.ldr-stat-strip { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; }
.ldr-stat {
    border-radius: 16px; padding: 18px 20px;
    display: flex; align-items: center; gap: 16px;
    border: 1px solid transparent;
    transition: transform .15s;
}
.ldr-stat:hover { transform: translateY(-2px); }
.ldr-stat-neutral { background: #f8fafc; border-color: #e2e8f0; }
.ldr-stat-blue    { background: linear-gradient(135deg,#eff6ff,#dbeafe); border-color: #bfdbfe; }
.ldr-stat-green   { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border-color: #bbf7d0; }
.ldr-stat-red     { background: linear-gradient(135deg,#fff1f2,#ffe4e6); border-color: #fecdd3; }
.ldr-stat-emerald { background: linear-gradient(135deg,#ecfdf5,#d1fae5); border-color: #a7f3d0; }

.ldr-stat-icon { font-size: 20px; border-radius: 10px; width: 44px; height: 44px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ldr-stat-neutral .ldr-stat-icon { background:#e2e8f0; color:#475569; }
.ldr-stat-blue    .ldr-stat-icon { background:#dbeafe; color:#1d4ed8; }
.ldr-stat-green   .ldr-stat-icon { background:#dcfce7; color:#16a34a; }
.ldr-stat-red     .ldr-stat-icon { background:#fecdd3; color:#dc2626; }
.ldr-stat-emerald .ldr-stat-icon { background:#a7f3d0; color:#059669; }

.ldr-stat-num  { font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1.1; }
.ldr-stat-lbl  { font-size: 11px; font-weight: 600; color: #64748b; margin: 3px 0 2px; text-transform: uppercase; letter-spacing: .4px; }
.ldr-stat-tag  { font-size: 11px; color: #94a3b8; }

/* ── Table card ── */
.ldr-table-card {
    background: #fff; border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 24px rgba(0,0,0,.06);
    overflow: hidden;
}
.ldr-table-header {
    padding: 16px 22px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    background: #fafbfc;
}
.ldr-table-title { display: flex; align-items: center; gap: 9px; font-size: 14px; font-weight: 700; color: #0f172a; }
.ldr-table-dot { width: 10px; height: 10px; border-radius: 50%; }
.ldr-dot-blue { background: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.2); }
.ldr-table-count { font-size: 11px; font-weight: 600; background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 20px; }
.ldr-table-meta { font-size: 12px; color: #94a3b8; }
.ldr-table-customer { color: #2563eb; font-weight: 600; }

/* ── Table cells ── */
.ldr-date { font-size: 12.5px; color: #64748b; font-variant-numeric: tabular-nums; }
.ldr-ob-ref { font-size: 12px; color: #94a3b8; font-style: italic; font-weight: 600; }
.ldr-ref-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: #1d4ed8; font-weight: 700; font-size: 13px;
    cursor: pointer; transition: color .15s;
}
.ldr-ref-link:hover { color: #1e40af; text-decoration: underline; }
.ldr-ref-icon { font-size: 11px; opacity: .7; }
.ldr-ref-badge {
    background: #2563eb; color: #fff;
    font-size: 9px; font-weight: 800;
    width: 17px; height: 17px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
}
.ldr-ref-plain { color: #475569; font-size: 13px; font-weight: 500; }

/* pills */
.ldr-pill { display: inline-flex; align-items: center; font-size: 11.5px; font-weight: 700; padding: 3px 11px; border-radius: 20px; gap: 4px; }
.ldr-pill-ob      { background: #f1f5f9; color: #64748b; }
.ldr-pill-sale    { background: #dbeafe; color: #1d4ed8; }
.ldr-pill-return  { background: #ffedd5; color: #c2410c; }
.ldr-pill-pay     { background: #dcfce7; color: #15803d; }

/* numbers */
.ldr-num    { font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; }
.ldr-num-dr { color: #1d4ed8; }
.ldr-num-cr { color: #15803d; }
.ldr-nil    { color: #cbd5e1; }
.ldr-bal    { font-size: 13.5px; font-weight: 800; font-variant-numeric: tabular-nums; }
.ldr-bal em { font-size: 9.5px; font-style: normal; font-weight: 700; margin-left: 3px; opacity: .75; }
.ldr-bal-dr { color: #dc2626; }
.ldr-bal-cr { color: #059669; }

/* row tints */
:deep(.ldr-row-ob  > td) { background: #fefce8 !important; }
:deep(.ldr-row-pay > td) { background: #f0fdf4 !important; }

.ldr-empty-tbl { padding: 40px; }

/* ── Closing Totals Card ── */
.ldr-closing-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 24px rgba(0,0,0,.07);
    padding: 24px 28px;
    overflow: hidden;
}
.ldr-closing-label {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 20px;
}
.ldr-closing-line { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); }
.ldr-closing-title {
    font-size: 11px; font-weight: 800; letter-spacing: 1.2px;
    text-transform: uppercase; color: #94a3b8;
    white-space: nowrap;
}
.ldr-closing-grid {
    display: flex; align-items: stretch; gap: 0;
    border-radius: 14px; overflow: hidden;
    border: 1px solid #e2e8f0;
}
.ldr-closing-block {
    flex: 1; display: flex; align-items: center; gap: 16px;
    padding: 22px 28px;
}
.ldr-closing-dr     { background: linear-gradient(135deg, #eff6ff, #dbeafe); }
.ldr-closing-cr     { background: linear-gradient(135deg, #f0fdf4, #dcfce7); }
.ldr-closing-bal    { flex: 1.2; }
.ldr-closing-bal-dr { background: linear-gradient(135deg, #fff1f2, #ffe4e6); }
.ldr-closing-bal-cr { background: linear-gradient(135deg, #ecfdf5, #d1fae5); }
.ldr-closing-divider { width: 1px; background: #e2e8f0; flex-shrink: 0; }
.ldr-closing-icon {
    font-size: 20px;
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.ldr-closing-dr   .ldr-closing-icon { background: #dbeafe; color: #1d4ed8; }
.ldr-closing-cr   .ldr-closing-icon { background: #dcfce7; color: #15803d; }
.ldr-closing-bal-dr .ldr-closing-icon { background: #fecdd3; color: #dc2626; }
.ldr-closing-bal-cr .ldr-closing-icon { background: #a7f3d0; color: #059669; }
.ldr-closing-val {
    font-size: 17px; font-weight: 800; color: #0f172a;
    font-variant-numeric: tabular-nums; line-height: 1.2;
}
.ldr-closing-val-lg { font-size: 20px; }
.ldr-closing-sub { font-size: 11.5px; color: #64748b; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.ldr-closing-badge {
    display: inline-block; font-size: 11px; font-weight: 700;
    padding: 2px 10px; border-radius: 20px; margin-top: 2px;
}
.ldr-badge-dr { background: #fee2e2; color: #b91c1c; }
.ldr-badge-cr { background: #dcfce7; color: #15803d; }

/* ── Expand panel ── */
.ldr-expand { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; }
.ldr-expand-hd { font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 12px; display:flex; align-items:center; gap:7px; }
.ldr-no-items { color: #94a3b8; font-size: 12.5px; padding: 8px; }

/* ── Items table ── */
.ldr-itm-tbl { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.ldr-itm-tbl thead tr { background: #f1f5f9; }
.ldr-itm-tbl th { padding: 8px 12px; font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; border-bottom: 2px solid #e2e8f0; text-align: left; }
.ldr-itm-tbl td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; }
.ldr-itm-tbl tbody tr:hover td { background: #f8fafc; }
.ldr-idx  { color: #cbd5e1; font-size: 11px; width: 28px; }
.ldr-pn   { font-weight: 600; color: #1e293b; }
.ldr-code { background: #f1f5f9; border-radius: 5px; padding: 1px 7px; font-size: 11px; color: #475569; font-family: monospace; }
.ldr-qty  { font-weight: 700; color: #2563eb; }
.ldr-sub  { font-weight: 700; color: #15803d; }
.ldr-foot td { background: #1e293b; color: #f1f5f9; font-weight: 700; padding: 10px 12px; }

/* ── Modal ── */
.ldr-modal-hd { display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 700; color: #0f172a; }
.ldr-modal-ico { font-size: 18px; color: #2563eb; background: #dbeafe; border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
.ldr-modal-body { }
.ldr-modal-meta { display: flex; gap: 0; border-bottom: 1px solid #f1f5f9; padding: 16px 24px; background: #f8fafc; }
.ldr-meta-blk { flex: 1; }
.ldr-meta-k { display: block; font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
.ldr-meta-v { font-size: 14px; color: #1e293b; font-weight: 500; }
.ldr-meta-ref   { font-weight: 700; }
.ldr-meta-total { color: #1d4ed8; font-weight: 800; font-size: 17px; }
.ldr-modal-tbl thead tr { background: #f8fafc !important; }
.ldr-modal-tbl th { padding: 10px 16px !important; }
.ldr-modal-tbl td { padding: 10px 16px !important; }

@media print {
    .ldr-hero { box-shadow: none; }
    .ldr-filters, .ldr-gen-btn, .ldr-back-btn, .ldr-print-btn { display: none !important; }
}
</style>
