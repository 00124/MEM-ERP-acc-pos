<template>
    <div class="bsd-root">
        <AdminPageHeader />

        <!-- ── Filter Bar ──────────────────────────────────────────────────── -->
        <div class="bsd-filter-bar">
            <div class="bsd-filter-row">
                <div class="bsd-title">
                    <BarChartOutlined class="bsd-title-icon" />
                    Balance Sheet Dashboard
                </div>
                <div class="bsd-filter-right">
                    <div class="bsd-filter-group">
                        <span class="bsd-filter-label">Year</span>
                        <a-select v-model:value="filters.year" style="width:100px" size="small" @change="load">
                            <a-select-option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</a-select-option>
                        </a-select>
                    </div>
                    <div class="bsd-filter-group">
                        <span class="bsd-filter-label">As of Date</span>
                        <a-date-picker v-model:value="filters.as_of" size="small" style="width:140px" @change="load" />
                    </div>
                    <a-button type="primary" size="small" :loading="loading" @click="load">
                        <template #icon><ReloadOutlined /></template> Refresh
                    </a-button>
                    <a-button size="small" style="font-weight:600;color:#1677ff;border-color:#1677ff"
                        @click="$router.push({ name: 'admin.accounting.balance_sheet_report' })">
                        <template #icon><FileTextOutlined /></template> Full Report
                    </a-button>
                    <a-button size="small" @click="print">
                        <template #icon><PrinterOutlined /></template>
                    </a-button>
                </div>
            </div>
        </div>

        <!-- Loading spinner -->
        <div v-if="loading" class="bsd-loading"><a-spin size="large" /></div>

        <div v-if="!loading && data" class="bsd-body">

            <!-- ── Row 1: 4 KPI Cards ─────────────────────────────────────── -->
            <div class="bsd-kpi-row">
                <div class="bsd-kpi-card bsd-kpi-blue">
                    <div class="bsd-kpi-icon"><BankOutlined /></div>
                    <div class="bsd-kpi-body">
                        <div class="bsd-kpi-label">Total Assets</div>
                        <div class="bsd-kpi-value">PKR {{ fmtNum(snap.total_assets) }}</div>
                        <div class="bsd-kpi-sub">As of {{ snap.as_of || filters.as_of }}</div>
                    </div>
                </div>
                <div class="bsd-kpi-card bsd-kpi-green">
                    <div class="bsd-kpi-icon"><WalletOutlined /></div>
                    <div class="bsd-kpi-body">
                        <div class="bsd-kpi-label">Cash &amp; Bank</div>
                        <div class="bsd-kpi-value">PKR {{ fmtNum(snap.cash) }}</div>
                        <div class="bsd-kpi-sub">{{ cashPct }}% of total assets</div>
                    </div>
                </div>
                <div class="bsd-kpi-card bsd-kpi-orange">
                    <div class="bsd-kpi-icon"><TeamOutlined /></div>
                    <div class="bsd-kpi-body">
                        <div class="bsd-kpi-label">Accounts Receivable</div>
                        <div class="bsd-kpi-value">PKR {{ fmtNum(snap.accounts_receivable) }}</div>
                        <div class="bsd-kpi-sub">DSO: {{ r.dso }} days</div>
                    </div>
                </div>
                <div class="bsd-kpi-card bsd-kpi-red">
                    <div class="bsd-kpi-icon"><AlertOutlined /></div>
                    <div class="bsd-kpi-body">
                        <div class="bsd-kpi-label">Total Liabilities</div>
                        <div class="bsd-kpi-value">PKR {{ fmtNum(snap.total_liabilities) }}</div>
                        <div class="bsd-kpi-sub">DAR: {{ r.dar }}%</div>
                    </div>
                </div>
            </div>

            <!-- ── Row 2: Balance Sheet + Asset Composition + Metrics ────── -->
            <div class="bsd-main-row">

                <!-- Left: Full Balance Sheet Table -->
                <div class="bsd-card bsd-bs-col">
                    <div class="bsd-section-title">
                        <span>Balance Sheet</span>
                        <span class="bsd-balance-badge" :class="isBalanced ? 'badge-ok' : 'badge-warn'">
                            {{ isBalanced ? '✓ Balanced' : '⚠ Unbalanced' }}
                        </span>
                    </div>

                    <!-- ASSETS -->
                    <div class="bsd-bs-group-hdr bsd-hdr-asset">
                        <span>ASSETS</span>
                        <span>PKR {{ fmtNum(snap.total_assets) }}</span>
                    </div>
                    <template v-for="row in activeAssets" :key="row.id">
                        <div class="bsd-bs-row">
                            <div class="bsd-bs-row-left">
                                <span class="bsd-acc-code">{{ row.account_code }}</span>
                                <span class="bsd-acc-name">{{ row.account_name }}</span>
                            </div>
                            <span class="bsd-bs-amt" :class="row.balance < 0 ? 'amt-neg' : 'amt-pos'">
                                PKR {{ fmtNum(Math.abs(row.balance)) }}
                                <span v-if="row.balance < 0" class="bsd-neg-tag">CR</span>
                            </span>
                        </div>
                    </template>
                    <div v-if="!activeAssets.length" class="bsd-empty-row">No asset entries posted</div>
                    <div class="bsd-bs-total bsd-total-asset">
                        <span>Total Assets</span>
                        <span>PKR {{ fmtNum(snap.total_assets) }}</span>
                    </div>

                    <div style="height:12px"></div>

                    <!-- LIABILITIES -->
                    <div class="bsd-bs-group-hdr bsd-hdr-liab">
                        <span>LIABILITIES</span>
                        <span>PKR {{ fmtNum(snap.total_liabilities) }}</span>
                    </div>
                    <template v-for="row in activeLiabs" :key="row.id">
                        <div class="bsd-bs-row">
                            <div class="bsd-bs-row-left">
                                <span class="bsd-acc-code">{{ row.account_code }}</span>
                                <span class="bsd-acc-name">{{ row.account_name }}</span>
                            </div>
                            <span class="bsd-bs-amt amt-liab">PKR {{ fmtNum(Math.abs(row.balance)) }}</span>
                        </div>
                    </template>
                    <div v-if="!activeLiabs.length" class="bsd-empty-row">No liability entries posted</div>
                    <div class="bsd-bs-total bsd-total-liab">
                        <span>Total Liabilities</span>
                        <span>PKR {{ fmtNum(snap.total_liabilities) }}</span>
                    </div>

                    <div style="height:12px"></div>

                    <!-- EQUITY -->
                    <div class="bsd-bs-group-hdr bsd-hdr-equity">
                        <span>EQUITY</span>
                        <span>PKR {{ fmtNum(snap.total_equity) }}</span>
                    </div>
                    <template v-for="row in activeEquity" :key="row.id">
                        <div class="bsd-bs-row">
                            <div class="bsd-bs-row-left">
                                <span class="bsd-acc-code">{{ row.account_code }}</span>
                                <span class="bsd-acc-name">{{ row.account_name }}</span>
                            </div>
                            <span class="bsd-bs-amt amt-equity">PKR {{ fmtNum(Math.abs(row.balance)) }}</span>
                        </div>
                    </template>
                    <div v-if="!activeEquity.length" class="bsd-empty-row">No equity entries posted</div>
                    <!-- Implied equity note -->
                    <div v-if="impliedEquity !== 0" class="bsd-implied-equity">
                        <span>Implied Equity (Assets − Liabilities)</span>
                        <span :class="impliedEquity >= 0 ? 'amt-equity' : 'amt-neg'">PKR {{ fmtNum(impliedEquity) }}</span>
                    </div>
                    <div class="bsd-bs-total bsd-total-equity">
                        <span>Total Equity</span>
                        <span>PKR {{ fmtNum(snap.total_equity) }}</span>
                    </div>

                    <!-- A = L + E check row -->
                    <div class="bsd-equation-bar" :class="isBalanced ? 'eq-ok' : 'eq-warn'">
                        <span>Assets</span>
                        <strong>PKR {{ fmtNum(snap.total_assets) }}</strong>
                        <span>=</span>
                        <span>L + E</span>
                        <strong>PKR {{ fmtNum(+snap.total_liabilities + +snap.total_equity) }}</strong>
                        <span class="bsd-eq-status">{{ isBalanced ? '✓' : '⚠ Difference: PKR ' + fmtNum(Math.abs(snap.total_assets - snap.total_liabilities - snap.total_equity)) }}</span>
                    </div>
                </div>

                <!-- Right: Metrics & Chart -->
                <div class="bsd-right-panel">

                    <!-- Asset Composition Bar -->
                    <div class="bsd-card bsd-comp-card">
                        <div class="bsd-section-title">Asset Composition</div>
                        <div v-for="seg in assetComposition" :key="seg.label" class="bsd-comp-row">
                            <div class="bsd-comp-label-row">
                                <span class="bsd-comp-name">{{ seg.label }}</span>
                                <span class="bsd-comp-pct" :class="seg.value < 0 ? 'amt-neg' : ''">
                                    {{ seg.value < 0 ? '−' : '' }}PKR {{ fmtNum(Math.abs(seg.value)) }} ({{ seg.pct }}%)
                                </span>
                            </div>
                            <div class="bsd-comp-bar-track">
                                <div class="bsd-comp-bar-fill" :style="{ width: Math.abs(seg.pct) + '%', background: seg.color }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Ratios -->
                    <div class="bsd-card bsd-ratios-card">
                        <div class="bsd-section-title">Key Ratios &amp; Metrics</div>
                        <div class="bsd-ratio-grid">
                            <div class="bsd-ratio-item">
                                <div class="bsd-ratio-lbl">Debt-to-Asset</div>
                                <div class="bsd-ratio-val" :class="r.dar > 50 ? 'val-warn' : 'val-ok'">{{ r.dar }}%</div>
                            </div>
                            <div class="bsd-ratio-item">
                                <div class="bsd-ratio-lbl">Working Capital</div>
                                <div class="bsd-ratio-val val-ok">PKR {{ fmtNum(r.working_capital) }}</div>
                            </div>
                            <div class="bsd-ratio-item">
                                <div class="bsd-ratio-lbl">Days Sales Outstanding</div>
                                <div class="bsd-ratio-val" :class="r.dso > 60 ? 'val-warn' : 'val-ok'">{{ r.dso }} days</div>
                            </div>
                            <div class="bsd-ratio-item">
                                <div class="bsd-ratio-lbl">Cash Balance</div>
                                <div class="bsd-ratio-val val-ok">PKR {{ fmtNum(r.cash_balance) }}</div>
                            </div>
                            <div class="bsd-ratio-item">
                                <div class="bsd-ratio-lbl">AR Outstanding</div>
                                <div class="bsd-ratio-val val-neutral">PKR {{ fmtNum(snap.accounts_receivable) }}</div>
                            </div>
                            <div class="bsd-ratio-item">
                                <div class="bsd-ratio-lbl">Customer Advances</div>
                                <div class="bsd-ratio-val val-warn">PKR {{ fmtNum(snap.total_liabilities) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Cash Trend -->
                    <div class="bsd-card bsd-chart-card">
                        <div class="bsd-section-title">Cash Position — Year by Year</div>
                        <div style="height:180px; background:#fff; border-radius:4px; overflow:hidden;">
                            <BarChart v-if="cashChartData" :chart-data="cashChartData" :options="cashBarOpts" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Row 3: Financial Statement Table ───────────────────────── -->
            <div class="bsd-card bsd-yearly-card">
                <div class="bsd-fs-header">
                    <div>
                        <div class="bsd-section-title" style="margin-bottom:2px">Overall Financial Statement</div>
                        <div class="bsd-yt-note">
                            {{ tableView === 'yearly'
                                ? 'Cumulative year-end balances — last 5 years'
                                : 'Cumulative month-end balances — last 12 months' }}
                        </div>
                    </div>
                    <div class="bsd-view-toggle">
                        <button class="bsd-toggle-btn" :class="tableView === 'yearly' ? 'active' : ''"
                            @click="tableView = 'yearly'">
                            <CalendarOutlined /> Yearly
                        </button>
                        <button class="bsd-toggle-btn" :class="tableView === 'monthly' ? 'active' : ''"
                            @click="tableView = 'monthly'">
                            <ScheduleOutlined /> Monthly
                        </button>
                    </div>
                </div>

                <div class="bsd-yearly-wrap">
                    <table class="bsd-ytbl">
                        <thead>
                            <tr>
                                <th>{{ tableView === 'yearly' ? 'Year' : 'Month' }}</th>
                                <th>Cash</th>
                                <th>Accounts Receivable</th>
                                <th>Inventory (Net)</th>
                                <th>Current Assets</th>
                                <th>Prop. &amp; Equipment</th>
                                <th>Accounts Payable</th>
                                <th>Current Liabilities</th>
                                <th>Equity Capital</th>
                            </tr>
                        </thead>
                        <!-- ── Yearly rows ── -->
                        <tbody v-if="tableView === 'yearly'">
                            <tr v-for="row in yearlyTable" :key="row.year"
                                :class="isRowEmpty(row) ? 'bsd-row-empty' : ''">
                                <td class="bsd-yt-year">
                                    {{ row.year }}
                                    <span v-if="isRowEmpty(row)" class="bsd-yt-no-data">no data</span>
                                </td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.cash) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.accounts_receivable) }}</td>
                                <td :class="row.inventory < 0 ? 'amt-neg' : ''">
                                    {{ isRowEmpty(row) ? '—' : (row.inventory < 0 ? '−' : '') + 'PKR ' + fmtNum(Math.abs(row.inventory)) }}
                                </td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.current_assets) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.property_equipment) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.accounts_payable) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.current_liabilities) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.equity_capital) }}</td>
                            </tr>
                            <tr v-if="!yearlyTable.length">
                                <td colspan="9" class="bsd-empty-msg">No data</td>
                            </tr>
                        </tbody>
                        <!-- ── Monthly rows ── -->
                        <tbody v-else>
                            <tr v-for="row in monthlyTable" :key="row.period"
                                :class="isRowEmpty(row) ? 'bsd-row-empty' : ''">
                                <td class="bsd-yt-year">
                                    {{ row.period }}
                                    <span v-if="isRowEmpty(row)" class="bsd-yt-no-data">no data</span>
                                </td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.cash) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.accounts_receivable) }}</td>
                                <td :class="row.inventory < 0 ? 'amt-neg' : ''">
                                    {{ isRowEmpty(row) ? '—' : (row.inventory < 0 ? '−' : '') + 'PKR ' + fmtNum(Math.abs(row.inventory)) }}
                                </td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.current_assets) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.property_equipment) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.accounts_payable) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.current_liabilities) }}</td>
                                <td>{{ isRowEmpty(row) ? '—' : 'PKR ' + fmtNum(row.equity_capital) }}</td>
                            </tr>
                            <tr v-if="!monthlyTable.length">
                                <td colspan="9" class="bsd-empty-msg">No data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, h } from 'vue';
