<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Head Office Dashboard" class="p-0">
                <template #extra>
                    <a-button @click="print" class="ho-btn-outline">
                        <PrinterOutlined /> Print
                    </a-button>
                    <a-button type="primary" :loading="loading" @click="load" class="ho-btn">
                        <ReloadOutlined /> Refresh
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>HO Dashboard</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- ── Access Denied ──────────────────────────────────────────────────── -->
    <div v-if="!isAdmin" class="ho-access-denied">
        <a-result status="403" title="Access Restricted"
            sub-title="This dashboard is only available to administrators and head office users.">
            <template #extra>
                <router-link :to="{ name: 'admin.dashboard.index' }">
                    <a-button type="primary">Go to My Dashboard</a-button>
                </router-link>
            </template>
        </a-result>
    </div>

    <template v-else>
        <!-- ── Filter Bar ─────────────────────────────────────────────────── -->
        <div class="ho-filter-bar no-print">
            <div class="ho-filter-inner">
                <div class="ho-filter-left">
                    <div class="ho-field-group">
                        <label class="ho-label">From Date</label>
                        <a-date-picker v-model:value="filters.date_from" class="ho-datepicker" />
                    </div>
                    <div class="ho-field-group">
                        <label class="ho-label">To Date</label>
                        <a-date-picker v-model:value="filters.date_to" class="ho-datepicker" />
                    </div>
                    <a-button type="primary" :loading="loading" @click="load" class="ho-generate-btn">
                        <SearchOutlined /> Generate Report
                    </a-button>
                </div>
                <div class="ho-quick-filters">
                    <span class="ho-quick-label">Quick:</span>
                    <button v-for="q in quickFilters" :key="q.key"
                        class="ho-quick-btn" :class="{ active: activeQuick === q.key }"
                        @click="setQuick(q.key)">{{ q.label }}</button>
                </div>
            </div>
        </div>

        <!-- ── Page Wrap ──────────────────────────────────────────────────── -->
        <div class="ho-page-wrap">
            <a-spin :spinning="loading">

                <!-- Print Header -->
                <div class="ho-print-header">
                    <h1>Head Office Dashboard — All Stores</h1>
                    <p>{{ fmtDate(data.date_from) }} to {{ fmtDate(data.date_to) }}</p>
                </div>

                <!-- ── KPI Cards ─────────────────────────────────────────── -->
                <div class="ho-kpi-grid no-print">
                    <div class="ho-kpi-card ho-kpi-sales">
                        <div class="ho-kpi-top">
                            <div class="ho-kpi-icon"><ShoppingOutlined /></div>
                            <div class="ho-kpi-badge">All Stores</div>
                        </div>
                        <div class="ho-kpi-label">Total Sales</div>
                        <div class="ho-kpi-value">{{ fmt(data.kpi?.total_sales) }}</div>
                        <div class="ho-kpi-sub">{{ data.kpi?.total_orders ?? 0 }} orders · {{ fmtDate(data.date_from) }} – {{ fmtDate(data.date_to) }}</div>
                    </div>
                    <div class="ho-kpi-card ho-kpi-orders">
                        <div class="ho-kpi-top">
                            <div class="ho-kpi-icon"><FileTextOutlined /></div>
                            <div class="ho-kpi-badge">Invoices</div>
                        </div>
                        <div class="ho-kpi-label">Total Orders</div>
                        <div class="ho-kpi-value">{{ data.kpi?.total_orders ?? 0 }}</div>
                        <div class="ho-kpi-sub">Avg {{ fmtAvg }} per order</div>
                    </div>
                    <div class="ho-kpi-card ho-kpi-cash">
                        <div class="ho-kpi-top">
                            <div class="ho-kpi-icon"><DollarOutlined /></div>
                            <div class="ho-kpi-badge">Collected</div>
                        </div>
                        <div class="ho-kpi-label">Cash Received</div>
                        <div class="ho-kpi-value">{{ fmt(data.kpi?.cash_received) }}</div>
                        <div class="ho-kpi-sub">Payments collected</div>
                    </div>
                    <div class="ho-kpi-card ho-kpi-credit">
                        <div class="ho-kpi-top">
                            <div class="ho-kpi-icon"><ClockCircleOutlined /></div>
                            <div class="ho-kpi-badge">Receivable</div>
                        </div>
                        <div class="ho-kpi-label">Credit Sales</div>
                        <div class="ho-kpi-value">{{ fmt(data.kpi?.credit_sales) }}</div>
                        <div class="ho-kpi-sub">Outstanding dues</div>
                    </div>
                    <div class="ho-kpi-card ho-kpi-advance">
                        <div class="ho-kpi-top">
                            <div class="ho-kpi-icon"><WalletOutlined /></div>
                            <div class="ho-kpi-badge">Unallocated</div>
                        </div>
                        <div class="ho-kpi-label">Advance Payments</div>
                        <div class="ho-kpi-value">{{ fmt(data.kpi?.advance_payments) }}</div>
                        <div class="ho-kpi-sub">Unused payment balance</div>
                    </div>
                </div>

                <!-- ── Charts Row ────────────────────────────────────────── -->
                <div class="ho-charts-grid no-print">
                    <!-- Bar: Store-wise -->
                    <div class="ho-chart-card ho-chart-bar">
                        <div class="ho-chart-header">
                            <BarChartOutlined class="ho-chart-hicon" />
                            <span>Store-wise Sales Comparison</span>
                        </div>
                        <div class="ho-chart-body">
                            <BarChart v-if="storeBarData.labels?.length" ref="barRef"
                                :chartData="storeBarData" :options="barOptions" />
                            <a-empty v-else description="No data" style="margin-top:40px" />
                        </div>
                    </div>

                    <!-- Line: Daily Trend -->
                    <div class="ho-chart-card ho-chart-line">
                        <div class="ho-chart-header">
                            <LineChartOutlined class="ho-chart-hicon" />
                            <span>Daily Sales Trend</span>
                        </div>
                        <div class="ho-chart-body">
                            <LineChart v-if="dailyLineData.labels?.length" ref="lineRef"
                                :chartData="dailyLineData" :options="lineOptions" />
                            <a-empty v-else description="No data" style="margin-top:40px" />
                        </div>
                    </div>

                    <!-- Doughnut: Payment Modes -->
                    <div class="ho-chart-card ho-chart-pie">
                        <div class="ho-chart-header">
                            <PieChartOutlined class="ho-chart-hicon" />
                            <span>Payment Mode Distribution</span>
                        </div>
                        <div class="ho-chart-body">
                            <DoughnutChart v-if="paymentPieData.labels?.length" ref="pieRef"
                                :chartData="paymentPieData" :options="pieOptions" />
                            <a-empty v-else description="No data" style="margin-top:40px" />
                        </div>
                    </div>
                </div>

                <!-- ── Store-wise Table ──────────────────────────────────── -->
                <div class="ho-section-card" style="margin-bottom:20px">
                    <div class="ho-section-header ho-header-stores">
                        <div class="ho-section-title">
                            <ShopOutlined class="ho-sicon" />
                            <span>STORE-WISE BREAKDOWN</span>
                        </div>
                        <div class="ho-section-meta">{{ (data.store_wise || []).length }} stores</div>
                    </div>
                    <div class="ho-table-wrap">
                        <table class="ho-table">
                            <thead>
                                <tr>
                                    <th class="ho-th-rank">#</th>
                                    <th class="ho-th-name">Store Name</th>
                                    <th class="ho-th-num">Total Sales</th>
                                    <th class="ho-th-num">Orders</th>
                                    <th class="ho-th-num">Cash Sales</th>
                                    <th class="ho-th-num">Credit Sales</th>
                                    <th class="ho-th-num">Advance</th>
                                    <th class="ho-th-pct">% Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!data.store_wise?.length">
                                    <td colspan="8" class="ho-empty">No store data for this period</td>
                                </tr>
                                <tr v-for="(s, i) in data.store_wise" :key="i"
                                    class="ho-row" :class="i === 0 ? 'ho-row-top' : ''">
                                    <td class="ho-td-rank">
                                        <span class="ho-rank-badge" :class="`ho-rank-${i+1}`">{{ i + 1 }}</span>
                                    </td>
                                    <td class="ho-td-name">
                                        <span class="ho-store-dot" :style="{ background: storeColors[i % storeColors.length] }"></span>
                                        {{ s.store_name }}
                                    </td>
                                    <td class="ho-td-num ho-num-sales">{{ fmt(s.total_sales) }}</td>
                                    <td class="ho-td-num">{{ s.total_orders }}</td>
                                    <td class="ho-td-num ho-num-cash">{{ fmt(s.cash_sales) }}</td>
                                    <td class="ho-td-num ho-num-credit">{{ fmt(s.credit_sales) }}</td>
                                    <td class="ho-td-num ho-num-advance">{{ fmt(s.advance_sales) }}</td>
                                    <td class="ho-td-pct">
                                        <div class="ho-pct-bar-wrap">
                                            <div class="ho-pct-bar" :style="{ width: shareOf(s.total_sales) + '%', background: storeColors[i % storeColors.length] }"></div>
                                            <span class="ho-pct-label">{{ shareOf(s.total_sales) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="data.store_wise?.length">
                                <tr class="ho-totals-row">
                                    <td colspan="2" class="ho-totals-label">TOTAL (All Stores)</td>
                                    <td class="ho-totals-num">{{ fmt(data.kpi?.total_sales) }}</td>
                                    <td class="ho-totals-num">{{ data.kpi?.total_orders }}</td>
                                    <td class="ho-totals-num">{{ fmt(totalCash) }}</td>
                                    <td class="ho-totals-num">{{ fmt(data.kpi?.credit_sales) }}</td>
                                    <td class="ho-totals-num">{{ fmt(data.kpi?.advance_payments) }}</td>
                                    <td class="ho-totals-num">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ── Top 10 Products ───────────────────────────────────── -->
                <div class="ho-top-products-grid">

                    <!-- Products Table -->
                    <div class="ho-section-card">
                        <div class="ho-section-header ho-header-products">
                            <div class="ho-section-title">
                                <TrophyOutlined class="ho-sicon" />
                                <span>TOP 10 SELLING PRODUCTS</span>
                            </div>
                            <div class="ho-section-meta">All stores · by quantity</div>
                        </div>
                        <div class="ho-table-wrap">
                            <table class="ho-table">
                                <thead>
                                    <tr>
                                        <th class="ho-th-rank">#</th>
                                        <th>Product</th>
                                        <th class="ho-th-code">SKU</th>
                                        <th class="ho-th-num">Qty Sold</th>
                                        <th class="ho-th-num">Revenue (PKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!data.top_products?.length">
                                        <td colspan="5" class="ho-empty">No sales data for this period</td>
                                    </tr>
                                    <tr v-for="(p, i) in data.top_products" :key="i"
                                        class="ho-row ho-product-row" :class="i === 0 ? 'ho-product-top' : ''">
                                        <td class="ho-td-rank">
                                            <span class="ho-rank-badge" :class="`ho-rank-${i+1}`">{{ i + 1 }}</span>
                                        </td>
                                        <td class="ho-product-name">
                                            <span v-if="i === 0" class="ho-crown">👑</span>
                                            {{ p.product_name }}
                                        </td>
                                        <td class="ho-td-code">
                                            <span class="ho-code-badge">{{ p.item_code || '—' }}</span>
                                        </td>
                                        <td class="ho-td-num">
                                            <span class="ho-qty">{{ Number(p.total_qty).toLocaleString() }}</span>
                                        </td>
                                        <td class="ho-td-num ho-num-sales">{{ fmt(p.total_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Products Bar Chart -->
                    <div class="ho-section-card">
                        <div class="ho-section-header ho-header-products-chart">
                            <div class="ho-section-title">
                                <BarChartOutlined class="ho-sicon" />
                                <span>TOP PRODUCTS — QTY CHART</span>
                            </div>
                        </div>
                        <div class="ho-chart-body" style="padding:16px">
                            <BarChart v-if="productBarData.labels?.length" ref="productBarRef"
                                :chartData="productBarData" :options="productBarOptions" />
                            <a-empty v-else description="No data" style="margin-top:40px" />
                        </div>
                    </div>
                </div>

            </a-spin>
        </div>
    </template>
</template>

<script>
import { defineComponent, ref, computed, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import { useStore } from 'vuex';
import { BarChart, LineChart, DoughnutChart } from 'vue-chart-3';
import { Chart, registerables } from 'chart.js';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import {
    PrinterOutlined, ReloadOutlined, SearchOutlined,
    ShoppingOutlined, FileTextOutlined, DollarOutlined,
    ClockCircleOutlined, WalletOutlined,
    BarChartOutlined, LineChartOutlined, PieChartOutlined,
    ShopOutlined, TrophyOutlined,
} from '@ant-design/icons-vue';

Chart.register(...registerables);

const STORE_COLORS = [
    '#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6',
    '#06b6d4','#ec4899','#84cc16','#f97316','#14b8a6',
];

export default defineComponent({
    components: {
        AdminPageHeader, BarChart, LineChart, DoughnutChart,
        PrinterOutlined, ReloadOutlined, SearchOutlined,
        ShoppingOutlined, FileTextOutlined, DollarOutlined,
        ClockCircleOutlined, WalletOutlined,
        BarChartOutlined, LineChartOutlined, PieChartOutlined,
        ShopOutlined, TrophyOutlined,
    },

    setup() {
        const store   = useStore();
        const loading = ref(false);
        const activeQuick = ref('month');

        const isAdmin = computed(() => {
            const role = store.state.auth?.user?.role?.name;
            return role === 'admin' || role === 'ceo-vfjpb8pk';
        });

        const filters = ref({
            date_from: dayjs().startOf('month'),
            date_to:   dayjs(),
        });

        const data = ref({
            kpi:          { total_sales: 0, total_orders: 0, cash_received: 0, credit_sales: 0, advance_payments: 0 },
            store_wise:   [],
            daily_trend:  [],
            payment_modes:[],
            top_products: [],
            date_from:    '',
            date_to:      '',
        });

        const quickFilters = [
            { key: 'today',   label: 'Today' },
            { key: 'week',    label: 'This Week' },
            { key: 'month',   label: 'This Month' },
            { key: 'quarter', label: 'This Quarter' },
            { key: 'year',    label: 'This Year' },
        ];

        const setQuick = (k) => {
            activeQuick.value = k;
            const now = dayjs();
            const map = {
                today:   [now.startOf('day'),     now.endOf('day')],
                week:    [now.startOf('week'),    now.endOf('week')],
                month:   [now.startOf('month'),   now.endOf('month')],
                quarter: [now.startOf('quarter'), now.endOf('quarter')],
                year:    [now.startOf('year'),    now.endOf('year')],
            };
            [filters.value.date_from, filters.value.date_to] = map[k];
            load();
        };

        const fmt     = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const fmtDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const storeColors = STORE_COLORS;

        const totalSales = computed(() => Number(data.value.kpi?.total_sales || 0));
        const totalCash  = computed(() => (data.value.store_wise || []).reduce((s, r) => s + Number(r.cash_sales || 0), 0));

        const fmtAvg = computed(() => {
            const ord = Number(data.value.kpi?.total_orders || 0);
            const sal = totalSales.value;
            if (!ord) return '0.00';
            return fmt(sal / ord);
        });

        const shareOf = (v) => {
            const t = totalSales.value;
            if (!t) return '0.0';
            return ((Number(v || 0) / t) * 100).toFixed(1);
        };

        // ── Charts ──────────────────────────────────────────────────────────

        // Store bar chart
        const storeBarData = computed(() => {
            const stores = data.value.store_wise || [];
            return {
                labels: stores.map(s => s.store_name.length > 18 ? s.store_name.substring(0, 18) + '…' : s.store_name),
                datasets: [{
                    label: 'Total Sales (PKR)',
                    data: stores.map(s => Number(s.total_sales || 0)),
                    backgroundColor: STORE_COLORS.slice(0, stores.length),
                    borderRadius: 6,
                    borderSkipped: false,
                }],
            };
        });
        const barOptions = ref({
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: {
                label: (ctx) => ' PKR ' + Number(ctx.raw).toLocaleString('en-PK', { minimumFractionDigits: 2 }),
            }}},
            scales: { y: { ticks: { callback: (v) => 'PKR ' + Number(v).toLocaleString('en-PK') } } },
        });

        // Daily line chart
        const dailyLineData = computed(() => {
            const days = data.value.daily_trend || [];
            return {
                labels: days.map(d => dayjs(d.date).format('DD MMM')),
                datasets: [{
                    label: 'Sales (PKR)',
                    data: days.map(d => d.total),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,.12)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 4,
                }],
            };
        });
        const lineOptions = ref({
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: {
                label: (ctx) => ' PKR ' + Number(ctx.raw).toLocaleString('en-PK', { minimumFractionDigits: 2 }),
            }}},
            scales: { y: { ticks: { callback: (v) => 'PKR ' + Number(v).toLocaleString('en-PK') } } },
        });

        // Payment mode pie
        const paymentPieData = computed(() => {
            const modes = data.value.payment_modes || [];
            return {
                labels: modes.map(m => m.mode || 'Unknown'),
                datasets: [{
                    data: modes.map(m => Number(m.total || 0)),
                    backgroundColor: STORE_COLORS.slice(0, modes.length),
                    hoverOffset: 8,
                }],
            };
        });
        const pieOptions = ref({
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: {
                    label: (ctx) => ' ' + ctx.label + ': PKR ' + Number(ctx.raw).toLocaleString('en-PK', { minimumFractionDigits: 2 }),
                }},
            },
        });

        // Products bar chart (horizontal)
        const productBarData = computed(() => {
            const prods = data.value.top_products || [];
            return {
                labels: prods.map(p => p.product_name.length > 22 ? p.product_name.substring(0, 22) + '…' : p.product_name),
                datasets: [{
                    label: 'Qty Sold',
                    data: prods.map(p => Number(p.total_qty || 0)),
                    backgroundColor: STORE_COLORS,
                    borderRadius: 5,
                    borderSkipped: false,
                }],
            };
        });
        const productBarOptions = ref({
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { ticks: { precision: 0 } } },
        });

        // ── Load ─────────────────────────────────────────────────────────────
        const load = async () => {
            if (!isAdmin.value) return;
            loading.value = true;
            try {
                const res = await window.axiosAdmin.get('ho-dashboard', {
                    params: {
                        date_from: filters.value.date_from?.format('YYYY-MM-DD'),
                        date_to:   filters.value.date_to?.format('YYYY-MM-DD'),
                    },
                });
                data.value = res.data;
            } catch (e) {
                /* silent */
            } finally {
                loading.value = false;
            }
        };

        const print = () => window.print();

        onMounted(() => { if (isAdmin.value) load(); });

        return {
            loading, isAdmin, filters, data, activeQuick,
            quickFilters, storeColors, totalCash, fmtAvg,
            storeBarData, barOptions,
            dailyLineData, lineOptions,
            paymentPieData, pieOptions,
            productBarData, productBarOptions,
            fmt, fmtDate, shareOf, setQuick, load, print,
        };
    },
});
</script>

