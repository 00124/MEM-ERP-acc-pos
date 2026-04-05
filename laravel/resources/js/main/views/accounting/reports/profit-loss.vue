<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Profit & Loss Statement" class="p-0">
                <template #extra>
                    <a-button @click="exportPdf" class="pl-btn pl-btn-outline">
                        <FilePdfOutlined /> Export PDF
                    </a-button>
                    <a-button @click="print" type="primary" class="pl-btn">
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
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Profit & Loss</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- ── Filter Bar ─────────────────────────────────────────────────────── -->
    <div class="pl-filter-bar no-print">
        <div class="pl-filter-inner">
            <div class="pl-filter-fields">
                <div class="pl-field-group">
                    <label class="pl-label">From Date</label>
                    <a-date-picker v-model:value="filters.date_from" class="pl-datepicker" />
                </div>
                <div class="pl-field-group">
                    <label class="pl-label">To Date</label>
                    <a-date-picker v-model:value="filters.date_to" class="pl-datepicker" />
                </div>
                <a-button type="primary" :loading="loading" @click="load" class="pl-generate-btn">
                    <SearchOutlined />
                    <span>Generate Report</span>
                </a-button>
            </div>
            <div class="pl-quick-filters">
                <span class="pl-quick-label">Quick:</span>
                <button class="pl-quick-btn" @click="setQuick('today')">Today</button>
                <button class="pl-quick-btn" @click="setQuick('week')">This Week</button>
                <button class="pl-quick-btn" @click="setQuick('month')">This Month</button>
                <button class="pl-quick-btn" @click="setQuick('quarter')">This Quarter</button>
                <button class="pl-quick-btn" @click="setQuick('year')">This Year</button>
            </div>
        </div>
    </div>

    <!-- ── Main Content ───────────────────────────────────────────────────── -->
    <div class="pl-page-wrap">
        <a-spin :spinning="loading">
            <div id="printable-area">

                <!-- Print Header -->
                <div class="pl-print-header">
                    <h1>Profit &amp; Loss Statement</h1>
                    <p>{{ filters.date_from?.format('DD MMM YYYY') }} &ndash; {{ filters.date_to?.format('DD MMM YYYY') }}</p>
                </div>

                <!-- ── Summary Cards ──────────────────────────────────────── -->
                <div class="pl-summary-grid no-print">

                    <!-- Revenue -->
                    <div class="pl-stat-card pl-stat-revenue">
                        <div class="pl-stat-top">
                            <div class="pl-stat-icon-wrap"><RiseOutlined /></div>
                            <div class="pl-stat-badge">{{ incomeRows.length }} accounts</div>
                        </div>
                        <div class="pl-stat-label">Total Revenue</div>
                        <div class="pl-stat-value">{{ fmt(data.total_revenue) }}</div>
                        <div class="pl-stat-sub">Income from all sources</div>
                    </div>

                    <!-- COGS -->
                    <div class="pl-stat-card pl-stat-cogs">
                        <div class="pl-stat-top">
                            <div class="pl-stat-icon-wrap"><ShoppingCartOutlined /></div>
                            <div class="pl-stat-badge">{{ cogsRows.length }} accounts</div>
                        </div>
                        <div class="pl-stat-label">Cost of Goods Sold</div>
                        <div class="pl-stat-value">{{ fmt(data.total_cogs) }}</div>
                        <div class="pl-stat-sub">Direct production costs</div>
                    </div>

                    <!-- Gross Profit -->
                    <div class="pl-stat-card" :class="data.gross_profit >= 0 ? 'pl-stat-gross-profit' : 'pl-stat-gross-loss'">
                        <div class="pl-stat-top">
                            <div class="pl-stat-icon-wrap"><BarChartOutlined /></div>
                            <div class="pl-stat-badge">{{ grossMargin }}% margin</div>
                        </div>
                        <div class="pl-stat-label">Gross Profit</div>
                        <div class="pl-stat-value">{{ fmt(data.gross_profit) }}</div>
                        <div class="pl-stat-sub">Revenue minus COGS</div>
                    </div>

                    <!-- Net Profit -->
                    <div class="pl-stat-card" :class="data.net_profit >= 0 ? 'pl-stat-net-profit' : 'pl-stat-net-loss'">
                        <div class="pl-stat-top">
                            <div class="pl-stat-icon-wrap">
                                <TrophyOutlined v-if="data.net_profit >= 0" />
                                <WarningOutlined v-else />
                            </div>
                            <div class="pl-stat-badge">{{ data.net_profit >= 0 ? 'Profit' : 'Loss' }}</div>
                        </div>
                        <div class="pl-stat-label">Net Profit / Loss</div>
                        <div class="pl-stat-value">{{ fmt(data.net_profit) }}</div>
                        <div class="pl-stat-sub">After all expenses · {{ netMargin }}% margin</div>
                    </div>
                </div>

                <!-- ── Period Info Bar ────────────────────────────────────── -->
                <div class="pl-period-bar no-print">
                    <div class="pl-period-left">
                        <CalendarOutlined class="pl-period-icon" />
                        <span class="pl-period-date">
                            {{ formatDate(data.date_from) }} &ndash; {{ formatDate(data.date_to) }}
                        </span>
                    </div>
                    <div class="pl-period-metrics">
                        <div class="pl-metric-box pl-metric-revenue">
                            <span class="pl-metric-label">Revenue</span>
                            <span class="pl-metric-val">{{ fmt(data.total_revenue) }}</span>
                        </div>
                        <span class="pl-metric-sep">−</span>
                        <div class="pl-metric-box pl-metric-cogs">
                            <span class="pl-metric-label">COGS</span>
                            <span class="pl-metric-val">{{ fmt(data.total_cogs) }}</span>
                        </div>
                        <span class="pl-metric-sep">−</span>
                        <div class="pl-metric-box pl-metric-exp">
                            <span class="pl-metric-label">Expenses</span>
                            <span class="pl-metric-val">{{ fmt(data.total_expenses) }}</span>
                        </div>
                        <span class="pl-metric-sep">=</span>
                        <div class="pl-metric-box" :class="data.net_profit >= 0 ? 'pl-metric-profit' : 'pl-metric-loss'">
                            <span class="pl-metric-label">Net {{ data.net_profit >= 0 ? 'Profit' : 'Loss' }}</span>
                            <span class="pl-metric-val">{{ fmt(data.net_profit) }}</span>
                        </div>
                        <span class="pl-period-tag" :class="data.net_profit >= 0 ? 'pl-tag-profit' : 'pl-tag-loss'">
                            {{ data.net_profit >= 0 ? '▲ Profitable' : '▼ Loss Period' }}
                        </span>
                    </div>
                </div>

                <!-- ── Revenue Section ────────────────────────────────────── -->
                <div class="pl-section-card">
                    <div class="pl-section-header pl-header-revenue">
                        <div class="pl-section-title">
                            <RiseOutlined class="pl-section-icon" />
                            <span>REVENUE</span>
                        </div>
                        <div class="pl-section-right">
                            <span class="pl-section-count">{{ incomeRows.length }} accounts</span>
                            <span class="pl-section-total">{{ fmt(data.total_revenue) }}</span>
                        </div>
                    </div>
                    <div class="pl-table-wrap">
                        <table class="pl-table">
                            <thead>
                                <tr>
                                    <th class="pl-th-code">Account Code</th>
                                    <th class="pl-th-name">Account Name</th>
                                    <th class="pl-th-amount">Amount (PKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="incomeRows.length === 0">
                                    <td colspan="3" class="pl-empty">No revenue accounts for this period</td>
                                </tr>
                                <tr v-for="r in incomeRows" :key="r.account_code" class="pl-row pl-row-revenue">
                                    <td class="pl-td-code"><span class="pl-code-badge">{{ r.account_code }}</span></td>
                                    <td class="pl-td-name">{{ r.account_name }}</td>
                                    <td class="pl-td-amount pl-amount-green">{{ fmt(r.net) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="pl-subtotal-row pl-subtotal-revenue">
                                    <td colspan="2" class="pl-subtotal-label">
                                        <RiseOutlined /> &nbsp;Total Revenue
                                    </td>
                                    <td class="pl-subtotal-amount pl-amount-green">{{ fmt(data.total_revenue) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ── COGS Section ───────────────────────────────────────── -->
                <div class="pl-section-card">
                    <div class="pl-section-header pl-header-cogs">
                        <div class="pl-section-title">
                            <ShoppingCartOutlined class="pl-section-icon" />
                            <span>COST OF GOODS SOLD</span>
                        </div>
                        <div class="pl-section-right">
                            <span class="pl-section-count">{{ cogsRows.length }} accounts</span>
                            <span class="pl-section-total">{{ fmt(data.total_cogs) }}</span>
                        </div>
                    </div>
                    <div class="pl-table-wrap">
                        <table class="pl-table">
                            <thead>
                                <tr>
                                    <th class="pl-th-code">Account Code</th>
                                    <th class="pl-th-name">Account Name</th>
                                    <th class="pl-th-amount">Amount (PKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="cogsRows.length === 0">
                                    <td colspan="3" class="pl-empty">No COGS accounts for this period</td>
                                </tr>
                                <tr v-for="r in cogsRows" :key="r.account_code" class="pl-row pl-row-cogs">
                                    <td class="pl-td-code"><span class="pl-code-badge">{{ r.account_code }}</span></td>
                                    <td class="pl-td-name">{{ r.account_name }}</td>
                                    <td class="pl-td-amount pl-amount-orange">{{ fmt(Math.abs(r.net)) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="pl-subtotal-row pl-subtotal-cogs">
                                    <td colspan="2" class="pl-subtotal-label">
                                        <ShoppingCartOutlined /> &nbsp;Total COGS
                                    </td>
                                    <td class="pl-subtotal-amount pl-amount-orange">{{ fmt(data.total_cogs) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ── Gross Profit Banner ────────────────────────────────── -->
                <div class="pl-mid-banner" :class="data.gross_profit >= 0 ? 'pl-banner-profit' : 'pl-banner-loss'">
                    <div class="pl-banner-left">
                        <div class="pl-banner-icon">
                            <BarChartOutlined />
                        </div>
                        <div>
                            <div class="pl-banner-title">Gross Profit</div>
                            <div class="pl-banner-sub">Revenue &minus; Cost of Goods Sold</div>
                        </div>
                    </div>
                    <div class="pl-banner-right">
                        <div class="pl-banner-margin-label">Gross Margin</div>
                        <div class="pl-banner-margin">{{ grossMargin }}%</div>
                        <div class="pl-banner-amount">{{ fmt(data.gross_profit) }}</div>
                    </div>
                </div>

                <!-- ── Operating Expenses Section ─────────────────────────── -->
                <div class="pl-section-card">
                    <div class="pl-section-header pl-header-expense">
                        <div class="pl-section-title">
                            <MinusCircleOutlined class="pl-section-icon" />
                            <span>OPERATING EXPENSES</span>
                        </div>
                        <div class="pl-section-right">
                            <span class="pl-section-count">{{ expenseRows.length }} accounts</span>
                            <span class="pl-section-total">{{ fmt(data.total_expenses) }}</span>
                        </div>
                    </div>
                    <div class="pl-table-wrap">
                        <table class="pl-table">
                            <thead>
                                <tr>
                                    <th class="pl-th-code">Account Code</th>
                                    <th class="pl-th-name">Account Name</th>
                                    <th class="pl-th-amount">Amount (PKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="expenseRows.length === 0">
                                    <td colspan="3" class="pl-empty">No expense accounts for this period</td>
                                </tr>
                                <tr v-for="r in expenseRows" :key="r.account_code" class="pl-row pl-row-expense">
                                    <td class="pl-td-code"><span class="pl-code-badge">{{ r.account_code }}</span></td>
                                    <td class="pl-td-name">{{ r.account_name }}</td>
                                    <td class="pl-td-amount pl-amount-purple">{{ fmt(Math.abs(r.net)) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="pl-subtotal-row pl-subtotal-expense">
                                    <td colspan="2" class="pl-subtotal-label">
                                        <MinusCircleOutlined /> &nbsp;Total Expenses
                                    </td>
                                    <td class="pl-subtotal-amount pl-amount-purple">{{ fmt(data.total_expenses) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ── Net Profit Final Banner ────────────────────────────── -->
                <div class="pl-net-banner" :class="data.net_profit >= 0 ? 'pl-net-profit' : 'pl-net-loss'">
                    <div class="pl-net-left">
                        <div class="pl-net-icon">
                            <TrophyOutlined v-if="data.net_profit >= 0" />
                            <WarningOutlined v-else />
                        </div>
                        <div>
                            <div class="pl-net-title">NET PROFIT / LOSS</div>
                            <div class="pl-net-formula">Gross Profit &minus; Operating Expenses</div>
                            <div class="pl-net-period">
                                Period: {{ formatDate(data.date_from) }} to {{ formatDate(data.date_to) }}
                            </div>
                        </div>
                    </div>
                    <div class="pl-net-right">
                        <div class="pl-net-margin-label">Net Margin</div>
                        <div class="pl-net-margin">{{ netMargin }}%</div>
                        <div class="pl-net-amount">{{ fmt(data.net_profit) }}</div>
                        <div class="pl-net-status">{{ data.net_profit >= 0 ? '▲ PROFIT' : '▼ LOSS' }}</div>
                    </div>
                </div>

            </div><!-- /#printable-area -->
        </a-spin>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import dayjs from 'dayjs';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import {
    PrinterOutlined,
    SearchOutlined,
    FilePdfOutlined,
    RiseOutlined,
    ShoppingCartOutlined,
    BarChartOutlined,
    DollarOutlined,
    CalendarOutlined,
    MinusCircleOutlined,
    TrophyOutlined,
    WarningOutlined,
} from '@ant-design/icons-vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        PrinterOutlined, SearchOutlined, FilePdfOutlined,
        RiseOutlined, ShoppingCartOutlined, BarChartOutlined, DollarOutlined,
        CalendarOutlined, MinusCircleOutlined, TrophyOutlined, WarningOutlined,
    },

    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading = ref(false);

        const filters = ref({
            date_from: dayjs().startOf('year'),
            date_to: dayjs(),
        });

        const data = ref({
            data: [],
            total_revenue: 0,
            total_cogs: 0,
            gross_profit: 0,
            total_expenses: 0,
            net_profit: 0,
            date_from: '',
            date_to: '',
        });

        const incomeRows  = computed(() => (data.value.data || []).filter(r => r.account_type === 'Income'));
        const cogsRows    = computed(() => (data.value.data || []).filter(r => r.account_type === 'COGS'));
        const expenseRows = computed(() => (data.value.data || []).filter(r => r.account_type === 'Expense'));

        const grossMargin = computed(() => {
            const rev = Number(data.value.total_revenue) || 0;
            if (rev === 0) return '0.00';
            return ((Number(data.value.gross_profit) / rev) * 100).toFixed(2);
        });

        const netMargin = computed(() => {
            const rev = Number(data.value.total_revenue) || 0;
            if (rev === 0) return '0.00';
            return ((Number(data.value.net_profit) / rev) * 100).toFixed(2);
        });

        const fmt        = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        const setQuick = (range) => {
            const now = dayjs();
            const presets = {
                today:   [now.startOf('day'),     now.endOf('day')],
                week:    [now.startOf('week'),    now.endOf('week')],
                month:   [now.startOf('month'),   now.endOf('month')],
                quarter: [now.startOf('quarter'), now.endOf('quarter')],
                year:    [now.startOf('year'),    now.endOf('year')],
            };
            [filters.value.date_from, filters.value.date_to] = presets[range];
            load();
        };

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/profit-loss', {
                    params: {
                        date_from: filters.value.date_from?.format('YYYY-MM-DD'),
                        date_to:   filters.value.date_to?.format('YYYY-MM-DD'),
                    },
                });
                data.value = res.data;
            } catch (e) { /* silent */ } finally {
                loading.value = false;
            }
        };

        const print     = () => window.print();
        const exportPdf = () => window.print();

        onMounted(load);

        return {
            loading, filters, data,
            incomeRows, cogsRows, expenseRows,
            grossMargin, netMargin,
            fmt, formatDate,
            load, setQuick, print, exportPdf,
        };
    },
});
</script>

<style scoped>
/* ── Page Wrap ────────────────────────────────────────────────────────────── */
.pl-page-wrap {
    padding: 0 24px 48px;
    background: linear-gradient(150deg, #f0fff8 0%, #f0f6ff 50%, #fdf4ff 100%);
    min-height: 100%;
}

/* ── Filter Bar ───────────────────────────────────────────────────────────── */
.pl-filter-bar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.pl-filter-inner { max-width: 1200px; }
.pl-filter-fields {
    display: flex; align-items: flex-end; gap: 16px;
    flex-wrap: wrap; margin-bottom: 12px;
}
.pl-field-group { display: flex; flex-direction: column; gap: 4px; }
.pl-label {
    font-size: 11px; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .5px;
}
.pl-datepicker { width: 185px !important; }

.pl-generate-btn {
    height: 36px !important; padding: 0 20px !important;
    border-radius: 8px !important; font-weight: 700 !important;
    background: linear-gradient(135deg, #10b981, #059669) !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(5,150,105,.35) !important;
    transition: all .15s !important;
}
.pl-generate-btn:hover {
    background: linear-gradient(135deg, #34d399, #10b981) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 18px rgba(5,150,105,.45) !important;
}

.pl-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pl-quick-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.pl-quick-btn {
    padding: 4px 13px; border-radius: 20px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    color: #475569; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: all .15s;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.pl-quick-btn:hover {
    border-color: #10b981; color: #047857;
    background: #ecfdf5; box-shadow: 0 3px 8px rgba(16,185,129,.2);
    transform: translateY(-1px);
}
.pl-quick-btn:active { transform: translateY(1px); box-shadow: none; }

/* ── Header Buttons ───────────────────────────────────────────────────────── */
.pl-btn {
    height: 34px !important; border-radius: 8px !important; font-weight: 600 !important;
}
.pl-btn-outline { border-color: #cbd5e1 !important; color: #475569 !important; }

/* ── Summary Cards ────────────────────────────────────────────────────────── */
.pl-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}
.pl-stat-card {
    border-radius: 16px; padding: 20px;
    position: relative; overflow: hidden;
    transition: transform .2s, box-shadow .2s; cursor: default;
}
.pl-stat-card:hover { transform: translateY(-3px); }

/* Glass shine */
.pl-stat-card::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 50%;
    background: linear-gradient(180deg, rgba(255,255,255,.2) 0%, rgba(255,255,255,0) 100%);
    border-radius: 16px 16px 0 0; pointer-events: none;
}

.pl-stat-revenue {
    background: linear-gradient(145deg, #34d399 0%, #059669 55%, #047857 100%);
    box-shadow: 0 8px 24px rgba(5,150,105,.35);
}
.pl-stat-cogs {
    background: linear-gradient(145deg, #fbbf24 0%, #d97706 55%, #b45309 100%);
    box-shadow: 0 8px 24px rgba(217,119,6,.35);
}
.pl-stat-gross-profit {
    background: linear-gradient(145deg, #60a5fa 0%, #2563eb 55%, #1d4ed8 100%);
    box-shadow: 0 8px 24px rgba(37,99,235,.35);
}
.pl-stat-gross-loss {
    background: linear-gradient(145deg, #f87171 0%, #dc2626 55%, #b91c1c 100%);
    box-shadow: 0 8px 24px rgba(220,38,38,.35);
}
.pl-stat-net-profit {
    background: linear-gradient(145deg, #4ade80 0%, #16a34a 55%, #15803d 100%);
    box-shadow: 0 8px 24px rgba(22,163,74,.35);
}
.pl-stat-net-loss {
    background: linear-gradient(145deg, #f87171 0%, #dc2626 55%, #991b1b 100%);
    box-shadow: 0 8px 24px rgba(220,38,38,.4);
}

.pl-stat-top {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 14px;
}
.pl-stat-icon-wrap {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(0,0,0,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
}
.pl-stat-badge {
    padding: 3px 10px; border-radius: 20px;
    background: rgba(0,0,0,.18); color: rgba(255,255,255,.9);
    font-size: 11px; font-weight: 700;
}
.pl-stat-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: rgba(255,255,255,.8); margin-bottom: 6px;
}
.pl-stat-value {
    font-size: 20px; font-weight: 900; color: #fff;
    font-family: 'Courier New', monospace; line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0,0,0,.2);
}
.pl-stat-sub { font-size: 11px; color: rgba(255,255,255,.65); margin-top: 6px; }

/* ── Period Info Bar ──────────────────────────────────────────────────────── */
.pl-period-bar {
    display: flex; align-items: center; justify-content: space-between;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 12px 20px; margin-bottom: 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    flex-wrap: wrap; gap: 12px;
}
.pl-period-left { display: flex; align-items: center; gap: 8px; }
.pl-period-icon { color: #10b981; font-size: 17px; }
.pl-period-date { font-size: 13px; font-weight: 700; color: #1e293b; }

.pl-period-metrics {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.pl-metric-box {
    display: flex; flex-direction: column; align-items: center;
    padding: 7px 14px; border-radius: 10px; min-width: 100px;
    box-shadow: 0 2px 6px rgba(0,0,0,.07);
}
.pl-metric-revenue { background: #ecfdf5; border: 1.5px solid #a7f3d0; }
.pl-metric-cogs    { background: #fffbeb; border: 1.5px solid #fde68a; }
.pl-metric-exp     { background: #faf5ff; border: 1.5px solid #e9d5ff; }
.pl-metric-profit  { background: #dcfce7; border: 1.5px solid #86efac; }
.pl-metric-loss    { background: #fee2e2; border: 1.5px solid #fca5a5; }

.pl-metric-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #64748b;
}
.pl-metric-val {
    font-size: 12px; font-weight: 800; font-family: 'Courier New', monospace;
    margin-top: 2px;
}
.pl-metric-revenue .pl-metric-val { color: #047857; }
.pl-metric-cogs    .pl-metric-val { color: #b45309; }
.pl-metric-exp     .pl-metric-val { color: #7e22ce; }
.pl-metric-profit  .pl-metric-val { color: #15803d; }
.pl-metric-loss    .pl-metric-val { color: #b91c1c; }

.pl-metric-sep {
    font-size: 18px; font-weight: 900; color: #94a3b8; line-height: 1;
}
.pl-period-tag {
    padding: 5px 14px; border-radius: 20px;
    font-size: 12px; font-weight: 800;
}
.pl-tag-profit { background: #dcfce7; color: #15803d; }
.pl-tag-loss   { background: #fee2e2; color: #b91c1c; }

/* ── Section Cards ────────────────────────────────────────────────────────── */
.pl-section-card {
    background: #fff; border-radius: 14px; overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08); border: 1px solid #e8ecf5;
    transition: box-shadow .2s;
}
.pl-section-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.12); }

.pl-section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; color: #fff; font-weight: 800; font-size: 14px;
    position: relative; overflow: hidden;
}
.pl-section-header::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 45%;
    background: linear-gradient(180deg, rgba(255,255,255,.18) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}
.pl-section-title {
    display: flex; align-items: center; gap: 9px;
    text-shadow: 0 1px 3px rgba(0,0,0,.25); letter-spacing: .5px;
}
.pl-section-icon { font-size: 17px; }
.pl-section-right { display: flex; align-items: center; gap: 12px; }
.pl-section-count {
    font-size: 12px; font-weight: 600;
    background: rgba(0,0,0,.18); color: rgba(255,255,255,.9);
    padding: 2px 10px; border-radius: 20px;
}
.pl-section-total {
    font-size: 16px; font-weight: 900;
    font-family: 'Courier New', monospace;
    text-shadow: 0 1px 3px rgba(0,0,0,.25);
}

.pl-header-revenue { background: linear-gradient(135deg, #34d399, #047857); }
.pl-header-cogs    { background: linear-gradient(135deg, #fbbf24, #b45309); }
.pl-header-expense { background: linear-gradient(135deg, #c084fc, #7e22ce); }

/* ── Tables ───────────────────────────────────────────────────────────────── */
.pl-table-wrap { overflow-x: auto; }
.pl-table { width: 100%; border-collapse: collapse; font-size: 13px; }

.pl-table thead tr {
    background: linear-gradient(180deg, #f8fafc, #f1f5f9);
    border-bottom: 2px solid #e2e8f0;
}
.pl-table th {
    padding: 10px 16px; font-size: 10px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .6px; color: #64748b;
}
.pl-th-code   { width: 140px; }
.pl-th-name   { text-align: left; }
.pl-th-amount { width: 180px; text-align: right; }

.pl-row { border-bottom: 1px solid #f0f4f8; transition: background .12s, transform .12s; }
.pl-row:last-child { border-bottom: none; }

.pl-row-revenue:hover { background: #ecfdf5; transform: translateX(3px); box-shadow: -3px 0 0 #10b981; }
.pl-row-cogs:hover    { background: #fffbeb; transform: translateX(3px); box-shadow: -3px 0 0 #d97706; }
.pl-row-expense:hover { background: #faf5ff; transform: translateX(3px); box-shadow: -3px 0 0 #9333ea; }

.pl-td-code   { padding: 11px 16px; }
.pl-td-name   { padding: 11px 16px; color: #334155; font-weight: 500; }
.pl-td-amount {
    padding: 11px 16px; text-align: right;
    font-family: 'Courier New', monospace; font-weight: 700; font-size: 13px;
}

.pl-code-badge {
    display: inline-block; padding: 2px 8px; border-radius: 6px;
    background: #f1f5f9; color: #475569;
    font-family: 'Courier New', monospace; font-size: 11px; font-weight: 700;
    border: 1px solid #e2e8f0;
}

.pl-amount-green  { color: #047857; }
.pl-amount-orange { color: #b45309; }
.pl-amount-purple { color: #7e22ce; }

.pl-empty {
    text-align: center; padding: 24px;
    color: #94a3b8; font-style: italic; font-size: 13px;
}

/* Subtotal rows */
.pl-subtotal-row { border-top: 2px solid #e2e8f0; }
.pl-subtotal-revenue { background: linear-gradient(90deg, #ecfdf5, #d1fae5); }
.pl-subtotal-cogs    { background: linear-gradient(90deg, #fffbeb, #fef3c7); }
.pl-subtotal-expense { background: linear-gradient(90deg, #faf5ff, #f3e8ff); }

.pl-subtotal-label {
    padding: 12px 16px; font-weight: 800; color: #1e293b;
    font-size: 13px; text-align: right;
}
.pl-subtotal-amount {
    padding: 12px 16px; font-weight: 900; font-size: 14px;
    text-align: right; font-family: 'Courier New', monospace;
}

/* ── Gross Profit Mid-Banner ──────────────────────────────────────────────── */
.pl-mid-banner {
    display: flex; align-items: center; justify-content: space-between;
    border-radius: 14px; padding: 20px 24px; margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,.1); position: relative; overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.pl-mid-banner:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.14); }
.pl-mid-banner::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 50%;
    background: linear-gradient(180deg, rgba(255,255,255,.25) 0%, rgba(255,255,255,0) 100%);
    border-radius: 14px 14px 0 0; pointer-events: none;
}

.pl-banner-profit {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 60%, #6ee7b7 100%);
    border: 1.5px solid #6ee7b7; border-bottom: 4px solid #34d399;
}
.pl-banner-loss {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 60%, #fca5a5 100%);
    border: 1.5px solid #fca5a5; border-bottom: 4px solid #f87171;
}

.pl-banner-left  { display: flex; align-items: center; gap: 16px; }
.pl-banner-icon  {
    width: 48px; height: 48px; border-radius: 13px;
    background: rgba(0,0,0,.1); display: flex; align-items: center;
    justify-content: center; font-size: 22px; color: #047857;
    flex-shrink: 0;
}
.pl-banner-loss .pl-banner-icon { color: #b91c1c; }
.pl-banner-title { font-size: 16px; font-weight: 900; color: #1e293b; }
.pl-banner-sub   { font-size: 12px; color: #475569; margin-top: 3px; }
.pl-banner-right { text-align: right; }
.pl-banner-margin-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    color: #64748b; letter-spacing: .5px;
}
.pl-banner-margin {
    font-size: 26px; font-weight: 900; color: #047857; line-height: 1.1;
}
.pl-banner-loss .pl-banner-margin { color: #b91c1c; }
.pl-banner-amount {
    font-size: 18px; font-weight: 900; color: #047857;
    font-family: 'Courier New', monospace;
}
.pl-banner-loss .pl-banner-amount { color: #b91c1c; }

/* ── Net Profit Final Banner ──────────────────────────────────────────────── */
.pl-net-banner {
    display: flex; align-items: center; justify-content: space-between;
    border-radius: 16px; padding: 24px 28px;
    box-shadow: 0 8px 28px rgba(0,0,0,.18);
    transition: transform .2s, box-shadow .2s;
    position: relative; overflow: hidden;
}
.pl-net-banner:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(0,0,0,.22); }
.pl-net-banner::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 45%;
    background: linear-gradient(180deg, rgba(255,255,255,.15) 0%, rgba(255,255,255,0) 100%);
    border-radius: 16px 16px 0 0; pointer-events: none;
}

.pl-net-profit { background: linear-gradient(145deg, #22c55e 0%, #16a34a 40%, #15803d 75%, #14532d 100%); }
.pl-net-loss   { background: linear-gradient(145deg, #f87171 0%, #dc2626 40%, #b91c1c 75%, #7f1d1d 100%); }

.pl-net-left { display: flex; align-items: center; gap: 16px; }
.pl-net-icon {
    width: 54px; height: 54px; border-radius: 15px;
    background: rgba(0,0,0,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: #fff; flex-shrink: 0;
}
.pl-net-title {
    font-size: 18px; font-weight: 900; color: #fff; letter-spacing: .5px;
    text-shadow: 0 2px 4px rgba(0,0,0,.3);
}
.pl-net-formula { font-size: 12px; color: rgba(255,255,255,.7); margin-top: 3px; }
.pl-net-period  { font-size: 11px; color: rgba(255,255,255,.5); margin-top: 2px; }

.pl-net-right { text-align: right; }
.pl-net-margin-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    color: rgba(255,255,255,.6); letter-spacing: .5px;
}
.pl-net-margin {
    font-size: 20px; font-weight: 900; color: rgba(255,255,255,.9); line-height: 1.2;
}
.pl-net-amount {
    font-size: 30px; font-weight: 900; color: #fff;
    font-family: 'Courier New', monospace; line-height: 1.2; margin-top: 4px;
    text-shadow: 0 2px 6px rgba(0,0,0,.3); letter-spacing: -0.5px;
}
.pl-net-status {
    font-size: 12px; font-weight: 800; color: rgba(255,255,255,.85);
    letter-spacing: 1.5px; margin-top: 4px;
}

/* ── Print ────────────────────────────────────────────────────────────────── */
.pl-print-header { display: none; }

@media print {
    .no-print   { display: none !important; }
    .pl-page-wrap { padding: 0 !important; background: #fff !important; }
    .pl-print-header { display: block; text-align: center; margin-bottom: 20px; }
    .pl-print-header h1 { font-size: 22px; margin: 0; }
    .pl-print-header p  { color: #555; margin: 4px 0 0; }
    .pl-section-card { box-shadow: none !important; border: 1px solid #ccc !important; page-break-inside: avoid; }
    .pl-mid-banner, .pl-net-banner { box-shadow: none !important; page-break-inside: avoid; }
    .pl-net-profit, .pl-net-loss {
        background: #f0f0f0 !important;
    }
    .pl-net-title, .pl-net-amount { color: #000 !important; text-shadow: none !important; }
    .pl-net-formula, .pl-net-period, .pl-net-margin-label,
    .pl-net-margin, .pl-net-status { color: #333 !important; }
    .pl-stat-card { display: none; }
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 1100px) {
    .pl-summary-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .pl-summary-grid { grid-template-columns: 1fr; }
    .pl-page-wrap { padding: 0 12px 36px; }
    .pl-filter-bar { padding: 12px 16px; }
    .pl-filter-fields { flex-direction: column; align-items: flex-start; }
    .pl-datepicker { width: 100% !important; }
    .pl-period-bar { flex-direction: column; align-items: flex-start; }
    .pl-period-metrics { justify-content: flex-start; }
    .pl-mid-banner { flex-direction: column; gap: 14px; text-align: center; }
    .pl-banner-right { text-align: center; }
    .pl-banner-left { flex-direction: column; align-items: center; text-align: center; }
    .pl-net-banner { flex-direction: column; gap: 16px; }
    .pl-net-right { text-align: center; }
    .pl-section-header { flex-direction: column; align-items: flex-start; gap: 8px; }
}
</style>
