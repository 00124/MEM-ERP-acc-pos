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

            <!-- Quick Filters -->
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

                <!-- Print Header (only visible when printing) -->
                <div class="print-header">
                    <h1>Profit &amp; Loss Statement</h1>
                    <p>{{ filters.date_from?.format('DD MMM YYYY') }} &ndash; {{ filters.date_to?.format('DD MMM YYYY') }}</p>
                </div>

                <!-- ── Summary Cards ──────────────────────────────────────── -->
                <div class="pl-summary-grid no-print">
                    <!-- Total Revenue -->
                    <div class="pl-stat-card pl-stat-revenue">
                        <div class="pl-stat-icon">
                            <RiseOutlined />
                        </div>
                        <div class="pl-stat-body">
                            <div class="pl-stat-label">Total Revenue</div>
                            <div class="pl-stat-value">{{ fmt(data.total_revenue) }}</div>
                            <div class="pl-stat-sub">Income from all sources</div>
                        </div>
                        <div class="pl-stat-badge pl-badge-green">{{ incomeRows.length }} accounts</div>
                    </div>

                    <!-- COGS -->
                    <div class="pl-stat-card pl-stat-cogs">
                        <div class="pl-stat-icon">
                            <ShoppingCartOutlined />
                        </div>
                        <div class="pl-stat-body">
                            <div class="pl-stat-label">Cost of Goods Sold</div>
                            <div class="pl-stat-value">{{ fmt(data.total_cogs) }}</div>
                            <div class="pl-stat-sub">Direct production costs</div>
                        </div>
                        <div class="pl-stat-badge pl-badge-orange">{{ cogsRows.length }} accounts</div>
                    </div>

                    <!-- Gross Profit -->
                    <div class="pl-stat-card" :class="data.gross_profit >= 0 ? 'pl-stat-gross-profit' : 'pl-stat-gross-loss'">
                        <div class="pl-stat-icon">
                            <BarChartOutlined />
                        </div>
                        <div class="pl-stat-body">
                            <div class="pl-stat-label">Gross Profit</div>
                            <div class="pl-stat-value">{{ fmt(data.gross_profit) }}</div>
                            <div class="pl-stat-sub">Revenue minus COGS</div>
                        </div>
                        <div class="pl-stat-badge" :class="data.gross_profit >= 0 ? 'pl-badge-green' : 'pl-badge-red'">
                            {{ grossMargin }}%
                        </div>
                    </div>

                    <!-- Net Profit -->
                    <div class="pl-stat-card" :class="data.net_profit >= 0 ? 'pl-stat-net-profit' : 'pl-stat-net-loss'">
                        <div class="pl-stat-icon">
                            <DollarOutlined />
                        </div>
                        <div class="pl-stat-body">
                            <div class="pl-stat-label">Net Profit / Loss</div>
                            <div class="pl-stat-value">{{ fmt(data.net_profit) }}</div>
                            <div class="pl-stat-sub">After all expenses</div>
                        </div>
                        <div class="pl-stat-badge" :class="data.net_profit >= 0 ? 'pl-badge-green' : 'pl-badge-red'">
                            {{ data.net_profit >= 0 ? 'Profit' : 'Loss' }}
                        </div>
                    </div>
                </div>

                <!-- ── Period Header ──────────────────────────────────────── -->
                <div class="pl-period-header no-print">
                    <div class="pl-period-left">
                        <CalendarOutlined class="pl-period-icon" />
                        <span class="pl-period-range">
                            {{ formatDate(data.date_from) }} &ndash; {{ formatDate(data.date_to) }}
                        </span>
                    </div>
                    <div class="pl-period-right">
                        <span class="pl-period-tag" :class="data.net_profit >= 0 ? 'pl-tag-profit' : 'pl-tag-loss'">
                            {{ data.net_profit >= 0 ? '▲ Profitable Period' : '▼ Loss Period' }}
                        </span>
                    </div>
                </div>

                <!-- ── Revenue Section ────────────────────────────────────── -->
                <div class="pl-section-card">
                    <div class="pl-section-header pl-header-revenue">
                        <div class="pl-section-title">
                            <RiseOutlined class="pl-section-icon" />
                            Revenue
                        </div>
                        <div class="pl-section-total">{{ fmt(data.total_revenue) }}</div>
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
                                <tr v-for="r in incomeRows" :key="r.account_code" class="pl-row">
                                    <td class="pl-td-code">
                                        <span class="pl-code-badge">{{ r.account_code }}</span>
                                    </td>
                                    <td class="pl-td-name">{{ r.account_name }}</td>
                                    <td class="pl-td-amount pl-amount-green">{{ fmt(r.net) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="pl-subtotal-row">
                                    <td colspan="2" class="pl-subtotal-label">Total Revenue</td>
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
                            Cost of Goods Sold
                        </div>
                        <div class="pl-section-total">{{ fmt(data.total_cogs) }}</div>
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
                                <tr v-for="r in cogsRows" :key="r.account_code" class="pl-row">
                                    <td class="pl-td-code">
                                        <span class="pl-code-badge">{{ r.account_code }}</span>
                                    </td>
                                    <td class="pl-td-name">{{ r.account_name }}</td>
                                    <td class="pl-td-amount pl-amount-red">{{ fmt(Math.abs(r.net)) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="pl-subtotal-row">
                                    <td colspan="2" class="pl-subtotal-label">Total COGS</td>
                                    <td class="pl-subtotal-amount pl-amount-red">{{ fmt(data.total_cogs) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ── Gross Profit Bar ───────────────────────────────────── -->
                <div class="pl-highlight-bar" :class="data.gross_profit >= 0 ? 'pl-bar-profit' : 'pl-bar-loss'">
                    <div class="pl-bar-left">
                        <BarChartOutlined class="pl-bar-icon" />
                        <div>
                            <div class="pl-bar-title">Gross Profit</div>
                            <div class="pl-bar-sub">Revenue − Cost of Goods Sold</div>
                        </div>
                    </div>
                    <div class="pl-bar-right">
                        <div class="pl-bar-margin-label">Gross Margin</div>
                        <div class="pl-bar-margin-value">{{ grossMargin }}%</div>
                        <div class="pl-bar-amount">{{ fmt(data.gross_profit) }}</div>
                    </div>
                </div>

                <!-- ── Operating Expenses Section ─────────────────────────── -->
                <div class="pl-section-card">
                    <div class="pl-section-header pl-header-expense">
                        <div class="pl-section-title">
                            <MinusCircleOutlined class="pl-section-icon" />
                            Operating Expenses
                        </div>
                        <div class="pl-section-total">{{ fmt(data.total_expenses) }}</div>
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
                                <tr v-for="r in expenseRows" :key="r.account_code" class="pl-row">
                                    <td class="pl-td-code">
                                        <span class="pl-code-badge">{{ r.account_code }}</span>
                                    </td>
                                    <td class="pl-td-name">{{ r.account_name }}</td>
                                    <td class="pl-td-amount pl-amount-orange">{{ fmt(Math.abs(r.net)) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="pl-subtotal-row">
                                    <td colspan="2" class="pl-subtotal-label">Total Expenses</td>
                                    <td class="pl-subtotal-amount pl-amount-orange">{{ fmt(data.total_expenses) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ── Net Profit Final Bar ───────────────────────────────── -->
                <div class="pl-net-bar" :class="data.net_profit >= 0 ? 'pl-net-profit' : 'pl-net-loss'">
                    <div class="pl-net-left">
                        <div class="pl-net-icon-wrap">
                            <TrophyOutlined v-if="data.net_profit >= 0" />
                            <WarningOutlined v-else />
                        </div>
                        <div>
                            <div class="pl-net-title">NET PROFIT / LOSS</div>
                            <div class="pl-net-formula">Gross Profit − Operating Expenses</div>
                            <div class="pl-net-period">
                                Period: {{ formatDate(data.date_from) }} to {{ formatDate(data.date_to) }}
                            </div>
                        </div>
                    </div>
                    <div class="pl-net-right">
                        <div class="pl-net-margin-label">Net Margin</div>
                        <div class="pl-net-margin-value">{{ netMargin }}%</div>
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

        /* ── Row Computed ─────────────────────────────────────────────── */
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

        /* ── Helpers ──────────────────────────────────────────────────── */
        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        /* ── Quick Filters ────────────────────────────────────────────── */
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

        /* ── Load Data ────────────────────────────────────────────────── */
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
            } catch (e) {
                /* silent — keep existing data on error */
            } finally {
                loading.value = false;
            }
        };

        /* ── Print ────────────────────────────────────────────────────── */
        const print = () => window.print();

        const exportPdf = () => window.print(); /* placeholder — triggers print dialog */

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
/* ── Base Layout ──────────────────────────────────────────────────────────── */
.pl-page-wrap {
    padding: 0 24px 40px;
}

/* ── Filter Bar ───────────────────────────────────────────────────────────── */
.pl-filter-bar {
    background: #fff;
    border-bottom: 1px solid #e8ecf0;
    padding: 16px 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.pl-filter-inner { max-width: 1200px; }
.pl-filter-fields {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.pl-field-group { display: flex; flex-direction: column; gap: 4px; }
.pl-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
.pl-datepicker { width: 180px !important; }
.pl-generate-btn {
    height: 36px !important;
    padding: 0 20px !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(102,126,234,.35) !important;
}
.pl-generate-btn:hover {
    background: linear-gradient(135deg, #5a6fd6, #6a3f96) !important;
}

/* Quick Filters */
.pl-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pl-quick-label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; }
.pl-quick-btn {
    padding: 4px 12px;
    border-radius: 20px;
    border: 1.5px solid #e5e7eb;
    background: #f9fafb;
    color: #374151;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all .2s;
}
.pl-quick-btn:hover {
    border-color: #667eea;
    color: #667eea;
    background: #eff0fe;
}

/* ── Print Buttons ────────────────────────────────────────────────────────── */
.pl-btn { height: 36px !important; border-radius: 8px !important; font-weight: 500 !important; }
.pl-btn-outline { border-color: #d1d5db !important; color: #374151 !important; }

/* ── Summary Cards ────────────────────────────────────────────────────────── */
.pl-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.pl-stat-card {
    border-radius: 14px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,.08);
    transition: transform .2s, box-shadow .2s;
}
.pl-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
}
.pl-stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    background: rgba(255,255,255,.25);
    color: #fff;
}
.pl-stat-body { flex: 1; }
.pl-stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; opacity: .85; color: #fff; margin-bottom: 4px; }
.pl-stat-value { font-size: 22px; font-weight: 800; color: #fff; line-height: 1.2; font-family: 'Courier New', monospace; }
.pl-stat-sub   { font-size: 11px; color: rgba(255,255,255,.7); margin-top: 4px; }
.pl-stat-badge {
    align-self: flex-start;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(255,255,255,.2);
    color: #fff;
    backdrop-filter: blur(4px);
}

/* Card color themes */
.pl-stat-revenue    { background: linear-gradient(135deg, #059669, #10b981); }
.pl-stat-cogs       { background: linear-gradient(135deg, #d97706, #f59e0b); }
.pl-stat-gross-profit { background: linear-gradient(135deg, #2563eb, #3b82f6); }
.pl-stat-gross-loss { background: linear-gradient(135deg, #dc2626, #ef4444); }
.pl-stat-net-profit { background: linear-gradient(135deg, #15803d, #16a34a); }
.pl-stat-net-loss   { background: linear-gradient(135deg, #b91c1c, #dc2626); }

.pl-badge-green  { background: rgba(16,185,129,.2) !important; }
.pl-badge-orange { background: rgba(245,158,11,.2) !important; }
.pl-badge-red    { background: rgba(239,68,68,.2) !important; }

/* ── Period Header ────────────────────────────────────────────────────────── */
.pl-period-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 20px;
    margin-bottom: 20px;
}
.pl-period-left { display: flex; align-items: center; gap: 10px; }
.pl-period-icon { color: #667eea; font-size: 18px; }
.pl-period-range { font-size: 14px; font-weight: 600; color: #1e293b; }
.pl-period-tag {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .3px;
}
.pl-tag-profit { background: #dcfce7; color: #15803d; }
.pl-tag-loss   { background: #fee2e2; color: #dc2626; }

/* ── Section Cards ────────────────────────────────────────────────────────── */
.pl-section-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    border: 1px solid #f0f0f0;
}
.pl-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    font-weight: 700;
    font-size: 14px;
    color: #fff;
}
.pl-section-title { display: flex; align-items: center; gap: 8px; }
.pl-section-icon  { font-size: 16px; }
.pl-section-total { font-size: 16px; font-family: 'Courier New', monospace; }

.pl-header-revenue { background: linear-gradient(135deg, #059669, #10b981); }
.pl-header-cogs    { background: linear-gradient(135deg, #d97706, #f59e0b); }
.pl-header-expense { background: linear-gradient(135deg, #7c3aed, #8b5cf6); }

/* ── Tables ───────────────────────────────────────────────────────────────── */
.pl-table-wrap { overflow-x: auto; }
.pl-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.pl-table thead tr {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}
.pl-table th {
    padding: 10px 16px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #64748b;
}
.pl-th-code   { width: 140px; }
.pl-th-name   { text-align: left; }
.pl-th-amount { width: 180px; text-align: right; }

.pl-row { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.pl-row:hover { background: #f8fafc; }
.pl-row:last-child { border-bottom: none; }

.pl-td-code   { padding: 11px 16px; }
.pl-td-name   { padding: 11px 16px; color: #334155; font-weight: 500; }
.pl-td-amount { padding: 11px 16px; text-align: right; font-family: 'Courier New', monospace; font-weight: 600; font-size: 13px; }

.pl-code-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    font-weight: 600;
}

.pl-amount-green  { color: #15803d; }
.pl-amount-red    { color: #dc2626; }
.pl-amount-orange { color: #b45309; }

.pl-empty {
    text-align: center;
    padding: 24px;
    color: #94a3b8;
    font-style: italic;
    font-size: 13px;
}

/* Subtotal Row */
.pl-subtotal-row { border-top: 2px solid #e2e8f0; background: #f8fafc; }
.pl-subtotal-label {
    padding: 12px 16px;
    font-weight: 700;
    color: #1e293b;
    font-size: 13px;
    text-align: right;
}
.pl-subtotal-amount {
    padding: 12px 16px;
    font-weight: 800;
    font-size: 14px;
    text-align: right;
    font-family: 'Courier New', monospace;
}

/* ── Gross Profit Highlight Bar ───────────────────────────────────────────── */
.pl-highlight-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,.1);
}
.pl-bar-profit { background: linear-gradient(135deg, #d1fae5, #a7f3d0); border: 2px solid #6ee7b7; }
.pl-bar-loss   { background: linear-gradient(135deg, #fee2e2, #fecaca); border: 2px solid #fca5a5; }
.pl-bar-left   { display: flex; align-items: center; gap: 14px; }
.pl-bar-icon   { font-size: 28px; color: #059669; }
.pl-bar-loss .pl-bar-icon { color: #dc2626; }
.pl-bar-title  { font-size: 16px; font-weight: 800; color: #1e293b; }
.pl-bar-sub    { font-size: 12px; color: #64748b; margin-top: 2px; }
.pl-bar-right  { text-align: right; }
.pl-bar-margin-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: .5px; }
.pl-bar-margin-value { font-size: 22px; font-weight: 800; color: #059669; line-height: 1.2; }
.pl-bar-loss .pl-bar-margin-value { color: #dc2626; }
.pl-bar-amount {
    font-size: 18px;
    font-weight: 800;
    color: #15803d;
    font-family: 'Courier New', monospace;
}
.pl-bar-loss .pl-bar-amount { color: #dc2626; }

/* ── Net Profit Final Bar ─────────────────────────────────────────────────── */
.pl-net-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 14px;
    padding: 24px 28px;
    box-shadow: 0 6px 24px rgba(0,0,0,.15);
}
.pl-net-profit { background: linear-gradient(135deg, #14532d, #15803d); }
.pl-net-loss   { background: linear-gradient(135deg, #7f1d1d, #b91c1c); }
.pl-net-left   { display: flex; align-items: center; gap: 16px; }
.pl-net-icon-wrap {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    color: #fff;
}
.pl-net-title   { font-size: 18px; font-weight: 900; color: #fff; letter-spacing: .5px; }
.pl-net-formula { font-size: 12px; color: rgba(255,255,255,.7); margin-top: 3px; }
.pl-net-period  { font-size: 11px; color: rgba(255,255,255,.55); margin-top: 2px; }
.pl-net-right   { text-align: right; }
.pl-net-margin-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,.6); letter-spacing: .5px; }
.pl-net-margin-value { font-size: 20px; font-weight: 800; color: rgba(255,255,255,.9); line-height: 1.2; }
.pl-net-amount {
    font-size: 28px;
    font-weight: 900;
    color: #fff;
    font-family: 'Courier New', monospace;
    line-height: 1.2;
    margin-top: 4px;
}
.pl-net-status {
    font-size: 12px;
    font-weight: 700;
    color: rgba(255,255,255,.8);
    letter-spacing: 1px;
    margin-top: 4px;
}

/* ── Print Styles ─────────────────────────────────────────────────────────── */
.print-header { display: none; }

@media print {
    .no-print { display: none !important; }
    .pl-page-wrap { padding: 0 !important; }
    .print-header { display: block; text-align: center; margin-bottom: 20px; }
    .print-header h1 { font-size: 20px; margin: 0; }
    .print-header p  { color: #666; margin: 4px 0 0; }

    .pl-section-card { box-shadow: none !important; border: 1px solid #ddd !important; page-break-inside: avoid; }
    .pl-highlight-bar, .pl-net-bar { page-break-inside: avoid; }

    .pl-net-profit, .pl-net-loss {
        background: none !important;
        border: 3px solid #15803d !important;
        color: #15803d !important;
    }
    .pl-net-loss { border-color: #dc2626 !important; color: #dc2626 !important; }
    .pl-net-title, .pl-net-formula, .pl-net-period, .pl-net-margin-label,
    .pl-net-margin-value, .pl-net-amount, .pl-net-status { color: inherit !important; }
    .pl-net-icon-wrap { background: transparent !important; border: 2px solid currentColor; }
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 1100px) {
    .pl-summary-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .pl-filter-fields { flex-direction: column; align-items: flex-start; }
    .pl-datepicker { width: 100% !important; }
    .pl-summary-grid { grid-template-columns: 1fr; }
    .pl-page-wrap { padding: 0 12px 32px; }
    .pl-filter-bar { padding: 12px 16px; }
    .pl-highlight-bar { flex-direction: column; gap: 16px; text-align: center; }
    .pl-bar-right { text-align: center; }
    .pl-net-bar { flex-direction: column; gap: 16px; }
    .pl-net-right { text-align: center; }
    .pl-section-header { flex-direction: column; align-items: flex-start; gap: 4px; }
    .pl-period-header { flex-direction: column; gap: 8px; }
}
</style>
