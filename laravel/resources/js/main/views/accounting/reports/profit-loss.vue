<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Profit & Loss Statement" class="p-0">
                <template #extra>
                    <a-button @click="print" style="border-radius:8px;font-weight:600"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Profit & Loss</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="pl-root">
        <!-- ── Filter Bar ─────────────────────────────────── -->
        <div class="pl-filter-bar no-print">
            <div class="pl-filter-row">
                <div class="pl-filter-group">
                    <label class="pl-filter-label">From Date</label>
                    <a-date-picker v-model:value="filters.date_from" class="pl-picker" />
                </div>
                <div class="pl-filter-group">
                    <label class="pl-filter-label">To Date</label>
                    <a-date-picker v-model:value="filters.date_to" class="pl-picker" />
                </div>
                <a-button type="primary" :loading="loading" @click="load" class="pl-gen-btn">
                    <SearchOutlined /> Generate Report
                </a-button>
                <div class="pl-quick-set">
                    <span class="pl-quick-label">Quick:</span>
                    <button class="pl-qbtn" @click="setQuick('month')">This Month</button>
                    <button class="pl-qbtn" @click="setQuick('quarter')">Quarter</button>
                    <button class="pl-qbtn" @click="setQuick('year')">This Year</button>
                </div>
            </div>
        </div>

        <a-spin :spinning="loading">
            <div id="printable-area">

                <!-- ── Hero Banner ───────────────────────────────────── -->
                <div class="pl-hero">
                    <div class="pl-hero-left">
                        <div class="pl-hero-eyebrow">Financial Report</div>
                        <h1 class="pl-hero-title">Profit &amp; Loss Statement</h1>
                        <p class="pl-hero-period">
                            <CalendarOutlined />
                            {{ formatDate(data.date_from) }} &mdash; {{ formatDate(data.date_to) }}
                        </p>
                        <div class="pl-hero-equation">
                            <span class="pl-eq-chip pl-eq-revenue">Revenue {{ fmt(data.total_revenue) }}</span>
                            <span class="pl-eq-op">−</span>
                            <span class="pl-eq-chip pl-eq-cogs">COGS {{ fmt(data.total_cogs) }}</span>
                            <span class="pl-eq-op">−</span>
                            <span class="pl-eq-chip pl-eq-exp">Expenses {{ fmt(data.total_expenses) }}</span>
                            <span class="pl-eq-op">=</span>
                            <span class="pl-eq-chip" :class="data.net_profit >= 0 ? 'pl-eq-profit' : 'pl-eq-loss'">
                                Net {{ fmt(data.net_profit) }}
                            </span>
                        </div>
                    </div>
                    <div class="pl-hero-right">
                        <div class="pl-net-badge" :class="data.net_profit >= 0 ? 'pl-net-badge-profit' : 'pl-net-badge-loss'">
                            <div class="pl-net-badge-icon">
                                <TrophyOutlined v-if="data.net_profit >= 0" />
                                <WarningOutlined v-else />
                            </div>
                            <div class="pl-net-badge-label">Net {{ data.net_profit >= 0 ? 'Profit' : 'Loss' }}</div>
                            <div class="pl-net-badge-amount">{{ fmt(data.net_profit) }}</div>
                            <div class="pl-net-badge-margin">{{ netMargin }}% net margin</div>
                        </div>
                    </div>
                </div>

                <!-- ── KPI Cards ─────────────────────────────────────── -->
                <div class="pl-kpi-grid no-print">
                    <div class="pl-kpi-card pl-kpi-revenue">
                        <div class="pl-kpi-icon-wrap"><RiseOutlined /></div>
                        <div class="pl-kpi-body">
                            <div class="pl-kpi-label">Total Revenue</div>
                            <div class="pl-kpi-value">{{ fmt(data.total_revenue) }}</div>
                            <div class="pl-kpi-sub">{{ incomeRows.length }} income accounts</div>
                        </div>
                        <div class="pl-kpi-bar pl-kpi-bar-revenue"></div>
                    </div>
                    <div class="pl-kpi-card pl-kpi-cogs">
                        <div class="pl-kpi-icon-wrap"><ShoppingCartOutlined /></div>
                        <div class="pl-kpi-body">
                            <div class="pl-kpi-label">Cost of Goods Sold</div>
                            <div class="pl-kpi-value">{{ fmt(data.total_cogs) }}</div>
                            <div class="pl-kpi-sub">{{ cogsRows.length }} COGS accounts</div>
                        </div>
                        <div class="pl-kpi-bar pl-kpi-bar-cogs"></div>
                    </div>
                    <div class="pl-kpi-card pl-kpi-gross">
                        <div class="pl-kpi-icon-wrap"><BarChartOutlined /></div>
                        <div class="pl-kpi-body">
                            <div class="pl-kpi-label">Gross Profit</div>
                            <div class="pl-kpi-value">{{ fmt(data.gross_profit) }}</div>
                            <div class="pl-kpi-sub">{{ grossMargin }}% gross margin</div>
                        </div>
                        <div class="pl-kpi-bar pl-kpi-bar-gross"></div>
                    </div>
                    <div class="pl-kpi-card pl-kpi-exp">
                        <div class="pl-kpi-icon-wrap"><MinusCircleOutlined /></div>
                        <div class="pl-kpi-body">
                            <div class="pl-kpi-label">Operating Expenses</div>
                            <div class="pl-kpi-value">{{ fmt(data.total_expenses) }}</div>
                            <div class="pl-kpi-sub">{{ expenseRows.length }} expense accounts</div>
                        </div>
                        <div class="pl-kpi-bar pl-kpi-bar-exp"></div>
                    </div>
                </div>

                <!-- ── Progress Bars (visual breakdown) ──────────────── -->
                <div class="pl-breakdown-card no-print">
                    <div class="pl-breakdown-title">Revenue Breakdown</div>
                    <div class="pl-breakdown-bars">
                        <div class="pl-bb-row">
                            <span class="pl-bb-label">COGS</span>
                            <div class="pl-bb-track">
                                <div class="pl-bb-fill pl-bb-cogs" :style="{ width: revenuePercent(data.total_cogs) + '%' }"></div>
                            </div>
                            <span class="pl-bb-pct">{{ revenuePercent(data.total_cogs) }}%</span>
                        </div>
                        <div class="pl-bb-row">
                            <span class="pl-bb-label">Expenses</span>
                            <div class="pl-bb-track">
                                <div class="pl-bb-fill pl-bb-exp" :style="{ width: revenuePercent(data.total_expenses) + '%' }"></div>
                            </div>
                            <span class="pl-bb-pct">{{ revenuePercent(data.total_expenses) }}%</span>
                        </div>
                        <div class="pl-bb-row">
                            <span class="pl-bb-label">Net {{ data.net_profit >= 0 ? 'Profit' : 'Loss' }}</span>
                            <div class="pl-bb-track">
                                <div class="pl-bb-fill" :class="data.net_profit >= 0 ? 'pl-bb-profit' : 'pl-bb-loss'" :style="{ width: revenuePercent(Math.abs(data.net_profit)) + '%' }"></div>
                            </div>
                            <span class="pl-bb-pct">{{ netMargin }}%</span>
                        </div>
                    </div>
                </div>

                <!-- ── Detail Tables ─────────────────────────────────── -->
                <div class="pl-tables-wrap">

                    <!-- Revenue -->
                    <div class="pl-table-card">
                        <div class="pl-tc-header pl-tc-header-revenue">
                            <div class="pl-tc-title"><RiseOutlined /> Revenue</div>
                            <div class="pl-tc-total">{{ fmt(data.total_revenue) }}</div>
                        </div>
                        <table class="pl-tbl">
                            <thead><tr><th>Code</th><th>Account Name</th><th class="pl-tbl-right">Amount (PKR)</th></tr></thead>
                            <tbody>
                                <tr v-if="incomeRows.length === 0"><td colspan="3" class="pl-tbl-empty">No revenue accounts for this period</td></tr>
                                <tr v-for="r in incomeRows" :key="r.account_code">
                                    <td><span class="pl-code">{{ r.account_code }}</span></td>
                                    <td>{{ r.account_name }}</td>
                                    <td class="pl-tbl-right pl-amt-green">{{ fmt(r.net) }}</td>
                                </tr>
                            </tbody>
                            <tfoot><tr class="pl-tbl-foot pl-tbl-foot-revenue"><td colspan="2">Total Revenue</td><td class="pl-tbl-right">{{ fmt(data.total_revenue) }}</td></tr></tfoot>
                        </table>
                    </div>

                    <!-- COGS -->
                    <div class="pl-table-card">
                        <div class="pl-tc-header pl-tc-header-cogs">
                            <div class="pl-tc-title"><ShoppingCartOutlined /> Cost of Goods Sold</div>
                            <div class="pl-tc-total">{{ fmt(data.total_cogs) }}</div>
                        </div>
                        <table class="pl-tbl">
                            <thead><tr><th>Code</th><th>Account Name</th><th class="pl-tbl-right">Amount (PKR)</th></tr></thead>
                            <tbody>
                                <tr v-if="cogsRows.length === 0"><td colspan="3" class="pl-tbl-empty">No COGS accounts for this period</td></tr>
                                <tr v-for="r in cogsRows" :key="r.account_code">
                                    <td><span class="pl-code">{{ r.account_code }}</span></td>
                                    <td>{{ r.account_name }}</td>
                                    <td class="pl-tbl-right pl-amt-orange">{{ fmt(Math.abs(r.net)) }}</td>
                                </tr>
                            </tbody>
                            <tfoot><tr class="pl-tbl-foot pl-tbl-foot-cogs"><td colspan="2">Total COGS</td><td class="pl-tbl-right">{{ fmt(data.total_cogs) }}</td></tr></tfoot>
                        </table>
                    </div>

                    <!-- Gross Profit Divider -->
                    <div class="pl-divider-banner" :class="data.gross_profit >= 0 ? 'pl-div-profit' : 'pl-div-loss'">
                        <div><BarChartOutlined /> Gross Profit</div>
                        <div>{{ fmt(data.gross_profit) }} &nbsp;·&nbsp; {{ grossMargin }}% margin</div>
                    </div>

                    <!-- Expenses -->
                    <div class="pl-table-card">
                        <div class="pl-tc-header pl-tc-header-exp">
                            <div class="pl-tc-title"><MinusCircleOutlined /> Operating Expenses</div>
                            <div class="pl-tc-total">{{ fmt(data.total_expenses) }}</div>
                        </div>
                        <table class="pl-tbl">
                            <thead><tr><th>Code</th><th>Account Name</th><th class="pl-tbl-right">Amount (PKR)</th></tr></thead>
                            <tbody>
                                <tr v-if="expenseRows.length === 0"><td colspan="3" class="pl-tbl-empty">No expense accounts for this period</td></tr>
                                <tr v-for="r in expenseRows" :key="r.account_code">
                                    <td><span class="pl-code">{{ r.account_code }}</span></td>
                                    <td>{{ r.account_name }}</td>
                                    <td class="pl-tbl-right pl-amt-purple">{{ fmt(Math.abs(r.net)) }}</td>
                                </tr>
                            </tbody>
                            <tfoot><tr class="pl-tbl-foot pl-tbl-foot-exp"><td colspan="2">Total Expenses</td><td class="pl-tbl-right">{{ fmt(data.total_expenses) }}</td></tr></tfoot>
                        </table>
                    </div>

                    <!-- Net Profit Final -->
                    <div class="pl-net-final" :class="data.net_profit >= 0 ? 'pl-net-final-profit' : 'pl-net-final-loss'">
                        <div class="pl-nf-left">
                            <div class="pl-nf-icon"><TrophyOutlined v-if="data.net_profit >= 0" /><WarningOutlined v-else /></div>
                            <div>
                                <div class="pl-nf-title">NET {{ data.net_profit >= 0 ? 'PROFIT' : 'LOSS' }}</div>
                                <div class="pl-nf-formula">Gross Profit &minus; Operating Expenses</div>
                                <div class="pl-nf-period">{{ formatDate(data.date_from) }} to {{ formatDate(data.date_to) }}</div>
                            </div>
                        </div>
                        <div class="pl-nf-right">
                            <div class="pl-nf-margin-lbl">Net Margin</div>
                            <div class="pl-nf-margin">{{ netMargin }}%</div>
                            <div class="pl-nf-amount">{{ fmt(data.net_profit) }}</div>
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
    RiseOutlined, ShoppingCartOutlined, BarChartOutlined,
    CalendarOutlined, MinusCircleOutlined, TrophyOutlined, WarningOutlined,
} from '@ant-design/icons-vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        PrinterOutlined, SearchOutlined,
        RiseOutlined, ShoppingCartOutlined, BarChartOutlined,
        CalendarOutlined, MinusCircleOutlined, TrophyOutlined, WarningOutlined,
    },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading = ref(false);
        const filters = ref({ date_from: dayjs().startOf('year'), date_to: dayjs() });
        const data = ref({ data: [], total_revenue: 0, total_cogs: 0, gross_profit: 0, total_expenses: 0, net_profit: 0, date_from: '', date_to: '' });

        const incomeRows  = computed(() => (data.value.data || []).filter(r => r.account_type === 'Income'));
        const cogsRows    = computed(() => (data.value.data || []).filter(r => r.account_type === 'COGS'));
        const expenseRows = computed(() => (data.value.data || []).filter(r => r.account_type === 'Expense'));

        const grossMargin = computed(() => {
            const rev = Number(data.value.total_revenue) || 0;
            return rev === 0 ? '0.00' : ((Number(data.value.gross_profit) / rev) * 100).toFixed(1);
        });
        const netMargin = computed(() => {
            const rev = Number(data.value.total_revenue) || 0;
            return rev === 0 ? '0.00' : ((Number(data.value.net_profit) / rev) * 100).toFixed(1);
        });
        const revenuePercent = (val) => {
            const rev = Number(data.value.total_revenue) || 0;
            if (rev === 0) return 0;
            return Math.min(100, Math.abs((Number(val) / rev) * 100)).toFixed(1);
        };

        const fmt        = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        const setQuick = (range) => {
            const now = dayjs();
            const presets = { month: [now.startOf('month'), now.endOf('month')], quarter: [now.startOf('quarter'), now.endOf('quarter')], year: [now.startOf('year'), now.endOf('year')] };
            [filters.value.date_from, filters.value.date_to] = presets[range];
            load();
        };

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/profit-loss', {
                    params: { date_from: filters.value.date_from?.format('YYYY-MM-DD'), date_to: filters.value.date_to?.format('YYYY-MM-DD') },
                });
                data.value = res.data;
            } catch (e) {} finally { loading.value = false; }
        };

        const print = () => window.print();
        onMounted(load);
        return { loading, filters, data, incomeRows, cogsRows, expenseRows, grossMargin, netMargin, revenuePercent, fmt, formatDate, load, setQuick, print };
    }
});
</script>