import dayjs from 'dayjs';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import { BarChart } from 'vue-chart-3';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

// Ensure charts always render on a white canvas (Chart.js 3 canvas is transparent by default)
if (!Chart.registry.plugins.get('whiteBackground')) {
    Chart.register({
        id: 'whiteBackground',
        beforeDraw(chart) {
            const ctx = chart.canvas.getContext('2d');
            ctx.save();
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, chart.canvas.width, chart.canvas.height);
            ctx.restore();
        },
    });
}
import {
    BarChartOutlined, FileTextOutlined, PrinterOutlined, ReloadOutlined,
    BankOutlined, WalletOutlined, TeamOutlined, AlertOutlined,
    CalendarOutlined, ScheduleOutlined,
} from '@ant-design/icons-vue';

export default defineComponent({
    components: {
        AdminPageHeader, BarChart,
        BarChartOutlined, FileTextOutlined, PrinterOutlined, ReloadOutlined,
        BankOutlined, WalletOutlined, TeamOutlined, AlertOutlined,
        CalendarOutlined, ScheduleOutlined,
    },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading    = ref(true);
        const data       = ref(null);

        const now         = dayjs();
        const currentYear = now.year();
        const yearOptions = Array.from({ length: 8 }, (_, i) => currentYear - 7 + i).reverse();

        const filters = ref({ year: currentYear, as_of: now });

        const tableView = ref('yearly');

        /* ── Computed from API ─────────────────────────────────────────── */
        const snap         = computed(() => data.value?.snapshot    ?? {});
        const r            = computed(() => data.value?.ratios      ?? {});
        const charts       = computed(() => data.value?.charts      ?? {});
        const yearlyTable  = computed(() => data.value?.yearly_table  ?? []);
        const monthlyTable = computed(() => data.value?.monthly_table ?? []);

        /* Only accounts with non-zero balances */
        const allRows    = computed(() => snap.value.data || []);
        const activeAssets  = computed(() => allRows.value.filter(x => x.account_type === 'Asset'  && Number(x.balance) !== 0));
        const activeLiabs   = computed(() => allRows.value.filter(x => x.account_type === 'Liability' && Number(x.balance) !== 0));
        const activeEquity  = computed(() => allRows.value.filter(x => x.account_type === 'Equity' && Number(x.balance) !== 0));

        const isBalanced    = computed(() =>
            Math.abs(Number(snap.value.total_assets || 0) - (Number(snap.value.total_liabilities || 0) + Number(snap.value.total_equity || 0))) < 1
        );
        const impliedEquity = computed(() =>
            Number(snap.value.total_assets || 0) - Number(snap.value.total_liabilities || 0)
        );

        /* Cash % of assets */
        const cashPct = computed(() => {
            const a = Number(snap.value.total_assets || 0);
            const c = Number(snap.value.cash || 0);
            return a > 0 ? Math.round((c / a) * 100) : 0;
        });

        /* Asset composition bars */
        const ASSET_COLORS = {
            cash:       '#1677ff',
            receivable: '#fa8b0c',
            inventory:  '#f5222d',
            other:      '#8c8c8c',
        };
        const assetComposition = computed(() => {
            const tot = Math.abs(Number(snap.value.total_assets || 0)) || 1;
            const cash = Number(snap.value.cash || 0);
            const ar   = Number(snap.value.accounts_receivable || 0);
            const inv  = Number(snap.value.inventory || 0);
            const other = Number(snap.value.total_assets || 0) - cash - ar - inv;
            return [
                { label: 'Cash & Bank',           value: cash,  color: ASSET_COLORS.cash,       pct: Math.round(Math.abs(cash)  / tot * 100) },
                { label: 'Accounts Receivable',   value: ar,    color: ASSET_COLORS.receivable,  pct: Math.round(Math.abs(ar)    / tot * 100) },
                { label: 'Inventory (Net)',        value: inv,   color: ASSET_COLORS.inventory,   pct: Math.round(Math.abs(inv)   / tot * 100) },
                { label: 'Other Assets',          value: other, color: ASSET_COLORS.other,       pct: Math.round(Math.abs(other) / tot * 100) },
            ].filter(s => s.value !== 0);
        });

        /* Cash bar chart */
        const cashChartData = computed(() => {
            const c = charts.value;
            if (!c.years?.length) return null;
            return {
                labels: c.years,
                datasets: [{
                    label: 'Cash (PKR)',
                    data: c.cash_by_year,
                    backgroundColor: '#1677ff',
                    borderRadius: 4,
                }],
            };
        });

        const cashBarOpts = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    ticks: {
                        maxTicksLimit: 5,
                        font: { size: 10 },
                        callback: v => v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'K' : v,
                    },
                },
                x: { ticks: { font: { size: 10 } } },
            },
        };

        /* ── Helpers ───────────────────────────────────────────────────── */
        const isRowEmpty = (row) =>
            !row.cash && !row.accounts_receivable && !row.inventory &&
            !row.current_assets && !row.property_equipment &&
            !row.accounts_payable && !row.current_liabilities && !row.equity_capital;

        const fmtNum = (v) => {
            const n = Number(v || 0);
            if (Math.abs(n) >= 1_000_000) return (n / 1_000_000).toFixed(2) + 'M';
            if (Math.abs(n) >= 1_000)    return n.toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            return n.toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        };

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/balance-sheet-dashboard', {
                    params: {
                        as_of: filters.value.as_of?.format?.('YYYY-MM-DD') ?? filters.value.as_of,
                        year:  filters.value.year,
                    },
                });
                data.value = res.data;
            } catch (e) {
                console.error(e);
            } finally {
                loading.value = false;
            }
        };

        const print = () => window.print();
        onMounted(load);

        return {
            loading, data, filters, yearOptions,
            snap, r, charts, yearlyTable,
            activeAssets, activeLiabs, activeEquity,
            isBalanced, impliedEquity, cashPct,
            assetComposition, cashChartData, cashBarOpts,
            fmtNum, isRowEmpty, tableView, monthlyTable, load, print,
        };
    },
});
</script>

