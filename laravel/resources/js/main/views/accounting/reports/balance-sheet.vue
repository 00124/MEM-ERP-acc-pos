<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Balance Sheet" class="p-0">
                <template #extra>
                    <a-button @click="print" style="border-radius:8px;font-weight:600"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Balance Sheet</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="bs-root">
        <!-- ── Filter Bar ─────────────────────────────────── -->
        <div class="bs-filter-bar no-print">
            <div class="bs-filter-row">
                <div class="bs-filter-group">
                    <label class="bs-filter-label">As of Date</label>
                    <a-date-picker v-model:value="filters.as_of" class="bs-picker" />
                </div>
                <a-button type="primary" :loading="loading" @click="load" class="bs-gen-btn">
                    <SearchOutlined /> Generate Report
                </a-button>
                <div class="bs-quick-set">
                    <span class="bs-quick-label">Quick:</span>
                    <button class="bs-qbtn" @click="setQuick('today')">Today</button>
                    <button class="bs-qbtn" @click="setQuick('month')">End of Month</button>
                    <button class="bs-qbtn" @click="setQuick('quarter')">End of Quarter</button>
                    <button class="bs-qbtn" @click="setQuick('year')">End of Year</button>
                </div>
            </div>
        </div>

        <a-spin :spinning="loading">
            <div id="printable-area">

                <!-- ── Hero Banner ───────────────────────────────────── -->
                <div class="bs-hero">
                    <div class="bs-hero-left">
                        <div class="bs-hero-eyebrow">Financial Report</div>
                        <h1 class="bs-hero-title">Balance Sheet</h1>
                        <p class="bs-hero-period"><CalendarOutlined /> As of {{ formatDate(data.as_of) }}</p>
                        <div class="bs-hero-equation">
                            <span class="bs-eq-chip bs-eq-assets">Assets {{ fmt(data.total_assets) }}</span>
                            <span class="bs-eq-op">=</span>
                            <span class="bs-eq-chip bs-eq-liab">Liabilities {{ fmt(data.total_liabilities) }}</span>
                            <span class="bs-eq-op">+</span>
                            <span class="bs-eq-chip bs-eq-equity">Equity {{ fmt(data.total_equity) }}</span>
                        </div>
                    </div>
                    <div class="bs-hero-right">
                        <div class="bs-balance-badge" :class="isBalanced ? 'bs-badge-ok' : 'bs-badge-err'">
                            <div class="bs-badge-icon"><CheckCircleOutlined v-if="isBalanced" /><WarningOutlined v-else /></div>
                            <div class="bs-badge-label">Balance Status</div>
                            <div class="bs-badge-status">{{ isBalanced ? 'Balanced' : 'Unbalanced' }}</div>
                            <div class="bs-badge-eq">A = L + E</div>
                        </div>
                    </div>
                </div>

                <!-- ── KPI Cards ─────────────────────────────────────── -->
                <div class="bs-kpi-grid no-print">
                    <div class="bs-kpi-card bs-kpi-assets">
                        <div class="bs-kpi-icon-wrap"><BankOutlined /></div>
                        <div class="bs-kpi-body">
                            <div class="bs-kpi-label">Total Assets</div>
                            <div class="bs-kpi-value">{{ fmt(data.total_assets) }}</div>
                            <div class="bs-kpi-sub">{{ assetRows.length }} asset accounts</div>
                        </div>
                        <div class="bs-kpi-bar bs-kpi-bar-assets"></div>
                    </div>
                    <div class="bs-kpi-card bs-kpi-liab">
                        <div class="bs-kpi-icon-wrap"><CreditCardOutlined /></div>
                        <div class="bs-kpi-body">
                            <div class="bs-kpi-label">Total Liabilities</div>
                            <div class="bs-kpi-value">{{ fmt(data.total_liabilities) }}</div>
                            <div class="bs-kpi-sub">{{ liabilityRows.length }} liability accounts</div>
                        </div>
                        <div class="bs-kpi-bar bs-kpi-bar-liab"></div>
                    </div>
                    <div class="bs-kpi-card bs-kpi-equity">
                        <div class="bs-kpi-icon-wrap"><PieChartOutlined /></div>
                        <div class="bs-kpi-body">
                            <div class="bs-kpi-label">Total Equity</div>
                            <div class="bs-kpi-value">{{ fmt(data.total_equity) }}</div>
                            <div class="bs-kpi-sub">{{ equityRows.length }} equity accounts</div>
                        </div>
                        <div class="bs-kpi-bar bs-kpi-bar-equity"></div>
                    </div>
                    <div class="bs-kpi-card" :class="isBalanced ? 'bs-kpi-ok' : 'bs-kpi-err'">
                        <div class="bs-kpi-icon-wrap"><CheckCircleOutlined v-if="isBalanced" /><WarningOutlined v-else /></div>
                        <div class="bs-kpi-body">
                            <div class="bs-kpi-label">Liabilities + Equity</div>
                            <div class="bs-kpi-value">{{ fmt(+data.total_liabilities + +data.total_equity) }}</div>
                            <div class="bs-kpi-sub">{{ isBalanced ? '✓ Sheet is balanced' : '⚠ Sheet is NOT balanced' }}</div>
                        </div>
                        <div class="bs-kpi-bar" :class="isBalanced ? 'bs-kpi-bar-ok' : 'bs-kpi-bar-err'"></div>
                    </div>
                </div>

                <!-- ── Asset Allocation Bar ───────────────────────────── -->
                <div class="bs-alloc-card no-print">
                    <div class="bs-alloc-title">Asset Allocation</div>
                    <div class="bs-alloc-track">
                        <div class="bs-alloc-fill bs-alloc-liab" :style="{ width: assetPercent(data.total_liabilities) + '%' }" :title="'Liabilities: ' + fmt(data.total_liabilities)"></div>
                        <div class="bs-alloc-fill bs-alloc-equity" :style="{ width: assetPercent(data.total_equity) + '%' }" :title="'Equity: ' + fmt(data.total_equity)"></div>
                    </div>
                    <div class="bs-alloc-legend">
                        <span class="bs-leg-item"><span class="bs-leg-dot bs-leg-dot-liab"></span> Liabilities {{ assetPercent(data.total_liabilities) }}%</span>
                        <span class="bs-leg-item"><span class="bs-leg-dot bs-leg-dot-equity"></span> Equity {{ assetPercent(data.total_equity) }}%</span>
                    </div>
                </div>

                <!-- ── Two-Column Detail Layout ───────────────────────── -->
                <div class="bs-detail-grid">

                    <!-- LEFT — Assets -->
                    <div class="bs-table-card">
                        <div class="bs-tc-header bs-tc-header-assets">
                            <div class="bs-tc-title"><BankOutlined /> Assets</div>
                            <div class="bs-tc-total">{{ fmt(data.total_assets) }}</div>
                        </div>
                        <table class="bs-tbl">
                            <thead><tr><th>Code</th><th>Account Name</th><th class="bs-tbl-right">Balance (PKR)</th></tr></thead>
                            <tbody>
                                <tr v-if="assetRows.length === 0"><td colspan="3" class="bs-tbl-empty">No asset accounts found</td></tr>
                                <tr v-for="r in assetRows" :key="r.account_code">
                                    <td><span class="bs-code">{{ r.account_code }}</span></td>
                                    <td>{{ r.account_name }}</td>
                                    <td class="bs-tbl-right bs-amt-blue">{{ fmt(r.balance) }}</td>
                                </tr>
                            </tbody>
                            <tfoot><tr class="bs-tbl-foot bs-tbl-foot-assets"><td colspan="2">Total Assets</td><td class="bs-tbl-right">{{ fmt(data.total_assets) }}</td></tr></tfoot>
                        </table>
                    </div>

                    <!-- RIGHT — Liabilities + Equity -->
                    <div class="bs-right-col">

                        <!-- Liabilities -->
                        <div class="bs-table-card">
                            <div class="bs-tc-header bs-tc-header-liab">
                                <div class="bs-tc-title"><CreditCardOutlined /> Liabilities</div>
                                <div class="bs-tc-total">{{ fmt(data.total_liabilities) }}</div>
                            </div>
                            <table class="bs-tbl">
                                <thead><tr><th>Code</th><th>Account Name</th><th class="bs-tbl-right">Balance (PKR)</th></tr></thead>
                                <tbody>
                                    <tr v-if="liabilityRows.length === 0"><td colspan="3" class="bs-tbl-empty">No liability accounts found</td></tr>
                                    <tr v-for="r in liabilityRows" :key="r.account_code">
                                        <td><span class="bs-code">{{ r.account_code }}</span></td>
                                        <td>{{ r.account_name }}</td>
                                        <td class="bs-tbl-right bs-amt-red">{{ fmt(Math.abs(r.balance)) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot><tr class="bs-tbl-foot bs-tbl-foot-liab"><td colspan="2">Total Liabilities</td><td class="bs-tbl-right">{{ fmt(data.total_liabilities) }}</td></tr></tfoot>
                            </table>
                        </div>

                        <!-- Equity -->
                        <div class="bs-table-card" style="margin-top:16px">
                            <div class="bs-tc-header bs-tc-header-equity">
                                <div class="bs-tc-title"><PieChartOutlined /> Equity</div>
                                <div class="bs-tc-total">{{ fmt(data.total_equity) }}</div>
                            </div>
                            <table class="bs-tbl">
                                <thead><tr><th>Code</th><th>Account Name</th><th class="bs-tbl-right">Balance (PKR)</th></tr></thead>
                                <tbody>
                                    <tr v-if="equityRows.length === 0"><td colspan="3" class="bs-tbl-empty">No equity accounts found</td></tr>
                                    <tr v-for="r in equityRows" :key="r.account_code">
                                        <td><span class="bs-code">{{ r.account_code }}</span></td>
                                        <td>{{ r.account_name }}</td>
                                        <td class="bs-tbl-right bs-amt-purple">{{ fmt(Math.abs(r.balance)) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot><tr class="bs-tbl-foot bs-tbl-foot-equity"><td colspan="2">Total Equity</td><td class="bs-tbl-right">{{ fmt(data.total_equity) }}</td></tr></tfoot>
                            </table>
                        </div>

                        <!-- L + E Total -->
                        <div class="bs-le-total" :class="isBalanced ? 'bs-le-ok' : 'bs-le-err'">
                            <div class="bs-le-left">
                                <div class="bs-le-icon"><CheckCircleOutlined v-if="isBalanced" /><WarningOutlined v-else /></div>
                                <div>
                                    <div class="bs-le-title">Total Liabilities + Equity</div>
                                    <div class="bs-le-sub">{{ isBalanced ? '✓ Balance sheet is balanced — A = L + E' : '⚠ Balance sheet is NOT balanced' }}</div>
                                </div>
                            </div>
                            <div class="bs-le-amount">{{ fmt(+data.total_liabilities + +data.total_equity) }}</div>
                        </div>

                    </div>
                </div>

            </div>
        </a-spin>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import dayjs from 'dayjs';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import {
    PrinterOutlined, SearchOutlined,
    BankOutlined, CreditCardOutlined, PieChartOutlined,
    CheckCircleOutlined, WarningOutlined, CalendarOutlined,
} from '@ant-design/icons-vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        PrinterOutlined, SearchOutlined,
        BankOutlined, CreditCardOutlined, PieChartOutlined,
        CheckCircleOutlined, WarningOutlined, CalendarOutlined,
    },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading = ref(false);
        const filters = ref({ as_of: dayjs() });
        const data = ref({ data: [], total_assets: 0, total_liabilities: 0, total_equity: 0, as_of: '' });

        const assetRows     = computed(() => (data.value.data || []).filter(r => r.account_type === 'Asset'));
        const liabilityRows = computed(() => (data.value.data || []).filter(r => r.account_type === 'Liability'));
        const equityRows    = computed(() => (data.value.data || []).filter(r => r.account_type === 'Equity'));
        const isBalanced    = computed(() => Math.abs((+data.value.total_assets) - ((+data.value.total_liabilities) + (+data.value.total_equity))) < 1);

        const assetPercent = (val) => {
            const total = Number(data.value.total_assets) || 0;
            if (total === 0) return 0;
            return Math.min(100, Math.abs((Number(val) / total) * 100)).toFixed(1);
        };

        const fmt        = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        const setQuick = (preset) => {
            const now = dayjs();
            const map = { today: now, month: now.endOf('month'), quarter: now.endOf('quarter'), year: now.endOf('year') };
            filters.value.as_of = map[preset];
            load();
        };

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/balance-sheet', {
                    params: { as_of: filters.value.as_of?.format('YYYY-MM-DD') },
                });
                data.value = res.data;
            } catch (e) {} finally { loading.value = false; }
        };

        const print = () => window.print();
        onMounted(load);
        return { loading, filters, data, assetRows, liabilityRows, equityRows, isBalanced, assetPercent, fmt, formatDate, load, setQuick, print };
    }
});
</script>

