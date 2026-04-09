<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Balance Sheet — Detailed Report" class="p-0">
                <template #extra>
                    <a-button @click="$router.push({ name: 'admin.accounting.balance_sheet' })" style="border-radius:8px;font-weight:600">
                        <LeftOutlined /> Dashboard
                    </a-button>
                    <a-button @click="print" style="border-radius:8px;font-weight:600"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Home</router-link></a-breadcrumb-item>
                <a-breadcrumb-item><router-link :to="{ name: 'admin.accounting.balance_sheet' }">Balance Sheet</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Detailed Report</a-breadcrumb-item>
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
    PrinterOutlined, SearchOutlined, LeftOutlined,
    BankOutlined, CreditCardOutlined, PieChartOutlined,
    CheckCircleOutlined, WarningOutlined, CalendarOutlined,
} from '@ant-design/icons-vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        PrinterOutlined, SearchOutlined, LeftOutlined,
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
.bs-root { background: #F4F5F7; min-height: 100%; padding-bottom: 48px; font-family: 'Inter', sans-serif; }

/* ── Filter Bar ───────────────────────────────────────────────── */
.bs-filter-bar { background: #fff; border-bottom: 1px solid #E3E6EF; padding: 14px 24px; }
.bs-filter-row { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
.bs-filter-group { display: flex; flex-direction: column; gap: 3px; }
.bs-filter-label { font-size: 10px; font-weight: 700; color: #5A5F7D; text-transform: uppercase; letter-spacing: .5px; font-family: 'Inter', sans-serif; }
.bs-picker { width: 185px !important; border-radius: 6px !important; }
.bs-gen-btn { height: 36px !important; border-radius: 6px !important; font-weight: 600 !important; padding: 0 20px !important; font-family: 'Inter', sans-serif !important; }
.bs-quick-set { display: flex; align-items: center; gap: 6px; margin-left: 8px; }
.bs-quick-label { font-size: 11px; color: #ADB4D2; font-weight: 600; font-family: 'Inter', sans-serif; }
.bs-qbtn { padding: 4px 12px; border-radius: 20px; border: 1px solid #E3E6EF; background: #F8F9FB; color: #5A5F7D; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; font-family: 'Inter', sans-serif; }
.bs-qbtn:hover { border-color: #1677ff; color: #1677ff; background: #e6f4ff; }

/* ── Hero ─────────────────────────────────────────────────────── */
.bs-hero {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;
    margin: 24px 24px 0;
    background: #fff;
    border-radius: 16px; padding: 28px 32px;
    border: 1px solid #E3E6EF;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.bs-hero-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #5A5F7D; margin-bottom: 6px; font-family: 'Inter', sans-serif; }
.bs-hero-title { font-size: 24px; font-weight: 800; margin: 0 0 6px; color: #272B41; font-family: 'Inter', sans-serif; }
.bs-hero-period { font-size: 13px; color: #5A5F7D; margin: 0 0 18px; display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif; }
.bs-hero-equation { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.bs-eq-chip { padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; font-family: 'Inter', sans-serif; }
.bs-eq-assets { background: #e6f4ff; color: #1677ff; border: 1px solid #91caff; }
.bs-eq-liab   { background: #fff1f0; color: #FF4D4F; border: 1px solid #ffccc7; }
.bs-eq-equity { background: #f5f0ff; color: #722ed1; border: 1px solid #d3adf7; }
.bs-eq-op { color: #ADB4D2; font-size: 16px; font-weight: 700; }

.bs-balance-badge { border-radius: 12px; padding: 20px 28px; text-align: center; min-width: 160px; }
.bs-badge-ok  { background: #d6fff4; border: 2px solid #87e8ca; }
.bs-badge-err { background: #fff1f0; border: 2px solid #ffccc7; }
.bs-badge-icon { font-size: 28px; margin-bottom: 6px; }
.bs-badge-ok  .bs-badge-icon { color: #20C997; }
.bs-badge-err .bs-badge-icon { color: #FF4D4F; }
.bs-badge-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #5A5F7D; font-family: 'Inter', sans-serif; }
.bs-badge-status { font-size: 18px; font-weight: 800; margin: 4px 0 2px; font-family: 'Inter', sans-serif; }
.bs-badge-ok  .bs-badge-status { color: #0CAB7C; }
.bs-badge-err .bs-badge-status { color: #FF4D4F; }
.bs-badge-eq { font-size: 12px; color: #5A5F7D; font-weight: 700; font-family: 'Inter', sans-serif; }

/* ── KPI Grid ─────────────────────────────────────────────────── */
.bs-kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin: 20px 24px 0; }
.bs-kpi-card { background: #fff; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; border: 1px solid #E3E6EF; position: relative; overflow: hidden; transition: transform .2s, box-shadow .2s; }
.bs-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
.bs-kpi-icon-wrap { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.bs-kpi-body { flex: 1; }
.bs-kpi-label { font-size: 11px; font-weight: 700; color: #5A5F7D; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; font-family: 'Inter', sans-serif; }
.bs-kpi-value { font-size: 16px; font-weight: 700; color: #272B41; font-family: 'Inter', sans-serif; }
.bs-kpi-sub { font-size: 11px; color: #ADB4D2; margin-top: 2px; font-family: 'Inter', sans-serif; }
.bs-kpi-bar { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; }

.bs-kpi-assets .bs-kpi-icon-wrap { background: #e6f4ff; color: #1677ff; }
.bs-kpi-assets .bs-kpi-bar { background: #1677ff; }
.bs-kpi-liab   .bs-kpi-icon-wrap { background: #fff1f0; color: #FF4D4F; }
.bs-kpi-liab   .bs-kpi-bar { background: #FF4D4F; }
.bs-kpi-equity .bs-kpi-icon-wrap { background: #f5f0ff; color: #722ed1; }
.bs-kpi-equity .bs-kpi-bar { background: #722ed1; }
.bs-kpi-ok   .bs-kpi-icon-wrap { background: #d6fff4; color: #20C997; }
.bs-kpi-ok   .bs-kpi-bar { background: #20C997; }
.bs-kpi-err  .bs-kpi-icon-wrap { background: #fff1f0; color: #FF4D4F; }
.bs-kpi-err  .bs-kpi-bar { background: #FF4D4F; }

/* ── Allocation Bar ───────────────────────────────────────────── */
.bs-alloc-card { background: #fff; border-radius: 12px; margin: 16px 24px 0; padding: 20px 24px; border: 1px solid #E3E6EF; }
.bs-alloc-title { font-size: 13px; font-weight: 700; color: #272B41; margin-bottom: 14px; font-family: 'Inter', sans-serif; }
.bs-alloc-track { height: 12px; border-radius: 8px; background: #F4F5F7; overflow: hidden; display: flex; }
.bs-alloc-fill { height: 100%; transition: width .8s ease; }
.bs-alloc-liab   { background: #FF4D4F; }
.bs-alloc-equity { background: #722ed1; }
.bs-alloc-legend { display: flex; gap: 20px; margin-top: 10px; }
.bs-leg-item { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #5A5F7D; font-family: 'Inter', sans-serif; }
.bs-leg-dot { width: 8px; height: 8px; border-radius: 50%; }
.bs-leg-dot-liab   { background: #FF4D4F; }
.bs-leg-dot-equity { background: #722ed1; }

/* ── Detail Grid ──────────────────────────────────────────────── */
.bs-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 24px 0; }
.bs-right-col { display: flex; flex-direction: column; }
.bs-table-card { background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #E3E6EF; }
.bs-tc-header { padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; font-size: 14px; font-weight: 700; font-family: 'Inter', sans-serif; }
.bs-tc-header-assets { background: #e6f4ff; color: #1677ff; border-bottom: 1px solid #91caff; }
.bs-tc-header-liab   { background: #fff1f0; color: #FF4D4F; border-bottom: 1px solid #ffccc7; }
.bs-tc-header-equity { background: #f5f0ff; color: #722ed1; border-bottom: 1px solid #d3adf7; }
.bs-tc-title { display: flex; align-items: center; gap: 8px; }
.bs-tc-total { font-size: 15px; font-weight: 700; }

.bs-tbl { width: 100%; border-collapse: collapse; font-size: 13px; font-family: 'Inter', sans-serif; }
.bs-tbl thead tr { background: #F8F9FB; }
.bs-tbl th { padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #5A5F7D; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #E3E6EF; }
.bs-tbl td { padding: 10px 14px; border-bottom: 1px solid #F1F2F6; color: #272B41; }
.bs-tbl tbody tr:hover { background: #F8F9FB; }
.bs-tbl tbody tr:last-child td { border-bottom: none; }
.bs-tbl-right { text-align: right !important; }
.bs-tbl-empty { text-align: center; color: #ADB4D2; padding: 24px !important; font-family: 'Inter', sans-serif; }
.bs-code { background: #F4F5F7; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-family: 'Inter', sans-serif; color: #5A5F7D; font-weight: 600; }
.bs-amt-blue   { color: #1677ff; font-weight: 600; }
.bs-amt-red    { color: #FF4D4F; font-weight: 600; }
.bs-amt-purple { color: #722ed1; font-weight: 600; }
.bs-tbl-foot td { padding: 12px 14px; font-weight: 700; font-size: 13px; font-family: 'Inter', sans-serif; }
.bs-tbl-foot-assets { background: #e6f4ff; color: #1677ff; }
.bs-tbl-foot-liab   { background: #fff1f0; color: #FF4D4F; }
.bs-tbl-foot-equity { background: #f5f0ff; color: #722ed1; }

/* ── L+E Total ────────────────────────────────────────────────── */
.bs-le-total {
    border-radius: 10px; margin-top: 16px; padding: 16px 18px;
    display: flex; align-items: center; justify-content: space-between;
    font-weight: 700; border: 1px solid; font-family: 'Inter', sans-serif;
}
.bs-le-ok  { background: #d6fff4; border-color: #87e8ca; color: #0CAB7C; }
.bs-le-err { background: #fff1f0; border-color: #ffccc7; color: #FF4D4F; }
.bs-le-left { display: flex; align-items: center; gap: 12px; }
.bs-le-icon { font-size: 20px; }
.bs-le-title { font-size: 14px; font-weight: 700; }
.bs-le-sub { font-size: 11px; opacity: .8; margin-top: 2px; }
.bs-le-amount { font-size: 20px; font-weight: 800; }

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