<style scoped>
/* ── Root ────────────────────────────────────────────────────────── */
.bsd-root  { min-height: 100vh; background: #F4F5F7; font-family: 'Inter', sans-serif; }
.bsd-body  { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; display: flex; flex-direction: column; gap: 18px; }
.bsd-loading { display: flex; justify-content: center; padding: 80px; }

/* ── Filter bar ──────────────────────────────────────────────────── */
.bsd-filter-bar  { background: #fff; border-bottom: 1px solid #E3E6EF; padding: 12px 24px; }
.bsd-filter-row  { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.bsd-title       { font-size: 16px; font-weight: 700; color: #272B41; display: flex; align-items: center; gap: 8px; }
.bsd-title-icon  { color: #1677ff; font-size: 18px; }
.bsd-filter-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.bsd-filter-group { display: flex; align-items: center; gap: 6px; }
.bsd-filter-label { font-size: 12px; color: #5A5F7D; white-space: nowrap; }

/* ── Cards ───────────────────────────────────────────────────────── */
.bsd-card { background: #fff; border-radius: 10px; border: 1px solid #E3E6EF; padding: 18px 20px; }
.bsd-section-title { font-size: 13px; font-weight: 700; color: #272B41; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 8px; }

/* ── KPI Row ─────────────────────────────────────────────────────── */
.bsd-kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.bsd-kpi-card { border-radius: 10px; padding: 16px 18px; display: flex; align-items: flex-start; gap: 14px; border: 1px solid transparent; }
.bsd-kpi-icon { font-size: 24px; padding-top: 2px; flex-shrink: 0; }
.bsd-kpi-body { flex: 1; }
.bsd-kpi-label { font-size: 12px; font-weight: 600; margin-bottom: 4px; opacity: .85; }
.bsd-kpi-value { font-size: 18px; font-weight: 800; letter-spacing: -.5px; line-height: 1.2; }
.bsd-kpi-sub   { font-size: 11px; margin-top: 4px; opacity: .7; }

.bsd-kpi-blue  { background: linear-gradient(135deg,#e6f4ff 0%,#dbeeff 100%); border-color: #bae0ff; color: #0958d9; }
.bsd-kpi-green { background: linear-gradient(135deg,#d6fff4 0%,#c6fae8 100%); border-color: #87e8ca; color: #0CAB7C; }
.bsd-kpi-orange{ background: linear-gradient(135deg,#fff7e6 0%,#ffe8ba 100%); border-color: #ffd591; color: #d46b08; }
.bsd-kpi-red   { background: linear-gradient(135deg,#fff1f0 0%,#ffe4e1 100%); border-color: #ffccc7; color: #cf1322; }

/* ── Main Row ────────────────────────────────────────────────────── */
.bsd-main-row { display: grid; grid-template-columns: 1fr 380px; gap: 18px; align-items: start; }

/* ── Balance Sheet ───────────────────────────────────────────────── */
.bsd-bs-col { padding: 18px 20px; }

.bsd-balance-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; flex-shrink: 0; }
.badge-ok   { background: #d6fff4; color: #0CAB7C; }
.badge-warn { background: #fff1f0; color: #FF4D4F; }

.bsd-bs-group-hdr { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; letter-spacing: .8px; margin-bottom: 4px; }
.bsd-hdr-asset  { background: #e6f4ff; color: #0958d9; }
.bsd-hdr-liab   { background: #fff1f0; color: #cf1322; }
.bsd-hdr-equity { background: #f5f0ff; color: #531dab; }

.bsd-bs-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-bottom: 1px solid #F1F2F6; font-size: 12px; }
.bsd-bs-row:hover { background: #F8F9FB; }
.bsd-bs-row-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
.bsd-acc-code { font-size: 10px; background: #F4F5F7; color: #ADB4D2; padding: 1px 6px; border-radius: 4px; flex-shrink: 0; font-family: monospace; }
.bsd-acc-name { color: #272B41; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bsd-bs-amt { font-weight: 600; font-size: 12px; flex-shrink: 0; text-align: right; min-width: 130px; }
.bsd-neg-tag { font-size: 9px; background: #fff1f0; color: #FF4D4F; padding: 1px 4px; border-radius: 3px; margin-left: 4px; vertical-align: middle; }

.amt-pos    { color: #0958d9; }
.amt-neg    { color: #FF4D4F; }
.amt-liab   { color: #cf1322; }
.amt-equity { color: #531dab; }

.bsd-empty-row { font-size: 12px; color: #ADB4D2; padding: 8px 10px; font-style: italic; }

.bsd-bs-total { display: flex; justify-content: space-between; padding: 9px 10px; border-radius: 6px; font-size: 13px; font-weight: 700; margin-top: 6px; }
.bsd-total-asset  { background: #e6f4ff; color: #0958d9; }
.bsd-total-liab   { background: #fff1f0; color: #cf1322; }
.bsd-total-equity { background: #f5f0ff; color: #531dab; }

.bsd-implied-equity { display: flex; justify-content: space-between; padding: 6px 10px; font-size: 11px; color: #5A5F7D; background: #F8F9FB; border-radius: 4px; margin: 6px 0 2px; font-style: italic; }

.bsd-equation-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 10px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; margin-top: 14px; border: 1.5px solid; }
.eq-ok   { background: #d6fff4; border-color: #87e8ca; color: #0CAB7C; }
.eq-warn { background: #fff1f0; border-color: #ffccc7; color: #cf1322; }
.bsd-eq-status { margin-left: auto; font-size: 11px; }

/* ── Right Panel ─────────────────────────────────────────────────── */
.bsd-right-panel { display: flex; flex-direction: column; gap: 14px; }

/* Composition bars */
.bsd-comp-card { padding: 16px 18px; }
.bsd-comp-row { margin-bottom: 12px; }
.bsd-comp-label-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; margin-bottom: 4px; }
.bsd-comp-name { color: #272B41; font-weight: 500; }
.bsd-comp-pct  { color: #5A5F7D; font-size: 11px; }
.bsd-comp-bar-track { height: 8px; background: #F1F2F6; border-radius: 4px; overflow: hidden; }
.bsd-comp-bar-fill  { height: 100%; border-radius: 4px; transition: width .4s ease; }

/* Ratio grid */
.bsd-ratios-card { padding: 16px 18px; }
.bsd-ratio-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.bsd-ratio-item { background: #F8F9FB; border-radius: 8px; padding: 10px 12px; }
.bsd-ratio-lbl  { font-size: 10px; color: #ADB4D2; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
.bsd-ratio-val  { font-size: 14px; font-weight: 800; }
.val-ok      { color: #0CAB7C; }
.val-warn    { color: #fa8b0c; }
.val-neutral { color: #1677ff; }

/* Cash chart */
.bsd-chart-card { padding: 16px 18px; background: #fff; }
.bsd-chart-card canvas { background: #fff !important; }

/* ── Financial Statement Table ───────────────────────────────────── */
.bsd-yearly-card { padding: 18px 20px; }
.bsd-fs-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 14px; flex-wrap: wrap; }
.bsd-view-toggle { display: flex; gap: 4px; background: #F4F5F7; border-radius: 8px; padding: 3px; flex-shrink: 0; }
.bsd-toggle-btn { display: flex; align-items: center; gap: 5px; padding: 6px 14px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; background: transparent; color: #ADB4D2; transition: all .2s; font-family: inherit; }
.bsd-toggle-btn:hover { color: #5A5F7D; }
.bsd-toggle-btn.active { background: #fff; color: #1677ff; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
.bsd-yearly-wrap { overflow-x: auto; }
.bsd-empty-msg { text-align: center; color: #ADB4D2; padding: 20px; }
.bsd-ytbl { width: 100%; border-collapse: collapse; font-size: 12px; white-space: nowrap; }
.bsd-ytbl th { background: #F8F9FB; color: #5A5F7D; font-weight: 700; font-size: 11px; padding: 9px 12px; text-align: right; border-bottom: 2px solid #E3E6EF; }
.bsd-ytbl th:first-child { text-align: left; }
.bsd-ytbl td { padding: 8px 12px; text-align: right; border-bottom: 1px solid #F1F2F6; color: #272B41; }
.bsd-ytbl tbody tr:hover td { background: #F8F9FB; }
.bsd-yt-year { text-align: left; font-weight: 700; color: #1677ff; }
.bsd-yt-note { display: block; font-size: 11px; color: #ADB4D2; font-weight: 400; font-style: italic; }
.bsd-row-empty td { color: #ADB4D2 !important; font-style: italic; background: #FAFAFA; }
.bsd-yt-no-data { font-size: 10px; font-weight: 400; color: #ADB4D2; margin-left: 6px; background: #F1F2F6; padding: 1px 6px; border-radius: 4px; font-style: italic; }

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 1100px) {
    .bsd-main-row { grid-template-columns: 1fr; }
}
@media (max-width: 800px) {
    .bsd-kpi-row { grid-template-columns: 1fr 1fr; }
    .bsd-body { padding: 12px 12px 30px; }
}
@media (max-width: 480px) {
    .bsd-kpi-row { grid-template-columns: 1fr; }
}
</style>