<style scoped>
/* ── Root ─────────────────────────────────────────────────────── */
.bs-root { background: #f1f5f9; min-height: 100%; padding-bottom: 48px; }

/* ── Filter Bar ───────────────────────────────────────────────── */
.bs-filter-bar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.bs-filter-row { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
.bs-filter-group { display: flex; flex-direction: column; gap: 3px; }
.bs-filter-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
.bs-picker { width: 185px !important; border-radius: 8px !important; }
.bs-gen-btn { height: 36px !important; border-radius: 8px !important; font-weight: 700 !important; padding: 0 20px !important; background: linear-gradient(135deg,#0ea5e9,#0284c7) !important; border: none !important; box-shadow: 0 4px 12px rgba(14,165,233,.35) !important; }
.bs-gen-btn:hover { background: linear-gradient(135deg,#38bdf8,#0ea5e9) !important; transform: translateY(-1px) !important; }
.bs-quick-set { display: flex; align-items: center; gap: 6px; margin-left: 8px; }
.bs-quick-label { font-size: 11px; color: #94a3b8; font-weight: 600; }
.bs-qbtn { padding: 5px 13px; border-radius: 20px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; }
.bs-qbtn:hover { border-color: #0ea5e9; color: #0284c7; background: #f0f9ff; transform: translateY(-1px); }

/* ── Hero ─────────────────────────────────────────────────────── */
.bs-hero {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;
    margin: 24px 24px 0;
    background: linear-gradient(135deg, #0c1445 0%, #0f2460 40%, #1e3a8a 100%);
    border-radius: 20px; padding: 32px 36px;
    box-shadow: 0 10px 40px rgba(30,58,138,.4);
    color: #fff;
}
.bs-hero-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #93c5fd; margin-bottom: 6px; }
.bs-hero-title { font-size: 28px; font-weight: 800; margin: 0 0 8px; color: #fff; }
.bs-hero-period { font-size: 14px; color: #bfdbfe; margin: 0 0 20px; display: flex; align-items: center; gap: 6px; }
.bs-hero-equation { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.bs-eq-chip { padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.bs-eq-assets { background: rgba(59,130,246,.2); color: #93c5fd; border: 1px solid rgba(59,130,246,.35); }
.bs-eq-liab   { background: rgba(239,68,68,.2);  color: #fca5a5; border: 1px solid rgba(239,68,68,.35); }
.bs-eq-equity { background: rgba(168,85,247,.2); color: #d8b4fe; border: 1px solid rgba(168,85,247,.35); }
.bs-eq-op { color: #93c5fd; font-size: 16px; font-weight: 700; }

.bs-balance-badge { border-radius: 16px; padding: 24px 32px; text-align: center; min-width: 180px; backdrop-filter: blur(8px); }
.bs-badge-ok  { background: rgba(16,185,129,.2); border: 2px solid rgba(16,185,129,.4); }
.bs-badge-err { background: rgba(239,68,68,.2);  border: 2px solid rgba(239,68,68,.4); }
.bs-badge-icon { font-size: 32px; margin-bottom: 8px; }
.bs-badge-ok  .bs-badge-icon { color: #6ee7b7; }
.bs-badge-err .bs-badge-icon { color: #fca5a5; }
.bs-badge-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #bfdbfe; }
.bs-badge-status { font-size: 20px; font-weight: 800; color: #fff; margin: 4px 0 2px; }
.bs-badge-eq { font-size: 13px; color: #93c5fd; font-weight: 700; }

/* ── KPI Grid ─────────────────────────────────────────────────── */
.bs-kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin: 20px 24px 0; }
.bs-kpi-card { background: #fff; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.07); position: relative; overflow: hidden; transition: transform .2s, box-shadow .2s; }
.bs-kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.bs-kpi-icon-wrap { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.bs-kpi-body { flex: 1; }
.bs-kpi-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.bs-kpi-value { font-size: 18px; font-weight: 800; color: #1e293b; }
.bs-kpi-sub { font-size: 11px; color: #cbd5e1; margin-top: 2px; }
.bs-kpi-bar { position: absolute; bottom: 0; left: 0; right: 0; height: 4px; border-radius: 0 0 16px 16px; }

.bs-kpi-assets .bs-kpi-icon-wrap { background: #dbeafe; color: #2563eb; }
.bs-kpi-assets .bs-kpi-bar { background: linear-gradient(90deg,#60a5fa,#2563eb); }
.bs-kpi-liab   .bs-kpi-icon-wrap { background: #fee2e2; color: #dc2626; }
.bs-kpi-liab   .bs-kpi-bar { background: linear-gradient(90deg,#f87171,#dc2626); }
.bs-kpi-equity .bs-kpi-icon-wrap { background: #f3e8ff; color: #9333ea; }
.bs-kpi-equity .bs-kpi-bar { background: linear-gradient(90deg,#c084fc,#9333ea); }
.bs-kpi-ok   .bs-kpi-icon-wrap { background: #dcfce7; color: #16a34a; }
.bs-kpi-ok   .bs-kpi-bar { background: linear-gradient(90deg,#4ade80,#16a34a); }
.bs-kpi-err  .bs-kpi-icon-wrap { background: #fee2e2; color: #dc2626; }
.bs-kpi-err  .bs-kpi-bar { background: linear-gradient(90deg,#f87171,#dc2626); }

/* ── Allocation Bar ───────────────────────────────────────────── */
.bs-alloc-card { background: #fff; border-radius: 16px; margin: 16px 24px 0; padding: 20px 24px; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.bs-alloc-title { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 14px; }
.bs-alloc-track { height: 14px; border-radius: 10px; background: #f1f5f9; overflow: hidden; display: flex; }
.bs-alloc-fill { height: 100%; transition: width .8s ease; }
.bs-alloc-liab   { background: linear-gradient(90deg,#f87171,#dc2626); }
.bs-alloc-equity { background: linear-gradient(90deg,#c084fc,#9333ea); }
.bs-alloc-legend { display: flex; gap: 20px; margin-top: 10px; }
.bs-leg-item { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #475569; }
.bs-leg-dot { width: 10px; height: 10px; border-radius: 50%; }
.bs-leg-dot-liab   { background: #dc2626; }
.bs-leg-dot-equity { background: #9333ea; }

/* ── Detail Grid ──────────────────────────────────────────────── */
.bs-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 24px 0; }
.bs-right-col { display: flex; flex-direction: column; }
.bs-table-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.bs-tc-header { padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; font-size: 14px; font-weight: 700; }
.bs-tc-header-assets { background: linear-gradient(135deg,#eff6ff,#dbeafe); color: #1d4ed8; border-bottom: 2px solid #bfdbfe; }
.bs-tc-header-liab   { background: linear-gradient(135deg,#fff1f2,#fee2e2); color: #b91c1c; border-bottom: 2px solid #fecaca; }
.bs-tc-header-equity { background: linear-gradient(135deg,#faf5ff,#f3e8ff); color: #7e22ce; border-bottom: 2px solid #e9d5ff; }
.bs-tc-title { display: flex; align-items: center; gap: 8px; }
.bs-tc-total { font-size: 16px; font-weight: 800; }

.bs-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.bs-tbl thead tr { background: #f8fafc; }
.bs-tbl th { padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #f1f5f9; }
.bs-tbl td { padding: 10px 14px; border-bottom: 1px solid #f8fafc; color: #334155; }
.bs-tbl tbody tr:hover { background: #f8fafc; }
.bs-tbl tbody tr:last-child td { border-bottom: none; }
.bs-tbl-right { text-align: right !important; }
.bs-tbl-empty { text-align: center; color: #cbd5e1; padding: 24px !important; }
.bs-code { background: #f1f5f9; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-family: monospace; color: #475569; font-weight: 600; }
.bs-amt-blue   { color: #2563eb; font-weight: 600; }
.bs-amt-red    { color: #dc2626; font-weight: 600; }
.bs-amt-purple { color: #9333ea; font-weight: 600; }
.bs-tbl-foot td { padding: 12px 14px; font-weight: 700; font-size: 13px; }
.bs-tbl-foot-assets { background: #eff6ff; color: #1d4ed8; }
.bs-tbl-foot-liab   { background: #fff1f2; color: #b91c1c; }
.bs-tbl-foot-equity { background: #faf5ff; color: #7e22ce; }

/* ── L+E Total ────────────────────────────────────────────────── */
.bs-le-total {
    border-radius: 12px; margin-top: 16px; padding: 18px 20px;
    display: flex; align-items: center; justify-content: space-between;
    font-weight: 700;
}
.bs-le-ok  { background: linear-gradient(135deg,#064e3b,#065f46); color: #fff; }
.bs-le-err { background: linear-gradient(135deg,#7f1d1d,#991b1b); color: #fff; }
.bs-le-left { display: flex; align-items: center; gap: 12px; }
.bs-le-icon { font-size: 22px; }
.bs-le-title { font-size: 14px; font-weight: 700; }
.bs-le-sub { font-size: 11px; opacity: .8; margin-top: 2px; }
.bs-le-amount { font-size: 22px; font-weight: 800; }

@media (max-width: 900px) {
    .bs-kpi-grid { grid-template-columns: repeat(2,1fr); }
    .bs-detail-grid { grid-template-columns: 1fr; }
}
@media print {
    .no-print { display: none !important; }
    .bs-root { background: #fff !important; }
    .bs-hero { box-shadow: none !important; }
}
</style>
