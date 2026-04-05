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
/* ═══════════════════════════════════════════════════════════════════════════
   3D PROFIT & LOSS — FULL STYLESHEET
   Technique: layered box-shadow stacking + perspective transforms + pseudo-
   element depth faces + glass-shine overlays + embossed text
   ═══════════════════════════════════════════════════════════════════════════ */

/* ── Page Background ──────────────────────────────────────────────────────── */
.pl-page-wrap {
    padding: 0 24px 48px;
    background: linear-gradient(160deg, #f0f4ff 0%, #faf5ff 50%, #f0fdf4 100%);
    min-height: 100%;
}

/* ── Filter Bar ───────────────────────────────────────────────────────────── */
.pl-filter-bar {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid #dde4ef;
    padding: 18px 24px;
    margin-bottom: 28px;
    box-shadow:
        0 1px 0 rgba(255,255,255,.9) inset,
        0 4px 12px rgba(0,0,0,.06),
        0 8px 0 -4px rgba(0,0,0,.03);
}
.pl-filter-inner { max-width: 1200px; }
.pl-filter-fields {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.pl-field-group { display: flex; flex-direction: column; gap: 5px; }
.pl-label {
    font-size: 11px; font-weight: 700; color: #5a6a85;
    text-transform: uppercase; letter-spacing: .6px;
    text-shadow: 0 1px 0 rgba(255,255,255,.8);
}
.pl-datepicker { width: 185px !important; }

/* 3D Generate Button */
.pl-generate-btn {
    height: 38px !important;
    padding: 0 22px !important;
    border-radius: 10px !important;
    font-weight: 700 !important;
    background: linear-gradient(180deg, #7c8ff5 0%, #5a67d8 60%, #4c56c0 100%) !important;
    border: none !important;
    border-bottom: 4px solid #3730a3 !important;
    box-shadow:
        0 1px 0 rgba(255,255,255,.3) inset,
        0 6px 16px rgba(79,70,229,.45) !important;
    transition: all .15s ease !important;
    text-shadow: 0 1px 2px rgba(0,0,0,.3) !important;
}
.pl-generate-btn:hover {
    background: linear-gradient(180deg, #8b9cf8 0%, #6875e8 60%, #5b64d4 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 8px 20px rgba(79,70,229,.5) !important;
}
.pl-generate-btn:active {
    transform: translateY(2px) !important;
    border-bottom-width: 1px !important;
    box-shadow: 0 2px 6px rgba(79,70,229,.3) !important;
}

/* Quick Filter Buttons — 3D pill style */
.pl-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pl-quick-label { font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; }
.pl-quick-btn {
    padding: 5px 14px;
    border-radius: 20px;
    border: 1px solid #d1d9e6;
    border-bottom: 3px solid #bcc5d8;
    background: linear-gradient(180deg, #ffffff 0%, #f1f4f9 100%);
    color: #4b5a72;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
    box-shadow: 0 2px 4px rgba(0,0,0,.06), 0 1px 0 rgba(255,255,255,.8) inset;
    text-shadow: 0 1px 0 rgba(255,255,255,.7);
}
.pl-quick-btn:hover {
    border-color: #818cf8;
    border-bottom-color: #6366f1;
    color: #4338ca;
    background: linear-gradient(180deg, #eef0fe 0%, #e0e3fd 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(99,102,241,.2);
}
.pl-quick-btn:active {
    transform: translateY(1px);
    border-bottom-width: 1px;
    box-shadow: none;
}

/* ── Print Buttons ────────────────────────────────────────────────────────── */
.pl-btn {
    height: 36px !important; border-radius: 9px !important; font-weight: 600 !important;
    border-bottom: 3px solid rgba(0,0,0,.15) !important;
    box-shadow: 0 2px 6px rgba(0,0,0,.1) !important;
    transition: all .15s !important;
}
.pl-btn:active { transform: translateY(2px) !important; border-bottom-width: 1px !important; }
.pl-btn-outline {
    border-color: #d1d5db !important;
    border-bottom-color: #b0b7c3 !important;
    color: #374151 !important;
    background: linear-gradient(180deg, #fff 0%, #f3f4f6 100%) !important;
}

/* ── Summary Cards — 3D raised tiles ─────────────────────────────────────── */
.pl-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 28px;
    perspective: 1200px;
}

.pl-stat-card {
    border-radius: 18px;
    padding: 22px 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
    overflow: hidden;
    cursor: default;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s ease;
    transform-style: preserve-3d;
}

/* 3D depth face — bottom edge */
.pl-stat-card::before {
    content: '';
    position: absolute;
    bottom: -8px; left: 6px; right: 6px;
    height: 12px;
    border-radius: 0 0 14px 14px;
    filter: brightness(.6);
    z-index: -1;
    transition: bottom .25s, height .25s;
}
/* Glass shine overlay */
.pl-stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 50%;
    border-radius: 18px 18px 60% 60% / 18px 18px 40% 40%;
    background: linear-gradient(180deg, rgba(255,255,255,.28) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}

.pl-stat-card:hover {
    transform: perspective(600px) rotateX(4deg) translateY(-6px);
}
.pl-stat-card:hover::before { bottom: -14px; height: 18px; }

/* Revenue — emerald */
.pl-stat-revenue {
    background: linear-gradient(145deg, #34d399 0%, #059669 55%, #047857 100%);
    box-shadow:
        0 1px 0 rgba(255,255,255,.25) inset,
        0 8px 0 #065f46,
        0 10px 30px rgba(5,150,105,.45);
}
.pl-stat-revenue::before { background: #065f46; }
.pl-stat-revenue:hover {
    box-shadow: 0 1px 0 rgba(255,255,255,.25) inset, 0 14px 0 #065f46, 0 18px 40px rgba(5,150,105,.55);
}

/* COGS — amber */
.pl-stat-cogs {
    background: linear-gradient(145deg, #fbbf24 0%, #d97706 55%, #b45309 100%);
    box-shadow:
        0 1px 0 rgba(255,255,255,.25) inset,
        0 8px 0 #92400e,
        0 10px 30px rgba(217,119,6,.45);
}
.pl-stat-cogs::before { background: #92400e; }
.pl-stat-cogs:hover {
    box-shadow: 0 1px 0 rgba(255,255,255,.25) inset, 0 14px 0 #92400e, 0 18px 40px rgba(217,119,6,.55);
}

/* Gross Profit — indigo */
.pl-stat-gross-profit {
    background: linear-gradient(145deg, #818cf8 0%, #4f46e5 55%, #4338ca 100%);
    box-shadow:
        0 1px 0 rgba(255,255,255,.25) inset,
        0 8px 0 #312e81,
        0 10px 30px rgba(79,70,229,.45);
}
.pl-stat-gross-profit::before { background: #312e81; }
.pl-stat-gross-profit:hover {
    box-shadow: 0 1px 0 rgba(255,255,255,.25) inset, 0 14px 0 #312e81, 0 18px 40px rgba(79,70,229,.55);
}

/* Gross Loss */
.pl-stat-gross-loss {
    background: linear-gradient(145deg, #f87171 0%, #dc2626 55%, #b91c1c 100%);
    box-shadow:
        0 1px 0 rgba(255,255,255,.25) inset,
        0 8px 0 #7f1d1d,
        0 10px 30px rgba(220,38,38,.45);
}
.pl-stat-gross-loss::before { background: #7f1d1d; }

/* Net Profit — deep green */
.pl-stat-net-profit {
    background: linear-gradient(145deg, #4ade80 0%, #16a34a 55%, #15803d 100%);
    box-shadow:
        0 1px 0 rgba(255,255,255,.25) inset,
        0 8px 0 #14532d,
        0 10px 30px rgba(22,163,74,.45);
}
.pl-stat-net-profit::before { background: #14532d; }
.pl-stat-net-profit:hover {
    box-shadow: 0 1px 0 rgba(255,255,255,.25) inset, 0 14px 0 #14532d, 0 18px 40px rgba(22,163,74,.55);
}

/* Net Loss */
.pl-stat-net-loss {
    background: linear-gradient(145deg, #f87171 0%, #dc2626 55%, #991b1b 100%);
    box-shadow:
        0 1px 0 rgba(255,255,255,.25) inset,
        0 8px 0 #7f1d1d,
        0 10px 30px rgba(220,38,38,.5);
}
.pl-stat-net-loss::before { background: #7f1d1d; }

/* Card internals */
.pl-stat-icon {
    width: 46px; height: 46px;
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    background: rgba(0,0,0,.18);
    color: rgba(255,255,255,.95);
    box-shadow: 0 2px 0 rgba(0,0,0,.2), 0 1px 0 rgba(255,255,255,.15) inset;
}
.pl-stat-body { flex: 1; }
.pl-stat-label {
    font-size: 10px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .8px; color: rgba(255,255,255,.8); margin-bottom: 5px;
    text-shadow: 0 1px 2px rgba(0,0,0,.3);
}
.pl-stat-value {
    font-size: 21px; font-weight: 900; color: #fff; line-height: 1.15;
    font-family: 'Courier New', monospace;
    text-shadow: 0 2px 4px rgba(0,0,0,.3), 0 1px 0 rgba(255,255,255,.1);
}
.pl-stat-sub { font-size: 11px; color: rgba(255,255,255,.65); margin-top: 4px; }
.pl-stat-badge {
    align-self: flex-start;
    padding: 4px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(0,0,0,.2);
    color: rgba(255,255,255,.95);
    box-shadow: 0 1px 0 rgba(255,255,255,.1) inset, 0 2px 4px rgba(0,0,0,.2);
    text-shadow: 0 1px 2px rgba(0,0,0,.3);
}
.pl-badge-green  { background: rgba(0,0,0,.18) !important; }
.pl-badge-orange { background: rgba(0,0,0,.18) !important; }
.pl-badge-red    { background: rgba(0,0,0,.18) !important; }

/* ── Period Header — raised panel ────────────────────────────────────────── */
.pl-period-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(180deg, #fff 0%, #f4f7fb 100%);
    border: 1px solid #dde4ef;
    border-bottom: 3px solid #c8d3e6;
    border-radius: 12px;
    padding: 13px 22px;
    margin-bottom: 22px;
    box-shadow:
        0 1px 0 rgba(255,255,255,.9) inset,
        0 4px 12px rgba(0,0,0,.07);
}
.pl-period-left { display: flex; align-items: center; gap: 10px; }
.pl-period-icon { color: #6366f1; font-size: 18px; filter: drop-shadow(0 2px 3px rgba(99,102,241,.3)); }
.pl-period-range {
    font-size: 14px; font-weight: 700; color: #1e293b;
    text-shadow: 0 1px 0 rgba(255,255,255,.8);
}
.pl-period-tag {
    padding: 5px 16px;
    border-radius: 20px;
    font-size: 12px; font-weight: 800; letter-spacing: .3px;
    border-bottom: 2px solid transparent;
    box-shadow: 0 2px 6px rgba(0,0,0,.1), 0 1px 0 rgba(255,255,255,.5) inset;
}
.pl-tag-profit {
    background: linear-gradient(180deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border-color: #6ee7b7;
}
.pl-tag-loss {
    background: linear-gradient(180deg, #fee2e2 0%, #fecaca 100%);
    color: #7f1d1d;
    border-color: #fca5a5;
}

/* ── Section Cards — 3D raised container ─────────────────────────────────── */
.pl-section-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 22px;
    position: relative;
    box-shadow:
        0 1px 0 rgba(255,255,255,.9) inset,
        0 6px 0 #dde4ef,
        0 8px 24px rgba(0,0,0,.09);
    border: 1px solid #e8edf5;
    transition: transform .2s, box-shadow .2s;
}
.pl-section-card:hover {
    transform: translateY(-3px);
    box-shadow:
        0 1px 0 rgba(255,255,255,.9) inset,
        0 10px 0 #dde4ef,
        0 14px 32px rgba(0,0,0,.12);
}

/* Embossed section headers */
.pl-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 22px;
    font-weight: 800;
    font-size: 14px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
/* Shine stripe on header */
.pl-section-header::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 45%;
    background: linear-gradient(180deg, rgba(255,255,255,.22) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}
.pl-section-title {
    display: flex; align-items: center; gap: 9px;
    text-shadow: 0 1px 3px rgba(0,0,0,.3), 0 2px 6px rgba(0,0,0,.15);
}
.pl-section-icon { font-size: 17px; filter: drop-shadow(0 2px 3px rgba(0,0,0,.25)); }
.pl-section-total {
    font-size: 17px; font-family: 'Courier New', monospace; font-weight: 900;
    text-shadow: 0 1px 3px rgba(0,0,0,.35);
}

/* Header gradient colors */
.pl-header-revenue {
    background: linear-gradient(135deg, #34d399 0%, #059669 50%, #047857 100%);
    border-bottom: 3px solid #065f46;
    box-shadow: 0 3px 0 #065f46;
}
.pl-header-cogs {
    background: linear-gradient(135deg, #fbbf24 0%, #d97706 50%, #b45309 100%);
    border-bottom: 3px solid #92400e;
    box-shadow: 0 3px 0 #92400e;
}
.pl-header-expense {
    background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 50%, #6d28d9 100%);
    border-bottom: 3px solid #4c1d95;
    box-shadow: 0 3px 0 #4c1d95;
}

/* ── Tables ───────────────────────────────────────────────────────────────── */
.pl-table-wrap { overflow-x: auto; }
.pl-table { width: 100%; border-collapse: collapse; font-size: 13px; }

.pl-table thead tr {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 2px solid #e2e8f0;
    box-shadow: 0 2px 0 rgba(255,255,255,.7) inset;
}
.pl-table th {
    padding: 11px 18px;
    font-size: 10px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .7px;
    color: #64748b;
    text-shadow: 0 1px 0 rgba(255,255,255,.8);
}
.pl-th-code   { width: 145px; }
.pl-th-name   { text-align: left; }
.pl-th-amount { width: 190px; text-align: right; }

.pl-row {
    border-bottom: 1px solid #f0f4f8;
    transition: background .12s, transform .12s, box-shadow .12s;
}
.pl-row:hover {
    background: linear-gradient(90deg, #f8fafc 0%, #eef2ff 100%);
    transform: translateX(3px);
    box-shadow: -3px 0 0 #6366f1;
}
.pl-row:last-child { border-bottom: none; }

.pl-td-code   { padding: 12px 18px; }
.pl-td-name   { padding: 12px 18px; color: #334155; font-weight: 500; }
.pl-td-amount {
    padding: 12px 18px; text-align: right;
    font-family: 'Courier New', monospace; font-weight: 700; font-size: 13px;
}

/* 3D code badge */
.pl-code-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 7px;
    background: linear-gradient(180deg, #f8fafc 0%, #e9eef5 100%);
    color: #475569;
    font-family: 'Courier New', monospace;
    font-size: 12px; font-weight: 700;
    border: 1px solid #dde4ef;
    border-bottom: 2px solid #c8d3e6;
    box-shadow: 0 1px 0 rgba(255,255,255,.9) inset, 0 2px 4px rgba(0,0,0,.06);
    text-shadow: 0 1px 0 rgba(255,255,255,.8);
}

.pl-amount-green  { color: #047857; text-shadow: 0 1px 0 rgba(4,120,87,.15); }
.pl-amount-red    { color: #b91c1c; text-shadow: 0 1px 0 rgba(185,28,28,.15); }
.pl-amount-orange { color: #92400e; text-shadow: 0 1px 0 rgba(146,64,14,.15); }

.pl-empty {
    text-align: center; padding: 28px;
    color: #94a3b8; font-style: italic; font-size: 13px;
}

/* Subtotal row — embossed footer */
.pl-subtotal-row {
    border-top: 2px solid #e2e8f0;
    background: linear-gradient(180deg, #f1f5f9 0%, #e8edf5 100%);
    box-shadow: 0 1px 0 rgba(255,255,255,.7) inset;
}
.pl-subtotal-label {
    padding: 13px 18px;
    font-weight: 800; color: #1e293b; font-size: 13px; text-align: right;
    text-shadow: 0 1px 0 rgba(255,255,255,.7);
}
.pl-subtotal-amount {
    padding: 13px 18px;
    font-weight: 900; font-size: 14px; text-align: right;
    font-family: 'Courier New', monospace;
    text-shadow: 0 1px 0 rgba(255,255,255,.5);
}

/* ── Gross Profit Highlight Bar — 3D raised plaque ───────────────────────── */
.pl-highlight-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 16px;
    padding: 22px 26px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
/* Shine */
.pl-highlight-bar::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 50%;
    background: linear-gradient(180deg, rgba(255,255,255,.35) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
    border-radius: 16px 16px 0 0;
}
.pl-highlight-bar:hover { transform: translateY(-3px); }

.pl-bar-profit {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 60%, #6ee7b7 100%);
    border: 1.5px solid #6ee7b7;
    border-bottom: 5px solid #34d399;
    box-shadow:
        0 8px 0 #a7f3d0,
        0 10px 28px rgba(16,185,129,.3);
}
.pl-bar-loss {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 60%, #fca5a5 100%);
    border: 1.5px solid #fca5a5;
    border-bottom: 5px solid #f87171;
    box-shadow:
        0 8px 0 #fecaca,
        0 10px 28px rgba(239,68,68,.25);
}
.pl-bar-left  { display: flex; align-items: center; gap: 16px; }
.pl-bar-icon  { font-size: 32px; color: #059669; filter: drop-shadow(0 3px 4px rgba(5,150,105,.35)); }
.pl-bar-loss .pl-bar-icon { color: #dc2626; filter: drop-shadow(0 3px 4px rgba(220,38,38,.35)); }
.pl-bar-title {
    font-size: 17px; font-weight: 900; color: #1e293b;
    text-shadow: 0 1px 0 rgba(255,255,255,.8);
}
.pl-bar-sub   { font-size: 12px; color: #475569; margin-top: 3px; }
.pl-bar-right { text-align: right; }
.pl-bar-margin-label {
    font-size: 10px; font-weight: 800; text-transform: uppercase;
    color: #64748b; letter-spacing: .6px;
}
.pl-bar-margin-value {
    font-size: 26px; font-weight: 900; color: #047857; line-height: 1.1;
    text-shadow: 0 2px 4px rgba(4,120,87,.2), 0 1px 0 rgba(255,255,255,.6);
}
.pl-bar-loss .pl-bar-margin-value { color: #b91c1c; text-shadow: 0 2px 4px rgba(185,28,28,.2); }
.pl-bar-amount {
    font-size: 20px; font-weight: 900; color: #047857;
    font-family: 'Courier New', monospace;
    text-shadow: 0 1px 0 rgba(255,255,255,.6);
}
.pl-bar-loss .pl-bar-amount { color: #b91c1c; }

/* ── Net Profit Final Bar — deep 3D slab ─────────────────────────────────── */
.pl-net-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 18px;
    padding: 26px 30px;
    position: relative;
    overflow: hidden;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s;
}
/* Shine stripe */
.pl-net-bar::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 45%;
    background: linear-gradient(180deg, rgba(255,255,255,.2) 0%, rgba(255,255,255,0) 100%);
    border-radius: 18px 18px 0 0;
    pointer-events: none;
}
/* Bottom specular edge */
.pl-net-bar::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0; height: 3px;
    background: rgba(0,0,0,.25);
    border-radius: 0 0 18px 18px;
}
.pl-net-bar:hover {
    transform: perspective(800px) rotateX(3deg) translateY(-4px);
}

.pl-net-profit {
    background: linear-gradient(145deg, #22c55e 0%, #16a34a 40%, #15803d 70%, #14532d 100%);
    border-bottom: 8px solid #052e16;
    box-shadow:
        0 1px 0 rgba(255,255,255,.2) inset,
        0 12px 0 #052e16,
        0 18px 40px rgba(21,128,61,.55);
}
.pl-net-profit:hover {
    box-shadow: 0 1px 0 rgba(255,255,255,.2) inset, 0 18px 0 #052e16, 0 26px 50px rgba(21,128,61,.65);
}

.pl-net-loss {
    background: linear-gradient(145deg, #f87171 0%, #dc2626 40%, #b91c1c 70%, #7f1d1d 100%);
    border-bottom: 8px solid #450a0a;
    box-shadow:
        0 1px 0 rgba(255,255,255,.2) inset,
        0 12px 0 #450a0a,
        0 18px 40px rgba(185,28,28,.55);
}
.pl-net-loss:hover {
    box-shadow: 0 1px 0 rgba(255,255,255,.2) inset, 0 18px 0 #450a0a, 0 26px 50px rgba(185,28,28,.65);
}

.pl-net-left { display: flex; align-items: center; gap: 18px; }
.pl-net-icon-wrap {
    width: 58px; height: 58px;
    border-radius: 16px;
    background: rgba(0,0,0,.22);
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; color: #fff;
    box-shadow: 0 2px 0 rgba(0,0,0,.25), 0 1px 0 rgba(255,255,255,.18) inset;
    flex-shrink: 0;
}
.pl-net-title {
    font-size: 19px; font-weight: 900; color: #fff; letter-spacing: .5px;
    text-shadow: 0 2px 6px rgba(0,0,0,.4), 0 1px 0 rgba(255,255,255,.1);
}
.pl-net-formula { font-size: 12px; color: rgba(255,255,255,.7); margin-top: 3px; }
.pl-net-period  { font-size: 11px; color: rgba(255,255,255,.5); margin-top: 2px; }

.pl-net-right  { text-align: right; }
.pl-net-margin-label {
    font-size: 10px; font-weight: 800; text-transform: uppercase;
    color: rgba(255,255,255,.6); letter-spacing: .6px;
}
.pl-net-margin-value {
    font-size: 22px; font-weight: 900; color: rgba(255,255,255,.95); line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0,0,0,.3);
}
.pl-net-amount {
    font-size: 32px; font-weight: 900; color: #fff;
    font-family: 'Courier New', monospace; line-height: 1.15; margin-top: 4px;
    text-shadow: 0 3px 8px rgba(0,0,0,.4), 0 1px 0 rgba(255,255,255,.15);
    letter-spacing: -1px;
}
.pl-net-status {
    font-size: 12px; font-weight: 800; color: rgba(255,255,255,.85);
    letter-spacing: 2px; margin-top: 4px;
    text-shadow: 0 1px 3px rgba(0,0,0,.35);
}

/* ── Print Styles ─────────────────────────────────────────────────────────── */
.print-header { display: none; }

@media print {
    .no-print { display: none !important; }
    .pl-page-wrap { padding: 0 !important; background: #fff !important; }
    .print-header { display: block; text-align: center; margin-bottom: 20px; }
    .print-header h1 { font-size: 20px; margin: 0; }
    .print-header p  { color: #666; margin: 4px 0 0; }

    .pl-stat-card, .pl-section-card, .pl-highlight-bar, .pl-net-bar {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        page-break-inside: avoid;
        transform: none !important;
    }
    .pl-net-profit, .pl-net-loss {
        background: #f9f9f9 !important;
        border: 2px solid #333 !important;
    }
    .pl-net-title, .pl-net-amount { color: #000 !important; text-shadow: none !important; }
    .pl-net-formula, .pl-net-period, .pl-net-margin-label, .pl-net-margin-value,
    .pl-net-status { color: #333 !important; text-shadow: none !important; }
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 1100px) {
    .pl-summary-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .pl-filter-fields { flex-direction: column; align-items: flex-start; }
    .pl-datepicker { width: 100% !important; }
    .pl-summary-grid { grid-template-columns: 1fr; gap: 14px; }
    .pl-page-wrap { padding: 0 12px 36px; }
    .pl-filter-bar { padding: 14px 16px; }
    .pl-highlight-bar { flex-direction: column; gap: 16px; text-align: center; }
    .pl-bar-right { text-align: center; }
    .pl-net-bar { flex-direction: column; gap: 18px; }
    .pl-net-right { text-align: center; }
    .pl-section-header { flex-direction: column; align-items: flex-start; gap: 6px; }
    .pl-period-header { flex-direction: column; gap: 10px; }
    .pl-stat-card:hover { transform: none; }
    .pl-section-card:hover { transform: none; }
}
</style>