<style scoped>
/* ── Root ─────────────────────────────────────────────────────── */
.pl-root { background: #f1f5f9; min-height: 100%; padding-bottom: 48px; }

/* ── Filter Bar ───────────────────────────────────────────────── */
.pl-filter-bar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.pl-filter-row { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
.pl-filter-group { display: flex; flex-direction: column; gap: 3px; }
.pl-filter-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
.pl-picker { width: 175px !important; border-radius: 8px !important; }
.pl-gen-btn { height: 36px !important; border-radius: 8px !important; font-weight: 700 !important; padding: 0 20px !important; background: linear-gradient(135deg,#6366f1,#4f46e5) !important; border: none !important; box-shadow: 0 4px 12px rgba(99,102,241,.35) !important; }
.pl-gen-btn:hover { background: linear-gradient(135deg,#818cf8,#6366f1) !important; transform: translateY(-1px) !important; }
.pl-quick-set { display: flex; align-items: center; gap: 6px; margin-left: 8px; }
.pl-quick-label { font-size: 11px; color: #94a3b8; font-weight: 600; }
.pl-qbtn { padding: 5px 13px; border-radius: 20px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; }
.pl-qbtn:hover { border-color: #6366f1; color: #4f46e5; background: #eef2ff; transform: translateY(-1px); }

/* ── Hero Banner ──────────────────────────────────────────────── */
.pl-hero {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;
    margin: 24px 24px 0;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
    border-radius: 20px; padding: 32px 36px;
    box-shadow: 0 10px 40px rgba(67,56,202,.35);
    color: #fff;
}
.pl-hero-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #a5b4fc; margin-bottom: 6px; }
.pl-hero-title { font-size: 28px; font-weight: 800; margin: 0 0 8px; color: #fff; }
.pl-hero-period { font-size: 14px; color: #c7d2fe; margin: 0 0 20px; display: flex; align-items: center; gap: 6px; }
.pl-hero-equation { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pl-eq-chip { padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.pl-eq-revenue { background: rgba(16,185,129,.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }
.pl-eq-cogs { background: rgba(251,146,60,.2); color: #fed7aa; border: 1px solid rgba(251,146,60,.3); }
.pl-eq-exp { background: rgba(167,139,250,.2); color: #ddd6fe; border: 1px solid rgba(167,139,250,.3); }
.pl-eq-profit { background: rgba(16,185,129,.25); color: #6ee7b7; border: 1px solid rgba(16,185,129,.4); }
.pl-eq-loss { background: rgba(239,68,68,.25); color: #fca5a5; border: 1px solid rgba(239,68,68,.4); }
.pl-eq-op { color: #a5b4fc; font-size: 16px; font-weight: 700; }

.pl-net-badge {
    border-radius: 16px; padding: 24px 32px; text-align: center; min-width: 200px;
    backdrop-filter: blur(8px);
}
.pl-net-badge-profit { background: rgba(16,185,129,.2); border: 2px solid rgba(16,185,129,.4); }
.pl-net-badge-loss   { background: rgba(239,68,68,.2);  border: 2px solid rgba(239,68,68,.4); }
.pl-net-badge-icon { font-size: 32px; margin-bottom: 8px; }
.pl-net-badge-profit .pl-net-badge-icon { color: #6ee7b7; }
.pl-net-badge-loss   .pl-net-badge-icon { color: #fca5a5; }
.pl-net-badge-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #c7d2fe; }
.pl-net-badge-amount { font-size: 26px; font-weight: 800; color: #fff; margin: 4px 0; }
.pl-net-badge-margin { font-size: 12px; color: #a5b4fc; }

/* ── KPI Cards ────────────────────────────────────────────────── */
.pl-kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin: 20px 24px 0; }
.pl-kpi-card {
    background: #fff; border-radius: 16px; padding: 20px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    position: relative; overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.pl-kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.pl-kpi-icon-wrap { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.pl-kpi-body { flex: 1; }
.pl-kpi-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.pl-kpi-value { font-size: 18px; font-weight: 800; color: #1e293b; }
.pl-kpi-sub { font-size: 11px; color: #cbd5e1; margin-top: 2px; }
.pl-kpi-bar { position: absolute; bottom: 0; left: 0; right: 0; height: 4px; border-radius: 0 0 16px 16px; }

.pl-kpi-revenue .pl-kpi-icon-wrap { background: #dcfce7; color: #16a34a; }
.pl-kpi-revenue .pl-kpi-bar { background: linear-gradient(90deg,#4ade80,#16a34a); }
.pl-kpi-cogs    .pl-kpi-icon-wrap { background: #ffedd5; color: #ea580c; }
.pl-kpi-cogs    .pl-kpi-bar { background: linear-gradient(90deg,#fb923c,#ea580c); }
.pl-kpi-gross   .pl-kpi-icon-wrap { background: #dbeafe; color: #2563eb; }
.pl-kpi-gross   .pl-kpi-bar { background: linear-gradient(90deg,#60a5fa,#2563eb); }
.pl-kpi-exp     .pl-kpi-icon-wrap { background: #f3e8ff; color: #9333ea; }
.pl-kpi-exp     .pl-kpi-bar { background: linear-gradient(90deg,#c084fc,#9333ea); }

/* ── Breakdown Bars ───────────────────────────────────────────── */
.pl-breakdown-card { background: #fff; border-radius: 16px; margin: 16px 24px 0; padding: 20px 24px; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.pl-breakdown-title { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 16px; }
.pl-breakdown-bars { display: flex; flex-direction: column; gap: 12px; }
.pl-bb-row { display: flex; align-items: center; gap: 12px; }
.pl-bb-label { width: 90px; font-size: 12px; font-weight: 600; color: #64748b; text-align: right; }
.pl-bb-track { flex: 1; height: 10px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
.pl-bb-fill { height: 100%; border-radius: 10px; transition: width .8s ease; }
.pl-bb-pct { width: 48px; font-size: 12px; font-weight: 700; color: #475569; }
.pl-bb-cogs   { background: linear-gradient(90deg,#fb923c,#ea580c); }
.pl-bb-exp    { background: linear-gradient(90deg,#c084fc,#9333ea); }
.pl-bb-profit { background: linear-gradient(90deg,#4ade80,#16a34a); }
.pl-bb-loss   { background: linear-gradient(90deg,#f87171,#dc2626); }

/* ── Tables Wrap ──────────────────────────────────────────────── */
.pl-tables-wrap { margin: 16px 24px 0; display: flex; flex-direction: column; gap: 16px; }
.pl-table-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.pl-tc-header { padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; font-size: 14px; font-weight: 700; }
.pl-tc-header-revenue { background: linear-gradient(135deg,#f0fdf4,#dcfce7); color: #15803d; border-bottom: 2px solid #bbf7d0; }
.pl-tc-header-cogs    { background: linear-gradient(135deg,#fff7ed,#ffedd5); color: #c2410c; border-bottom: 2px solid #fed7aa; }
.pl-tc-header-exp     { background: linear-gradient(135deg,#faf5ff,#f3e8ff); color: #7e22ce; border-bottom: 2px solid #e9d5ff; }
.pl-tc-title { display: flex; align-items: center; gap: 8px; }
.pl-tc-total { font-size: 16px; font-weight: 800; }

.pl-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.pl-tbl thead tr { background: #f8fafc; }
.pl-tbl th { padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #f1f5f9; }
.pl-tbl td { padding: 11px 16px; border-bottom: 1px solid #f8fafc; color: #334155; }
.pl-tbl tbody tr:hover { background: #f8fafc; }
.pl-tbl tbody tr:last-child td { border-bottom: none; }
.pl-tbl-right { text-align: right !important; }
.pl-tbl-empty { text-align: center; color: #cbd5e1; padding: 24px !important; }
.pl-code { background: #f1f5f9; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-family: monospace; color: #475569; font-weight: 600; }
.pl-amt-green  { color: #16a34a; font-weight: 600; }
.pl-amt-orange { color: #ea580c; font-weight: 600; }
.pl-amt-purple { color: #9333ea; font-weight: 600; }

.pl-tbl-foot td { padding: 12px 16px; font-weight: 700; font-size: 13px; }
.pl-tbl-foot-revenue { background: #f0fdf4; color: #15803d; }
.pl-tbl-foot-cogs    { background: #fff7ed; color: #c2410c; }
.pl-tbl-foot-exp     { background: #faf5ff; color: #7e22ce; }

/* ── Gross Profit Divider ─────────────────────────────────────── */
.pl-divider-banner {
    border-radius: 12px; padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between;
    font-size: 15px; font-weight: 700;
}
.pl-div-profit { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color: #1d4ed8; border: 2px solid #93c5fd; }
.pl-div-loss   { background: linear-gradient(135deg,#fee2e2,#fecaca); color: #b91c1c; border: 2px solid #fca5a5; }

/* ── Net Profit Final Banner ──────────────────────────────────── */
.pl-net-final {
    border-radius: 16px; padding: 28px 28px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.12);
}
.pl-net-final-profit { background: linear-gradient(135deg,#064e3b,#065f46,#047857); color: #fff; }
.pl-net-final-loss   { background: linear-gradient(135deg,#7f1d1d,#991b1b,#b91c1c); color: #fff; }
.pl-nf-left { display: flex; align-items: center; gap: 20px; }
.pl-nf-icon { width: 52px; height: 52px; border-radius: 12px; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 24px; }
.pl-nf-title { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
.pl-nf-formula { font-size: 12px; opacity: .8; }
.pl-nf-period { font-size: 11px; opacity: .6; margin-top: 2px; }
.pl-nf-right { text-align: right; }
.pl-nf-margin-lbl { font-size: 11px; opacity: .7; text-transform: uppercase; letter-spacing: 1px; }
.pl-nf-margin { font-size: 22px; font-weight: 800; opacity: .9; }
.pl-nf-amount { font-size: 32px; font-weight: 900; }

@media print {
    .no-print { display: none !important; }
    .pl-root { background: #fff !important; }
    .pl-hero { box-shadow: none !important; }
}
</style>
