<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Branch-wise Profit & Loss" class="p-0">
                <template #extra>
                    <a-button @click="printReport" class="no-print">
                        <PrinterOutlined /> Print
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Reports</a-breadcrumb-item>
                <a-breadcrumb-item>Branch P&amp;L</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- Filter Bar -->
    <div class="bpl-filter-bar no-print">
        <div class="bpl-filter-inner">
            <div class="bpl-filter-fields">
                <div class="bpl-field-group">
                    <label class="bpl-label">From Date</label>
                    <a-date-picker v-model:value="filters.start_date" class="bpl-datepicker" />
                </div>
                <div class="bpl-field-group">
                    <label class="bpl-label">To Date</label>
                    <a-date-picker v-model:value="filters.end_date" class="bpl-datepicker" />
                </div>
                <a-button type="primary" :loading="loading" @click="load" class="bpl-generate-btn">
                    <SearchOutlined /> Generate Report
                </a-button>
            </div>
            <div class="bpl-quick-filters">
                <span class="bpl-quick-label">Quick:</span>
                <button class="bpl-quick-btn" :class="{ active: activeQuick === 'today' }" @click="setQuick('today')">Today</button>
                <button class="bpl-quick-btn" :class="{ active: activeQuick === 'week' }" @click="setQuick('week')">This Week</button>
                <button class="bpl-quick-btn" :class="{ active: activeQuick === 'month' }" @click="setQuick('month')">This Month</button>
                <button class="bpl-quick-btn" :class="{ active: activeQuick === 'quarter' }" @click="setQuick('quarter')">This Quarter</button>
                <button class="bpl-quick-btn" :class="{ active: activeQuick === 'year' }" @click="setQuick('year')">This Year</button>
            </div>
        </div>
    </div>

    <div class="bpl-wrap">
        <a-spin :spinning="loading">

            <!-- Print header -->
            <div class="bpl-print-header print-only">
                <h1>Branch-wise Profit &amp; Loss</h1>
                <p>{{ printDateRange }}</p>
            </div>

            <template v-if="data">

                <!-- Consolidated KPI Cards -->
                <div class="bpl-kpi-row no-print">
                    <div class="bpl-kpi bpl-kpi--blue">
                        <div class="bpl-kpi-icon">📈</div>
                        <div>
                            <div class="bpl-kpi-label">Total Revenue</div>
                            <div class="bpl-kpi-value">{{ fmt(data.consolidated.sales - data.consolidated.sales_returns + data.consolidated.purchase_returns) }}</div>
                        </div>
                    </div>
                    <div class="bpl-kpi bpl-kpi--amber">
                        <div class="bpl-kpi-icon">🛒</div>
                        <div>
                            <div class="bpl-kpi-label">Total Purchases</div>
                            <div class="bpl-kpi-value">{{ fmt(data.consolidated.purchases) }}</div>
                        </div>
                    </div>
                    <div class="bpl-kpi bpl-kpi--red">
                        <div class="bpl-kpi-icon">💸</div>
                        <div>
                            <div class="bpl-kpi-label">Total Expenses</div>
                            <div class="bpl-kpi-value">{{ fmt(data.consolidated.expenses) }}</div>
                        </div>
                    </div>
                    <div class="bpl-kpi" :class="data.consolidated.profit >= 0 ? 'bpl-kpi--green' : 'bpl-kpi--loss'">
                        <div class="bpl-kpi-icon">{{ data.consolidated.profit >= 0 ? '✅' : '❌' }}</div>
                        <div>
                            <div class="bpl-kpi-label">Net Profit (All Branches)</div>
                            <div class="bpl-kpi-value">{{ fmt(data.consolidated.profit) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Branch Comparison Table -->
                <div class="bpl-section-card">
                    <div class="bpl-section-header">
                        <span>📊 Branch-wise Performance Breakdown</span>
                        <span class="bpl-branch-count">{{ data.branches.length }} branches</span>
                    </div>
                    <div class="table-responsive">
                        <table class="bpl-table">
                            <thead>
                                <tr>
                                    <th class="bpl-th-branch">Branch</th>
                                    <th class="bpl-th-num">Sales</th>
                                    <th class="bpl-th-num">Sales Returns</th>
                                    <th class="bpl-th-num">Net Revenue</th>
                                    <th class="bpl-th-num">Purchases</th>
                                    <th class="bpl-th-num">Expenses</th>
                                    <th class="bpl-th-num">Net Profit / Loss</th>
                                    <th class="bpl-th-bar no-print">Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(branch, idx) in sortedBranches"
                                    :key="branch.id"
                                    class="bpl-tr"
                                    :class="branch.pl.profit < 0 ? 'bpl-tr--loss' : ''"
                                >
                                    <td class="bpl-td-branch">
                                        <span class="bpl-rank-badge" :class="rankClass(idx)">{{ idx + 1 }}</span>
                                        <span class="bpl-branch-name">{{ branch.name }}</span>
                                    </td>
                                    <td class="bpl-td-num bpl-amount-blue">{{ fmt(branch.pl.sales) }}</td>
                                    <td class="bpl-td-num bpl-amount-orange">{{ fmt(branch.pl.sales_returns) }}</td>
                                    <td class="bpl-td-num bpl-amount-teal">{{ fmt(branch.pl.net_revenue) }}</td>
                                    <td class="bpl-td-num bpl-amount-amber">{{ fmt(branch.pl.purchases) }}</td>
                                    <td class="bpl-td-num bpl-amount-red">{{ fmt(branch.pl.expenses) }}</td>
                                    <td class="bpl-td-num bpl-td-profit">
                                        <span :class="branch.pl.profit >= 0 ? 'bpl-profit' : 'bpl-loss'">
                                            {{ branch.pl.profit >= 0 ? '+' : '' }}{{ fmt(branch.pl.profit) }}
                                        </span>
                                    </td>
                                    <td class="bpl-td-bar no-print">
                                        <div class="bpl-bar-wrap">
                                            <div
                                                class="bpl-bar-fill"
                                                :class="branch.pl.profit >= 0 ? 'bpl-bar-green' : 'bpl-bar-red'"
                                                :style="{ width: barWidth(branch.pl.profit) + '%' }"
                                            ></div>
                                            <span class="bpl-bar-pct">{{ margin(branch.pl) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bpl-total-row">
                                    <td class="bpl-td-branch"><strong>🏢 All Branches (Total)</strong></td>
                                    <td class="bpl-td-num"><strong>{{ fmt(data.consolidated.sales) }}</strong></td>
                                    <td class="bpl-td-num"><strong>{{ fmt(data.consolidated.sales_returns) }}</strong></td>
                                    <td class="bpl-td-num"><strong>{{ fmt(consolidatedNetRevenue) }}</strong></td>
                                    <td class="bpl-td-num"><strong>{{ fmt(data.consolidated.purchases) }}</strong></td>
                                    <td class="bpl-td-num"><strong>{{ fmt(data.consolidated.expenses) }}</strong></td>
                                    <td class="bpl-td-num bpl-td-profit">
                                        <strong :class="data.consolidated.profit >= 0 ? 'bpl-profit' : 'bpl-loss'">
                                            {{ data.consolidated.profit >= 0 ? '+' : '' }}{{ fmt(data.consolidated.profit) }}
                                        </strong>
                                    </td>
                                    <td class="no-print"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Branch Profit Ranking -->
                <div class="bpl-section-card no-print">
                    <div class="bpl-section-header">
                        <span>🏆 Branch Profit Ranking</span>
                    </div>
                    <div class="bpl-ranking-grid">
                        <div
                            v-for="(branch, idx) in sortedBranches"
                            :key="branch.id"
                            class="bpl-rank-card"
                            :class="branch.pl.profit < 0 ? 'bpl-rank-card--loss' : ''"
                        >
                            <div class="bpl-rank-card-top">
                                <span class="bpl-rank-badge bpl-rank-badge--lg" :class="rankClass(idx)">{{ idx + 1 }}</span>
                                <div>
                                    <div class="bpl-rank-name">{{ branch.name }}</div>
                                    <div class="bpl-rank-margin">Margin: {{ margin(branch.pl) }}%</div>
                                </div>
                            </div>
                            <div class="bpl-rank-rows">
                                <div class="bpl-rank-row">
                                    <span>Sales</span><span class="bpl-amount-blue">{{ fmt(branch.pl.sales) }}</span>
                                </div>
                                <div class="bpl-rank-row">
                                    <span>Expenses</span><span class="bpl-amount-red">{{ fmt(branch.pl.expenses) }}</span>
                                </div>
                                <div class="bpl-rank-row bpl-rank-row--total">
                                    <span>Net P&amp;L</span>
                                    <span :class="branch.pl.profit >= 0 ? 'bpl-profit' : 'bpl-loss'">
                                        {{ branch.pl.profit >= 0 ? '+' : '' }}{{ fmt(branch.pl.profit) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </template>

            <!-- Empty state -->
            <div v-if="!data && !loading" class="bpl-empty">
                <div class="bpl-empty-icon">📊</div>
                <p>Select a date range and click <strong>Generate Report</strong> to view the Branch-wise P&amp;L.</p>
            </div>

        </a-spin>
    </div>
</template>

<script>
import { ref, reactive, computed } from "vue";
import { PrinterOutlined, SearchOutlined } from "@ant-design/icons-vue";
import dayjs from "dayjs";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import common from "../../../../common/composable/common";

export default {
    components: { PrinterOutlined, SearchOutlined, AdminPageHeader },
    setup() {
        const { formatAmountCurrency, appSetting } = common();

        const loading     = ref(false);
        const data        = ref(null);
        const activeQuick = ref("month");

        const filters = reactive({
            start_date: dayjs().startOf("month"),
            end_date:   dayjs(),
        });

        const fmt = (v) => formatAmountCurrency(v ?? 0);

        const sortedBranches = computed(() => {
            if (!data.value) return [];
            return [...data.value.branches].sort((a, b) => b.pl.profit - a.pl.profit);
        });

        const consolidatedNetRevenue = computed(() => {
            if (!data.value) return 0;
            const c = data.value.consolidated;
            return c.sales - c.sales_returns + c.purchase_returns;
        });

        const maxAbsProfit = computed(() => {
            if (!data.value) return 1;
            const max = Math.max(...data.value.branches.map((b) => Math.abs(b.pl.profit)));
            return max || 1;
        });

        const barWidth = (profit) => {
            return Math.min(100, Math.round((Math.abs(profit) / maxAbsProfit.value) * 100));
        };

        const margin = (pl) => {
            if (!pl.sales || pl.sales === 0) return "0.0";
            return ((pl.profit / pl.sales) * 100).toFixed(1);
        };

        const rankClass = (idx) => {
            if (idx === 0) return "rank-gold";
            if (idx === 1) return "rank-silver";
            if (idx === 2) return "rank-bronze";
            return "";
        };

        const printDateRange = computed(() => {
            if (!data.value) return "";
            return `${filters.start_date?.format("DD MMM YYYY")} – ${filters.end_date?.format("DD MMM YYYY")}`;
        });

        const load = async () => {
            loading.value = true;
            try {
                const params = new URLSearchParams();
                if (filters.start_date) params.append("start_date", filters.start_date.format("YYYY-MM-DD") + " 00:00:00");
                if (filters.end_date)   params.append("end_date",   filters.end_date.format("YYYY-MM-DD")   + " 23:59:59");
                const res = await axiosAdmin.get(`reports/branch-profit-loss?${params}`);
                data.value = res;
            } catch (e) {
                data.value = null;
            } finally {
                loading.value = false;
            }
        };

        const setQuick = (period) => {
            activeQuick.value = period;
            const today = dayjs();
            const map = {
                today:   [today.startOf("day"),     today.endOf("day")],
                week:    [today.startOf("week"),     today.endOf("week")],
                month:   [today.startOf("month"),    today.endOf("month")],
                quarter: [today.startOf("quarter"),  today.endOf("quarter")],
                year:    [today.startOf("year"),     today.endOf("year")],
            };
            [filters.start_date, filters.end_date] = map[period];
            load();
        };

        const printReport = () => window.print();

        // Load on mount with current month
        load();

        return {
            loading, data, filters, activeQuick,
            sortedBranches, consolidatedNetRevenue, maxAbsProfit,
            fmt, barWidth, margin, rankClass, printDateRange,
            load, setQuick, printReport,
        };
    },
};
</script>

<style scoped>
/* ── Filter Bar ──────────────────────────────────────────────────────────── */
.bpl-filter-bar {
    background: #fff; border-bottom: 1px solid #e2e8f0;
    padding: 16px 24px;
}
.bpl-filter-inner { display: flex; flex-direction: column; gap: 10px; }
.bpl-filter-fields { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
.bpl-field-group { display: flex; flex-direction: column; gap: 4px; }
.bpl-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.bpl-datepicker { width: 160px; }
.bpl-generate-btn { height: 34px; padding: 0 20px; font-weight: 600; }
.bpl-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.bpl-quick-label { font-size: 12px; color: #94a3b8; font-weight: 600; }
.bpl-quick-btn {
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
    border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b; cursor: pointer;
    transition: all .15s;
}
.bpl-quick-btn:hover, .bpl-quick-btn.active {
    background: #1677ff; border-color: #1677ff; color: #fff;
}

/* ── Wrap ──────────────────────────────────────────────────────────────── */
.bpl-wrap { padding: 20px 24px; }

/* ── KPI Cards ───────────────────────────────────────────────────────────── */
.bpl-kpi-row { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.bpl-kpi {
    flex: 1; min-width: 200px; display: flex; align-items: center; gap: 14px;
    padding: 18px 20px; border-radius: 12px; color: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,.12);
}
.bpl-kpi--blue   { background: linear-gradient(135deg, #1677ff, #0958d9); }
.bpl-kpi--amber  { background: linear-gradient(135deg, #f59e0b, #d97706); }
.bpl-kpi--red    { background: linear-gradient(135deg, #ef4444, #dc2626); }
.bpl-kpi--green  { background: linear-gradient(135deg, #22c55e, #16a34a); }
.bpl-kpi--loss   { background: linear-gradient(135deg, #f87171, #b91c1c); }
.bpl-kpi-icon { font-size: 28px; }
.bpl-kpi-label { font-size: 11px; opacity: .88; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .4px; }
.bpl-kpi-value { font-size: 20px; font-weight: 800; }

/* ── Section Card ────────────────────────────────────────────────────────── */
.bpl-section-card {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    margin-bottom: 20px; overflow: hidden;
}
.bpl-section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; font-size: 14px; font-weight: 700; color: #1e293b;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}
.bpl-branch-count {
    font-size: 12px; font-weight: 600; color: #64748b;
    background: #e2e8f0; padding: 2px 10px; border-radius: 20px;
}

/* ── Table ───────────────────────────────────────────────────────────────── */
.bpl-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.bpl-table thead tr { background: linear-gradient(90deg, #1e3a5f, #1677ff); }
.bpl-table thead th {
    padding: 12px 14px; color: #fff; font-weight: 700;
    font-size: 11px; text-transform: uppercase; letter-spacing: .4px;
    white-space: nowrap;
}
.bpl-th-branch { text-align: left; min-width: 160px; }
.bpl-th-num    { text-align: right; min-width: 120px; }
.bpl-th-bar    { text-align: left; min-width: 160px; }

.bpl-tr { transition: background .15s; }
.bpl-tr:nth-child(even) { background: #f8fafc; }
.bpl-tr:hover { background: #eff6ff; }
.bpl-tr--loss { background: #fff5f5 !important; }
.bpl-tr--loss:hover { background: #fee2e2 !important; }

.bpl-td-branch {
    padding: 12px 14px; display: flex; align-items: center; gap: 10px;
}
.bpl-td-num    { padding: 12px 14px; text-align: right; font-family: 'Courier New', monospace; }
.bpl-td-profit { font-weight: 800; font-size: 13.5px; }
.bpl-td-bar    { padding: 12px 14px; }

.bpl-amount-blue  { color: #1677ff; }
.bpl-amount-orange{ color: #d97706; }
.bpl-amount-teal  { color: #0891b2; }
.bpl-amount-amber { color: #b45309; }
.bpl-amount-red   { color: #dc2626; }

.bpl-profit { color: #16a34a; }
.bpl-loss   { color: #dc2626; }

.bpl-total-row {
    background: linear-gradient(90deg, #f0fdf4, #dcfce7) !important;
    border-top: 2px solid #16a34a;
}
.bpl-total-row td { padding: 14px 14px; }

/* Rank badge */
.bpl-rank-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 50%; font-size: 11px;
    font-weight: 800; background: #e2e8f0; color: #64748b; flex-shrink: 0;
}
.bpl-rank-badge--lg { width: 32px; height: 32px; font-size: 14px; }
.rank-gold   { background: #fef3c7; color: #92400e; border: 1.5px solid #fbbf24; }
.rank-silver { background: #f1f5f9; color: #475569; border: 1.5px solid #94a3b8; }
.rank-bronze { background: #fef9ee; color: #92400e; border: 1.5px solid #d97706; }

.bpl-branch-name { font-weight: 600; color: #1e293b; }

/* Progress bar */
.bpl-bar-wrap {
    display: flex; align-items: center; gap: 8px;
    background: #f1f5f9; border-radius: 20px; overflow: hidden;
    height: 20px; position: relative; padding-right: 48px;
}
.bpl-bar-fill {
    height: 100%; border-radius: 20px;
    transition: width .6s cubic-bezier(.4,0,.2,1);
    min-width: 4px;
}
.bpl-bar-green { background: linear-gradient(90deg, #4ade80, #16a34a); }
.bpl-bar-red   { background: linear-gradient(90deg, #fca5a5, #dc2626); }
.bpl-bar-pct {
    position: absolute; right: 8px; font-size: 11px;
    font-weight: 700; color: #475569; white-space: nowrap;
}

/* ── Ranking Grid ────────────────────────────────────────────────────────── */
.bpl-ranking-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px; padding: 16px;
}
.bpl-rank-card {
    border-radius: 10px; border: 1.5px solid #e2e8f0;
    background: #fff; padding: 14px 16px;
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
    transition: box-shadow .2s, transform .2s;
}
.bpl-rank-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); transform: translateY(-2px); }
.bpl-rank-card--loss { border-color: #fca5a5; background: #fff5f5; }
.bpl-rank-card-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.bpl-rank-name { font-size: 14px; font-weight: 700; color: #1e293b; }
.bpl-rank-margin { font-size: 11px; color: #64748b; }
.bpl-rank-rows { display: flex; flex-direction: column; gap: 6px; }
.bpl-rank-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 12.5px; color: #475569;
}
.bpl-rank-row--total {
    margin-top: 6px; padding-top: 8px; border-top: 1px solid #e2e8f0;
    font-weight: 700; font-size: 13px;
}

/* ── Empty ────────────────────────────────────────────────────────────────── */
.bpl-empty {
    text-align: center; padding: 60px 24px; color: #94a3b8;
}
.bpl-empty-icon { font-size: 48px; margin-bottom: 12px; }
.bpl-empty p { font-size: 15px; }

/* ── Print ────────────────────────────────────────────────────────────────── */
.bpl-print-header { display: none; text-align: center; margin-bottom: 20px; }
.bpl-print-header h1 { font-size: 22px; margin: 0 0 6px; }
.bpl-print-header p  { font-size: 13px; color: #64748b; }

@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    .bpl-wrap { padding: 0; }
    .bpl-section-card { box-shadow: none; border: 1px solid #e2e8f0; }
    .bpl-table thead tr { background: #1e3a5f !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