<style scoped>
/* ── Page Wrap ────────────────────────────────────────────────────────────── */
.ho-page-wrap {
    padding: 0 24px 56px;
    background: linear-gradient(150deg, #f0f6ff 0%, #fdf4ff 50%, #f0fff8 100%);
    min-height: 100%;
}

/* ── Access Denied ────────────────────────────────────────────────────────── */
.ho-access-denied {
    padding: 80px 24px;
    text-align: center;
}

/* ── Filter Bar ───────────────────────────────────────────────────────────── */
.ho-filter-bar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.ho-filter-inner {
    display: flex; align-items: flex-end; justify-content: space-between;
    gap: 16px; flex-wrap: wrap;
}
.ho-filter-left { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
.ho-field-group { display: flex; flex-direction: column; gap: 4px; }
.ho-label {
    font-size: 11px; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .5px;
}
.ho-datepicker { width: 185px !important; }
.ho-generate-btn {
    height: 36px !important; padding: 0 20px !important;
    border-radius: 8px !important; font-weight: 700 !important;
    background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
    border: none !important; box-shadow: 0 4px 14px rgba(99,102,241,.35) !important;
    transition: all .15s !important;
}
.ho-generate-btn:hover {
    background: linear-gradient(135deg, #818cf8, #6366f1) !important;
    transform: translateY(-1px) !important;
}
.ho-btn {
    height: 34px !important; border-radius: 8px !important; font-weight: 600 !important;
}
.ho-btn-outline {
    height: 34px !important; border-radius: 8px !important; font-weight: 600 !important;
    border-color: #cbd5e1 !important; color: #475569 !important;
}

.ho-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ho-quick-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.ho-quick-btn {
    padding: 4px 13px; border-radius: 20px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    color: #475569; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: all .15s;
}
.ho-quick-btn:hover, .ho-quick-btn.active {
    border-color: #6366f1; color: #4f46e5;
    background: #eef2ff; box-shadow: 0 3px 8px rgba(99,102,241,.2);
}

/* ── Print Header ─────────────────────────────────────────────────────────── */
.ho-print-header { display: none; }

/* ── KPI Cards ────────────────────────────────────────────────────────────── */
.ho-kpi-grid {
    display: grid; grid-template-columns: repeat(5, 1fr);
    gap: 16px; margin-bottom: 20px;
}
.ho-kpi-card {
    border-radius: 16px; padding: 18px 20px;
    position: relative; overflow: hidden;
    transition: transform .2s, box-shadow .2s; cursor: default;
    color: #fff;
}
.ho-kpi-card:hover { transform: translateY(-3px); }
.ho-kpi-card::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 50%;
    background: linear-gradient(180deg, rgba(255,255,255,.2) 0%, rgba(255,255,255,0) 100%);
    border-radius: 16px 16px 0 0; pointer-events: none;
}
.ho-kpi-sales   { background: linear-gradient(145deg, #818cf8, #4f46e5, #3730a3); box-shadow: 0 8px 24px rgba(99,102,241,.35); }
.ho-kpi-orders  { background: linear-gradient(145deg, #34d399, #059669, #047857); box-shadow: 0 8px 24px rgba(5,150,105,.35); }
.ho-kpi-cash    { background: linear-gradient(145deg, #fbbf24, #d97706, #b45309); box-shadow: 0 8px 24px rgba(217,119,6,.35); }
.ho-kpi-credit  { background: linear-gradient(145deg, #f87171, #dc2626, #b91c1c); box-shadow: 0 8px 24px rgba(220,38,38,.35); }
.ho-kpi-advance { background: linear-gradient(145deg, #c084fc, #9333ea, #7e22ce); box-shadow: 0 8px 24px rgba(147,51,234,.35); }

.ho-kpi-top {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 12px;
}
.ho-kpi-icon {
    width: 40px; height: 40px; border-radius: 11px;
    background: rgba(0,0,0,.16);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.ho-kpi-badge {
    padding: 2px 9px; border-radius: 20px;
    background: rgba(0,0,0,.18); color: rgba(255,255,255,.9);
    font-size: 10px; font-weight: 700;
}
.ho-kpi-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: rgba(255,255,255,.8); margin-bottom: 5px;
}
.ho-kpi-value {
    font-size: 17px; font-weight: 900; color: #fff;
    font-family: 'Courier New', monospace; line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0,0,0,.2);
}
.ho-kpi-sub { font-size: 10px; color: rgba(255,255,255,.6); margin-top: 5px; }

/* ── Charts Grid ──────────────────────────────────────────────────────────── */
.ho-charts-grid {
    display: grid; grid-template-columns: 2fr 2fr 1.2fr;
    gap: 16px; margin-bottom: 20px;
}
.ho-chart-card {
    background: #fff; border-radius: 14px; overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.08); border: 1px solid #e8ecf5;
    transition: box-shadow .2s;
}
.ho-chart-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.12); }
.ho-chart-header {
    display: flex; align-items: center; gap: 8px;
    padding: 13px 16px; border-bottom: 1px solid #f0f4f8;
    font-size: 12px; font-weight: 800; color: #334155;
    text-transform: uppercase; letter-spacing: .5px;
}
.ho-chart-hicon { color: #6366f1; font-size: 15px; }
.ho-chart-body { padding: 16px; }

/* ── Section Cards ────────────────────────────────────────────────────────── */
.ho-section-card {
    background: #fff; border-radius: 14px; overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.08); border: 1px solid #e8ecf5;
    transition: box-shadow .2s;
}
.ho-section-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.12); }

.ho-section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 13px 20px; color: #fff;
    position: relative; overflow: hidden;
}
.ho-section-header::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 45%;
    background: linear-gradient(180deg, rgba(255,255,255,.18) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}
.ho-section-title {
    display: flex; align-items: center; gap: 9px;
    font-size: 13px; font-weight: 800; letter-spacing: .5px;
    text-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.ho-sicon { font-size: 16px; }
.ho-section-meta {
    font-size: 11px; color: rgba(255,255,255,.8);
    background: rgba(0,0,0,.18); padding: 2px 10px; border-radius: 20px; font-weight: 600;
}

.ho-header-stores        { background: linear-gradient(135deg, #6366f1, #4f46e5); }
.ho-header-products      { background: linear-gradient(135deg, #f59e0b, #b45309); }
.ho-header-products-chart{ background: linear-gradient(135deg, #10b981, #047857); }

/* ── Tables ───────────────────────────────────────────────────────────────── */
.ho-table-wrap { overflow-x: auto; }
.ho-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ho-table thead tr {
    background: linear-gradient(180deg, #f8fafc, #f1f5f9);
    border-bottom: 2px solid #e2e8f0;
}
.ho-table th {
    padding: 10px 14px; font-size: 10px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .5px; color: #64748b;
    white-space: nowrap;
}
.ho-th-rank  { width: 48px; text-align: center; }
.ho-th-num   { width: 130px; text-align: right; }
.ho-th-pct   { width: 150px; }
.ho-th-code  { width: 100px; }
.ho-th-name  { text-align: left; }

.ho-row { border-bottom: 1px solid #f0f4f8; transition: background .12s, transform .12s; }
.ho-row:hover { background: #f8fafc; transform: translateX(3px); box-shadow: -3px 0 0 #6366f1; }
.ho-row-top { background: #fefce8; }
.ho-row-top:hover { background: #fef9c3; box-shadow: -3px 0 0 #f59e0b; }

.ho-product-row:hover { box-shadow: -3px 0 0 #f59e0b; }
.ho-product-top { background: linear-gradient(90deg, #fefce8, #fff); }

.ho-td-rank { padding: 11px 14px; text-align: center; }
.ho-td-name { padding: 11px 14px; color: #334155; font-weight: 600; }
.ho-td-num  { padding: 11px 14px; text-align: right; font-family: 'Courier New', monospace; font-weight: 700; }
.ho-td-pct  { padding: 8px 14px; }
.ho-td-code { padding: 11px 14px; }

.ho-rank-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 50%;
    font-size: 11px; font-weight: 800; color: #fff;
}
.ho-rank-1 { background: linear-gradient(135deg, #fbbf24, #d97706); box-shadow: 0 2px 6px rgba(217,119,6,.4); }
.ho-rank-2 { background: linear-gradient(135deg, #94a3b8, #64748b); }
.ho-rank-3 { background: linear-gradient(135deg, #fb923c, #ea580c); }
.ho-rank-4, .ho-rank-5, .ho-rank-6, .ho-rank-7, .ho-rank-8, .ho-rank-9, .ho-rank-10 {
    background: #e2e8f0; color: #475569;
}

.ho-num-sales  { color: #4f46e5; }
.ho-num-cash   { color: #047857; }
.ho-num-credit { color: #b91c1c; }
.ho-num-advance{ color: #7e22ce; }

.ho-store-dot {
    display: inline-block; width: 10px; height: 10px; border-radius: 50%;
    margin-right: 8px; flex-shrink: 0; vertical-align: middle;
}

.ho-pct-bar-wrap {
    display: flex; align-items: center; gap: 8px;
}
.ho-pct-bar {
    height: 8px; border-radius: 4px;
    max-width: 100%; transition: width .3s;
    min-width: 4px;
}
.ho-pct-label { font-size: 11px; font-weight: 700; color: #475569; white-space: nowrap; }

.ho-totals-row {
    background: linear-gradient(90deg, #eef2ff, #f8fafc);
    border-top: 2px solid #e2e8f0;
}
.ho-totals-label {
    padding: 13px 14px; font-size: 12px; font-weight: 900; color: #1e293b;
    text-align: right; text-transform: uppercase; letter-spacing: .5px;
}
.ho-totals-num {
    padding: 13px 14px; text-align: right;
    font-weight: 900; font-size: 13px;
    font-family: 'Courier New', monospace; color: #1e293b;
}

.ho-empty {
    text-align: center; padding: 32px;
    color: #94a3b8; font-style: italic; font-size: 13px;
}

.ho-product-name { padding: 11px 14px; color: #334155; font-weight: 600; }
.ho-crown { font-size: 14px; margin-right: 4px; }
.ho-qty { font-size: 14px; font-weight: 800; color: #4f46e5; }
.ho-code-badge {
    display: inline-block; padding: 2px 8px; border-radius: 6px;
    background: #f1f5f9; color: #475569;
    font-family: 'Courier New', monospace; font-size: 11px; font-weight: 700;
    border: 1px solid #e2e8f0;
}

/* ── Top Products Layout ──────────────────────────────────────────────────── */
.ho-top-products-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
}

/* ── Print ────────────────────────────────────────────────────────────────── */
@media print {
    .no-print   { display: none !important; }
    .ho-page-wrap { padding: 0 !important; background: #fff !important; }
    .ho-print-header { display: block; text-align: center; margin-bottom: 20px; }
    .ho-print-header h1 { font-size: 20px; margin: 0; }
    .ho-section-card, .ho-chart-card { box-shadow: none !important; border: 1px solid #ccc !important; page-break-inside: avoid; }
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 1400px) { .ho-kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 1100px) {
    .ho-kpi-grid       { grid-template-columns: repeat(2, 1fr); }
    .ho-charts-grid    { grid-template-columns: 1fr 1fr; }
    .ho-top-products-grid { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
    .ho-kpi-grid    { grid-template-columns: 1fr; }
    .ho-charts-grid { grid-template-columns: 1fr; }
    .ho-page-wrap   { padding: 0 12px 36px; }
    .ho-filter-bar  { padding: 12px 16px; }
    .ho-filter-inner { flex-direction: column; align-items: flex-start; }
    .ho-datepicker { width: 100% !important; }
}
</style>
