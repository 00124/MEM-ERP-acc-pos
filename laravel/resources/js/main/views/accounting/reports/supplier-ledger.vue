<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Supplier Ledger" class="p-0">
                <template #extra>
                    <a-button class="sl-print-btn" @click="print"><PrinterOutlined /> Print</a-button>
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

    <!-- ── Hero filter bar ── -->
    <div class="sl-hero">
        <div class="sl-hero-glow sl-glow-1"></div>
        <div class="sl-hero-glow sl-glow-2"></div>

        <div class="sl-hero-top">
            <div class="sl-hero-title">
                <span class="sl-hero-icon"><ShopOutlined /></span>
                <div>
                    <h1 class="sl-h1">Supplier Ledger</h1>
                    <p class="sl-sub">Payables &amp; purchase history</p>
                </div>
            </div>
            <div v-if="generated" class="sl-hero-kpi-row">
                <div class="sl-kpi sl-kpi-glass">
                    <div class="sl-kpi-val">PKR {{ fmt(totalCredit) }}</div>
                    <div class="sl-kpi-lbl">Total Purchased</div>
                </div>
                <div class="sl-kpi sl-kpi-glass">
                    <div class="sl-kpi-val">PKR {{ fmt(totalDebit) }}</div>
                    <div class="sl-kpi-lbl">Total Paid</div>
                </div>
                <div class="sl-kpi sl-kpi-glass sl-kpi-accent" :class="closingBalance >= 0 ? 'sl-kpi-due' : 'sl-kpi-ok'">
                    <div class="sl-kpi-val">PKR {{ fmt(Math.abs(closingBalance)) }}</div>
                    <div class="sl-kpi-lbl">{{ closingBalance >= 0 ? 'Payable' : 'Advance Paid' }}</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="sl-filters">
            <div class="sl-filter-field">
                <label class="sl-filter-label"><ShopOutlined /> Supplier</label>
                <a-select v-model:value="filters.user_id" class="sl-input" show-search :filter-option="filterOption" placeholder="All Suppliers" allow-clear>
                    <a-select-option v-for="s in suppliers" :key="s.xid" :value="s.xid">{{ s.name }}</a-select-option>
                </a-select>
            </div>
            <div class="sl-filter-field sl-filter-sm">
                <label class="sl-filter-label"><CalendarOutlined /> From</label>
                <a-date-picker v-model:value="filters.date_from" class="sl-input" />
            </div>
            <div class="sl-filter-field sl-filter-sm">
                <label class="sl-filter-label"><CalendarOutlined /> To</label>
                <a-date-picker v-model:value="filters.date_to" class="sl-input" />
            </div>
            <div class="sl-filter-actions">
                <button class="sl-gen-btn" @click="load" :disabled="loading">
                    <span v-if="loading" class="sl-spinner"></span>
                    <SearchOutlined v-else />
                    {{ loading ? 'Loading…' : 'Generate' }}
                </button>
            </div>
        </div>
    </div>

    <!-- ── Empty state ── -->
    <div v-if="!generated && !loading" class="sl-idle">
        <div class="sl-idle-ring">
            <BarChartOutlined class="sl-idle-ico" />
        </div>
        <h3 class="sl-idle-h">Ready to generate</h3>
        <p class="sl-idle-p">Choose a supplier and date range above, then click <b>Generate</b>.</p>
    </div>

    <a-spin :spinning="loading">
        <div v-if="generated" id="printable-area" class="sl-body">

            <!-- Estimated amounts warning -->
            <div v-if="hasZeroTotalGrns" class="sl-warn-bar">
                <span class="sl-warn-ico"><WarningOutlined /></span>
                <span>Some GRN entries had <b>PKR 0</b> unit prices — amounts shown are estimated from product purchase prices. Update GRN unit prices for accurate payable figures.</span>
            </div>

            <!-- Summary strip -->
            <div class="sl-stat-strip">
                <div class="sl-stat sl-stat-neutral">
                    <div class="sl-stat-icon"><DollarOutlined /></div>
                    <div class="sl-stat-info">
                        <div class="sl-stat-num">PKR {{ fmt(Math.abs(openingBalance)) }}</div>
                        <div class="sl-stat-lbl">Opening Balance</div>
                        <div class="sl-stat-tag">{{ openingBalance >= 0 ? 'Payable b/f' : 'Advance b/f' }}</div>
                    </div>
                </div>
                <div class="sl-stat sl-stat-purple">
                    <div class="sl-stat-icon"><ShoppingCartOutlined /></div>
                    <div class="sl-stat-info">
                        <div class="sl-stat-num">PKR {{ fmt(totalCredit) }}</div>
                        <div class="sl-stat-lbl">Total Purchases</div>
                        <div class="sl-stat-tag">{{ reportData.rows.filter(r=>r.type==='GRN'||r.type==='Purchase').length }} GRN/order(s)</div>
                    </div>
                </div>
                <div class="sl-stat sl-stat-green">
                    <div class="sl-stat-icon"><CheckCircleOutlined /></div>
                    <div class="sl-stat-info">
                        <div class="sl-stat-num">PKR {{ fmt(totalDebit) }}</div>
                        <div class="sl-stat-lbl">Total Paid</div>
                        <div class="sl-stat-tag">{{ reportData.rows.filter(r=>r.type==='Payment Made').length }} payment(s)</div>
                    </div>
                </div>
                <div class="sl-stat" :class="closingBalance >= 0 ? 'sl-stat-red' : 'sl-stat-emerald'">
                    <div class="sl-stat-icon"><WalletOutlined /></div>
                    <div class="sl-stat-info">
                        <div class="sl-stat-num">PKR {{ fmt(Math.abs(closingBalance)) }}</div>
                        <div class="sl-stat-lbl">{{ closingBalance >= 0 ? 'Payable' : 'Advance' }}</div>
                        <div class="sl-stat-tag">Net closing</div>
                    </div>
                </div>
            </div>

            <!-- Table card -->
            <div class="sl-table-card">
                <div class="sl-table-header">
                    <div class="sl-table-title">
                        <span class="sl-table-dot"></span>
                        Transaction Ledger
                        <span class="sl-table-count">{{ tableRows.length }} rows</span>
                    </div>
                    <div class="sl-table-meta">
                        {{ formatDate(reportData.date_from) }} — {{ formatDate(reportData.date_to) }}
                        <span v-if="reportData.supplier" class="sl-table-sup"> · {{ reportData.supplier.name }}</span>
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
                    class="sl-table"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'date'">
                            <span class="sl-date">{{ record.date }}</span>
                        </template>
                        <template v-if="column.key === 'reference'">
                            <span v-if="record._is_opening" class="sl-ob-ref">— Opening Balance —</span>
                            <span v-else class="sl-ref-wrap">
                                <span class="sl-ref-ico"><FileTextOutlined /></span>
                                <span class="sl-ref-text">{{ record.reference }}</span>
                                <span class="sl-ref-badge" v-if="record.items?.length">{{ record.items.length }}</span>
                                <a-tooltip v-if="record.recorded_amt==0 && +record.credit>0" title="Estimated from product purchase prices">
                                    <span class="sl-est-dot">~</span>
                                </a-tooltip>
                            </span>
                        </template>
                        <template v-if="column.key === 'type'">
                            <span v-if="record._is_opening" class="sl-pill sl-pill-ob">b/f</span>
                            <span v-else class="sl-pill" :class="typePill(record.type)">{{ record.type }}</span>
                        </template>
                        <template v-if="column.key === 'debit'">
                            <span v-if="+record.debit" class="sl-num sl-num-dr">{{ fmt(record.debit) }}</span>
                            <span v-else class="sl-nil">—</span>
                        </template>
                        <template v-if="column.key === 'credit'">
                            <span v-if="+record.credit" class="sl-num sl-num-cr">
                                {{ fmt(record.credit) }}
                                <span v-if="record.recorded_amt==0 && +record.credit>0" class="sl-est-tag">est.</span>
                            </span>
                            <span v-else class="sl-nil">—</span>
                        </template>
                        <template v-if="column.key === 'running_balance'">
                            <span class="sl-bal" :class="record.running_balance >= 0 ? 'sl-bal-pay' : 'sl-bal-adv'">
                                {{ fmt(Math.abs(record.running_balance)) }}
                                <em>{{ record.running_balance >= 0 ? 'Pay' : 'Adv' }}</em>
                            </span>
                        </template>
                    </template>

                    <template #expandedRowRender="{ record }">
                        <div v-if="record.items?.length" class="sl-expand">
                            <div class="sl-expand-hd">
                                <ShoppingCartOutlined /> {{ record.reference }} &mdash; Items Received
                                <span v-if="record.recorded_amt==0 && +record.credit>0" class="sl-expand-est">⚠ Estimated</span>
                            </div>
                            <div v-if="record.recorded_amt==0 && +record.credit>0" class="sl-est-notice">
                                GRN had no unit prices. Amount (PKR {{ fmt(record.credit) }}) estimated from product purchase prices — update GRN to correct.
                            </div>
                            <table class="sl-itm-tbl">
                                <thead><tr><th>#</th><th>Product</th><th>Code</th><th class="r">Qty</th><th class="r">Unit Price</th><th class="r">Subtotal</th></tr></thead>
                                <tbody>
                                    <tr v-for="(it,i) in record.items" :key="i">
                                        <td class="sl-idx">{{ i+1 }}</td>
                                        <td class="sl-pn">{{ it.name }}</td>
                                        <td><code v-if="it.item_code" class="sl-code">{{ it.item_code }}</code></td>
                                        <td class="r sl-qty">{{ it.qty }}</td>
                                        <td class="r" :class="it.unit_price==0?'sl-price-miss':''">
                                            {{ it.unit_price>0?'PKR '+fmt(it.unit_price):'—' }}
                                        </td>
                                        <td class="r sl-sub">{{ it.subtotal>0?'PKR '+fmt(it.subtotal):'—' }}</td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="+record.credit>0">
                                    <tr class="sl-foot"><td colspan="5" class="r">{{ record.recorded_amt==0?'Estimated Total':'Total' }}</td><td class="r">PKR {{ fmt(record.credit) }}</td></tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-else class="sl-no-items">No item details available.</div>
                    </template>

                </a-table>
                <a-empty v-if="!reportData.rows.length" description="No transactions in this period" class="sl-empty-tbl" />
            </div>

            <!-- ── Closing Totals Card ── -->
            <div class="sl-closing-card">
                <div class="sl-closing-label">
                    <span class="sl-closing-line"></span>
                    <span class="sl-closing-title">Closing Totals</span>
                    <span class="sl-closing-line"></span>
                </div>
                <div class="sl-closing-grid">
                    <div class="sl-closing-block sl-closing-dr">
                        <div class="sl-closing-icon"><ArrowDownOutlined /></div>
                        <div class="sl-closing-info">
                            <div class="sl-closing-val">PKR {{ fmt(openingBalance < 0 ? Math.abs(openingBalance) + totalDebit : totalDebit) }}</div>
                            <div class="sl-closing-sub">Total Debit</div>
                        </div>
                    </div>
                    <div class="sl-closing-divider"></div>
                    <div class="sl-closing-block sl-closing-cr">
                        <div class="sl-closing-icon"><ArrowUpOutlined /></div>
                        <div class="sl-closing-info">
                            <div class="sl-closing-val">PKR {{ fmt(openingBalance > 0 ? openingBalance + totalCredit : totalCredit) }}</div>
                            <div class="sl-closing-sub">Total Credit</div>
                        </div>
                    </div>
                    <div class="sl-closing-divider"></div>
                    <div class="sl-closing-block sl-closing-bal" :class="closingBalance >= 0 ? 'sl-closing-bal-pay' : 'sl-closing-bal-adv'">
                        <div class="sl-closing-icon">
                            <RiseOutlined v-if="closingBalance >= 0" />
                            <FallOutlined v-else />
                        </div>
                        <div class="sl-closing-info">
                            <div class="sl-closing-val sl-closing-val-lg">PKR {{ fmt(Math.abs(closingBalance)) }}</div>
                            <div class="sl-closing-sub">
                                <span class="sl-closing-badge" :class="closingBalance >= 0 ? 'sl-badge-pay' : 'sl-badge-adv'">
                                    {{ closingBalance >= 0 ? 'Payable' : 'Advance Paid' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </a-spin>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import {
    PrinterOutlined, SearchOutlined, ShoppingCartOutlined,
    FileTextOutlined, ShopOutlined, CalendarOutlined, DollarOutlined,
    CheckCircleOutlined, WalletOutlined, WarningOutlined, BarChartOutlined,
    ArrowUpOutlined, ArrowDownOutlined, RiseOutlined, FallOutlined,
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: {
        AdminPageHeader, PrinterOutlined, SearchOutlined, ShoppingCartOutlined,
        FileTextOutlined, ShopOutlined, CalendarOutlined, DollarOutlined,
        CheckCircleOutlined, WalletOutlined, WarningOutlined, BarChartOutlined,
        ArrowUpOutlined, ArrowDownOutlined, RiseOutlined, FallOutlined,
    },
    setup() {
        const axiosAdmin  = window.axiosAdmin;
        const loading     = ref(false);
        const generated   = ref(false);
        const suppliers   = ref([]);
        const filters     = ref({ user_id: null, date_from: dayjs().startOf('year'), date_to: dayjs() });
        const reportData  = ref({ rows: [], opening_balance: 0, supplier: null, date_from: '', date_to: '' });

        const expandedRowKeys = ref([]);
        const expandable = computed(() => ({
            expandedRowKeys: expandedRowKeys.value,
            onExpand: (expanded, record) => {
                const key = tableRows.value.indexOf(record);
                expandedRowKeys.value = expanded ? [...expandedRowKeys.value, key] : expandedRowKeys.value.filter(k => k !== key);
            },
            rowExpandable: (r) => (r.type === 'Purchase' || r.type === 'GRN') && r.items?.length > 0,
        }));

        const columns = [
            { title: 'Date',         dataIndex: 'date', key: 'date',  width: 108 },
            { title: 'Reference',    key: 'reference',                 width: 210 },
            { title: 'Type',         key: 'type',                      width: 155 },
            { title: 'Debit (PKR)',  key: 'debit',  width: 140, align: 'right' },
            { title: 'Credit (PKR)', key: 'credit', width: 160, align: 'right' },
            { title: 'Balance',      key: 'running_balance',           align: 'right' },
        ];

        const fmt          = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate   = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const filterOption = (input, option) => option.children?.()[0]?.children?.toLowerCase().includes(input.toLowerCase());
        const typePill     = (t) => ({ 'Purchase': 'sl-pill-purchase', 'GRN': 'sl-pill-grn', 'Purchase Return': 'sl-pill-return', 'Payment Made': 'sl-pill-pay' })[t] || '';
        const rowClass     = (r) => r._is_opening ? 'sl-row-ob' : r.type === 'Payment Made' ? 'sl-row-pay' : (r.type==='GRN'||r.type==='Purchase') ? 'sl-row-pur' : '';

        const openingBalance  = computed(() => reportData.value.opening_balance ?? 0);
        const totalDebit      = computed(() => reportData.value.rows.reduce((s,r) => s + +r.debit,  0));
        const totalCredit     = computed(() => reportData.value.rows.reduce((s,r) => s + +r.credit, 0));
        const closingBalance  = computed(() => openingBalance.value + totalCredit.value - totalDebit.value);
        const hasZeroTotalGrns = computed(() => reportData.value.rows.some(r => (r.type==='GRN'||r.type==='Purchase') && r.recorded_amt==0 && +r.credit>0));

        const tableRows = computed(() => {
            const ob = openingBalance.value;
            return [{ date: reportData.value.date_from, reference:'—', type:'Opening Balance', debit: ob<0?Math.abs(ob):0, credit: ob>0?ob:0, running_balance: ob, items:[], _is_opening:true }, ...reportData.value.rows];
        });

        const loadSuppliers = async () => {
            const res = await axiosAdmin.get('suppliers?limit=10000');
            suppliers.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
        };

        const load = async () => {
            loading.value = true; generated.value = true; expandedRowKeys.value = [];
            try {
                const res = await axiosAdmin.get('accounting/reports/supplier-ledger', { params: { user_id: filters.value.user_id||undefined, date_from: filters.value.date_from?.format('YYYY-MM-DD'), date_to: filters.value.date_to?.format('YYYY-MM-DD') } });
                reportData.value = res.data;
            } catch { message.error('Failed to load ledger'); }
            finally { loading.value = false; }
        };

        const print = () => window.print();
        onMounted(loadSuppliers);

        return {
            loading, generated, suppliers, filters, reportData, tableRows, columns, expandable, expandedRowKeys, hasZeroTotalGrns,
            fmt, formatDate, filterOption, typePill, rowClass, openingBalance, totalDebit, totalCredit, closingBalance,
            load, print,
        };
    }
});
</script>

<style scoped>
.r { text-align: right; }

/* ══ HERO ════════════════════════════════════════════════════ */
.sl-hero {
    position: relative; overflow: hidden;
    border-radius: 0; margin-bottom: 0;
    padding: 24px 32px 0;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
}
.sl-hero-glow { display:none; }
.sl-glow-1 { display:none; }
.sl-glow-2 { display:none; }

.sl-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
.sl-hero-title { display:flex; align-items:center; gap:18px; }
.sl-hero-icon {
    font-size:24px; color:#fff;
    background: linear-gradient(135deg, #7c3aed, #4c1d95);
    border:none;
    border-radius:14px;
    width:54px; height:54px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
    box-shadow: 0 4px 12px rgba(124,58,237,.3);
}
.sl-h1   { font-size:22px; font-weight:800; color:#1e293b; margin:0 0 4px; letter-spacing:-.4px; }
.sl-sub  { font-size:13px; color:#64748b; margin:0; }

.sl-hero-kpi-row { display:flex; gap:10px; flex-wrap:wrap; }
.sl-kpi { padding:10px 18px; border-radius:12px; text-align:center; min-width:130px; }
.sl-kpi-glass { background:#f8fafc; border:1px solid #e2e8f0; }
.sl-kpi-due { background:#fef2f2!important; border-color:#fecaca!important; }
.sl-kpi-ok  { background:#f0fdf4!important; border-color:#bbf7d0!important; }
.sl-kpi-val { font-size:15px; font-weight:800; color:#1e293b; }
.sl-kpi-lbl { font-size:10px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }

.sl-filters {
    display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;
    background:#f8fafc;
    border-top:1px solid #e2e8f0;
    padding:18px 32px 20px; margin:0 -32px;
}
.sl-filter-field { display:flex; flex-direction:column; gap:5px; flex:1; min-width:170px; }
.sl-filter-sm { max-width:180px; }
.sl-filter-label { font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.6px; display:flex; align-items:center; gap:5px; }
.sl-input { border-radius:10px!important; }
.sl-filter-actions { display:flex; gap:8px; padding-bottom:1px; }
.sl-gen-btn {
    display:flex; align-items:center; gap:7px;
    background:#1677ff; color:#fff;
    border:none; border-radius:10px;
    font-size:13.5px; font-weight:700;
    padding:0 22px; height:36px; cursor:pointer;
    box-shadow:0 4px 12px rgba(22,119,255,.3);
    transition:all .2s;
}
.sl-gen-btn:hover:not(:disabled) { background:#0958d9; transform:translateY(-1px); }
.sl-gen-btn:disabled { opacity:.6; cursor:not-allowed; }
.sl-back-btn {
    display:flex; align-items:center; gap:6px;
    background:#f1f5f9; color:#475569;
    border:1px solid #e2e8f0; border-radius:10px;
    font-size:13px; font-weight:600;
    padding:0 16px; height:36px; cursor:pointer;
    transition:all .2s;
}
.sl-back-btn:hover:not(:disabled) { background:#e2e8f0; }
.sl-back-btn:disabled { opacity:.5; cursor:not-allowed; }
.sl-print-btn { border-radius:8px!important; }

.sl-spinner { width:14px; height:14px; border:2px solid #4c1d95; border-top-color:transparent; border-radius:50%; animation:spin .7s linear infinite; display:inline-block; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ══ IDLE ═════════════════════════════════════════════════════ */
.sl-idle { text-align:center; padding:80px 20px; }
.sl-idle-ring {
    width:90px; height:90px; border-radius:50%;
    background:linear-gradient(135deg,#f5f3ff,#ede9fe);
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 20px;
    box-shadow:0 0 0 12px rgba(124,58,237,.08);
}
.sl-idle-ico { font-size:40px; color:#7c3aed; }
.sl-idle-h { font-size:18px; font-weight:700; color:#1e293b; margin:0 0 8px; }
.sl-idle-p { font-size:14px; color:#94a3b8; margin:0; }

/* ══ BODY ═════════════════════════════════════════════════════ */
.sl-body { display:flex; flex-direction:column; gap:16px; }

/* Warning */
.sl-warn-bar { background:#fffbeb; border:1px solid #fcd34d; border-left:4px solid #f59e0b; border-radius:12px; padding:12px 18px; font-size:13px; color:#92400e; display:flex; align-items:flex-start; gap:10px; line-height:1.5; }
.sl-warn-ico { font-size:16px; color:#f59e0b; flex-shrink:0; margin-top:1px; }

/* Stat strip */
.sl-stat-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.sl-stat { border-radius:16px; padding:18px 20px; display:flex; align-items:center; gap:16px; border:1px solid transparent; transition:transform .15s; }
.sl-stat:hover { transform:translateY(-2px); }
.sl-stat-neutral { background:#f8fafc; border-color:#e2e8f0; }
.sl-stat-purple  { background:linear-gradient(135deg,#f5f3ff,#ede9fe); border-color:#ddd6fe; }
.sl-stat-green   { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border-color:#bbf7d0; }
.sl-stat-red     { background:linear-gradient(135deg,#fff1f2,#ffe4e6); border-color:#fecdd3; }
.sl-stat-emerald { background:linear-gradient(135deg,#ecfdf5,#d1fae5); border-color:#a7f3d0; }

.sl-stat-icon { font-size:20px; border-radius:10px; width:44px; height:44px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.sl-stat-neutral .sl-stat-icon { background:#e2e8f0; color:#475569; }
.sl-stat-purple  .sl-stat-icon { background:#ddd6fe; color:#7c3aed; }
.sl-stat-green   .sl-stat-icon { background:#dcfce7; color:#16a34a; }
.sl-stat-red     .sl-stat-icon { background:#fecdd3; color:#dc2626; }
.sl-stat-emerald .sl-stat-icon { background:#a7f3d0; color:#059669; }

.sl-stat-num { font-size:16px; font-weight:800; color:#0f172a; line-height:1.1; }
.sl-stat-lbl { font-size:11px; font-weight:600; color:#64748b; margin:3px 0 2px; text-transform:uppercase; letter-spacing:.4px; }
.sl-stat-tag { font-size:11px; color:#94a3b8; }
.sl-stat-purple .sl-stat-num { color:#7c3aed; }
.sl-stat-green  .sl-stat-num { color:#16a34a; }
.sl-stat-red    .sl-stat-num { color:#dc2626; }
.sl-stat-emerald .sl-stat-num { color:#059669; }

/* Table card */
.sl-table-card { background:#fff; border-radius:18px; border:1px solid #e2e8f0; box-shadow:0 4px 24px rgba(0,0,0,.06); overflow:hidden; }
.sl-table-header { padding:16px 22px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; background:#fafbfc; }
.sl-table-title { display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; color:#0f172a; }
.sl-table-dot { width:10px; height:10px; border-radius:50%; background:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.2); }
.sl-table-count { font-size:11px; font-weight:600; background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:20px; }
.sl-table-meta { font-size:12px; color:#94a3b8; }
.sl-table-sup { color:#7c3aed; font-weight:600; }

/* Cells */
.sl-date { font-size:12.5px; color:#64748b; font-variant-numeric:tabular-nums; }
.sl-ob-ref { font-size:12px; color:#94a3b8; font-style:italic; font-weight:600; }
.sl-ref-wrap { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#334155; }
.sl-ref-ico { font-size:11px; color:#7c3aed; opacity:.8; }
.sl-ref-text { font-weight:600; }
.sl-ref-badge { background:#7c3aed; color:#fff; font-size:9px; font-weight:800; width:17px; height:17px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; }
.sl-est-dot { background:#fef3c7; border:1px solid #fcd34d; border-radius:4px; font-size:10px; font-weight:800; color:#92400e; padding:0 5px; }

/* pills */
.sl-pill { display:inline-flex; align-items:center; font-size:11.5px; font-weight:700; padding:3px 11px; border-radius:20px; }
.sl-pill-ob       { background:#f1f5f9; color:#64748b; }
.sl-pill-purchase { background:#ede9fe; color:#6d28d9; }
.sl-pill-grn      { background:#f5f3ff; color:#7c3aed; }
.sl-pill-return   { background:#ffedd5; color:#c2410c; }
.sl-pill-pay      { background:#dcfce7; color:#15803d; }

/* numbers */
.sl-num    { font-size:13px; font-weight:700; font-variant-numeric:tabular-nums; }
.sl-num-dr { color:#1d4ed8; }
.sl-num-cr { color:#7c3aed; display:inline-flex; align-items:center; gap:5px; }
.sl-nil    { color:#cbd5e1; }
.sl-est-tag { background:#fef3c7; border:1px solid #fde68a; border-radius:4px; font-size:9px; font-weight:700; color:#92400e; padding:0 4px; }
.sl-bal    { font-size:13.5px; font-weight:800; font-variant-numeric:tabular-nums; }
.sl-bal em { font-size:9.5px; font-style:normal; font-weight:700; margin-left:3px; opacity:.75; }
.sl-bal-pay { color:#dc2626; }
.sl-bal-adv { color:#059669; }

/* row tints */
:deep(.sl-row-ob  > td) { background:#fefce8!important; }
:deep(.sl-row-pay > td) { background:#f0fdf4!important; }
:deep(.sl-row-pur > td) { background:#faf5ff!important; }

.sl-empty-tbl { padding:40px; }

/* ── Closing Totals Card ── */
.sl-closing-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 24px rgba(0,0,0,.07);
    padding: 24px 28px;
    overflow: hidden;
}
.sl-closing-label {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 20px;
}
.sl-closing-line { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); }
.sl-closing-title {
    font-size: 11px; font-weight: 800; letter-spacing: 1.2px;
    text-transform: uppercase; color: #94a3b8; white-space: nowrap;
}
.sl-closing-grid {
    display: flex; align-items: stretch; gap: 0;
    border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0;
}
.sl-closing-block {
    flex: 1; display: flex; align-items: center; gap: 16px; padding: 22px 28px;
}
.sl-closing-dr      { background: linear-gradient(135deg, #eff6ff, #dbeafe); }
.sl-closing-cr      { background: linear-gradient(135deg, #f5f3ff, #ede9fe); }
.sl-closing-bal     { flex: 1.2; }
.sl-closing-bal-pay { background: linear-gradient(135deg, #fff1f2, #ffe4e6); }
.sl-closing-bal-adv { background: linear-gradient(135deg, #ecfdf5, #d1fae5); }
.sl-closing-divider { width: 1px; background: #e2e8f0; flex-shrink: 0; }
.sl-closing-icon {
    font-size: 20px; width: 46px; height: 46px;
    border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.sl-closing-dr      .sl-closing-icon { background: #dbeafe; color: #1d4ed8; }
.sl-closing-cr      .sl-closing-icon { background: #ede9fe; color: #7c3aed; }
.sl-closing-bal-pay .sl-closing-icon { background: #fecdd3; color: #dc2626; }
.sl-closing-bal-adv .sl-closing-icon { background: #a7f3d0; color: #059669; }
.sl-closing-val {
    font-size: 17px; font-weight: 800; color: #0f172a;
    font-variant-numeric: tabular-nums; line-height: 1.2;
}
.sl-closing-val-lg { font-size: 20px; }
.sl-closing-sub { font-size: 11.5px; color: #64748b; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.sl-closing-badge {
    display: inline-block; font-size: 11px; font-weight: 700;
    padding: 2px 10px; border-radius: 20px; margin-top: 2px;
}
.sl-badge-pay { background: #fee2e2; color: #b91c1c; }
.sl-badge-adv { background: #dcfce7; color: #15803d; }

/* Expand panel */
.sl-expand { background:#faf5ff; border:1px solid #ede9fe; border-radius:10px; padding:14px 18px; }
.sl-expand-hd { font-size:12px; font-weight:700; color:#1e293b; margin-bottom:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.sl-expand-est { background:#fef3c7; border:1px solid #fcd34d; border-radius:4px; font-size:10px; font-weight:700; color:#92400e; padding:1px 7px; }
.sl-est-notice { font-size:12px; color:#92400e; background:#fffbeb; border:1px solid #fde68a; border-radius:6px; padding:8px 12px; margin-bottom:10px; line-height:1.5; }
.sl-no-items { color:#94a3b8; font-size:12.5px; padding:8px; }

/* Items table */
.sl-itm-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.sl-itm-tbl thead tr { background:#ede9fe; }
.sl-itm-tbl th { padding:8px 12px; font-size:10.5px; font-weight:700; color:#6d28d9; text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid #ddd6fe; text-align:left; }
.sl-itm-tbl td { padding:9px 12px; border-bottom:1px solid #f5f3ff; }
.sl-itm-tbl tbody tr:hover td { background:#f5f3ff; }
.sl-foot td { background:#1e1b4b; color:#f1f5f9; font-weight:700; padding:10px 12px; }
.sl-idx  { color:#cbd5e1; font-size:11px; width:28px; }
.sl-pn   { font-weight:600; color:#1e293b; }
.sl-code { background:#ede9fe; border-radius:5px; padding:1px 7px; font-size:11px; color:#6d28d9; font-family:monospace; }
.sl-qty  { font-weight:700; color:#7c3aed; }
.sl-sub  { font-weight:700; color:#dc2626; }
.sl-price-miss { color:#f59e0b!important; }

@media print {
    .sl-hero { box-shadow:none; }
    .sl-filters,.sl-gen-btn,.sl-back-btn,.sl-print-btn { display:none!important; }
}
</style>
