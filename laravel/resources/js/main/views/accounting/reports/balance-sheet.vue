<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Balance Sheet" class="p-0">
                <template #extra>
                    <a-button @click="print" class="bs-btn bs-btn-outline">
                        <PrinterOutlined /> Print
                    </a-button>
                    <a-button type="primary" @click="print" class="bs-btn">
                        <FilePdfOutlined /> Export PDF
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
                <a-breadcrumb-item>Balance Sheet</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- ── Filter Bar ─────────────────────────────────────────────────────── -->
    <div class="bs-filter-bar no-print">
        <div class="bs-filter-inner">
            <div class="bs-filter-fields">
                <div class="bs-field-group">
                    <label class="bs-label">As of Date</label>
                    <a-date-picker v-model:value="filters.as_of" class="bs-datepicker" />
                </div>
                <a-button type="primary" :loading="loading" @click="load" class="bs-generate-btn">
                    <SearchOutlined />
                    <span>Generate Report</span>
                </a-button>
            </div>
            <!-- Quick Presets -->
            <div class="bs-quick-filters">
                <span class="bs-quick-label">Quick:</span>
                <button class="bs-quick-btn" @click="setQuick('today')">Today</button>
                <button class="bs-quick-btn" @click="setQuick('month')">End of Month</button>
                <button class="bs-quick-btn" @click="setQuick('quarter')">End of Quarter</button>
                <button class="bs-quick-btn" @click="setQuick('year')">End of Year</button>
                <button class="bs-quick-btn" @click="setQuick('lastyear')">Last Year End</button>
            </div>
        </div>
    </div>

    <!-- ── Main Content ───────────────────────────────────────────────────── -->
    <div class="bs-page-wrap">
        <a-spin :spinning="loading">
            <div id="printable-area">

                <!-- Print Header -->
                <div class="bs-print-header">
                    <h1>Balance Sheet</h1>
                    <p>As of {{ formatDate(data.as_of) }}</p>
                </div>

                <!-- ── Summary Cards ──────────────────────────────────────── -->
                <div class="bs-summary-grid no-print">

                    <!-- Total Assets -->
                    <div class="bs-stat-card bs-stat-assets">
                        <div class="bs-stat-top">
                            <div class="bs-stat-icon-wrap">
                                <BankOutlined />
                            </div>
                            <div class="bs-stat-badge">{{ assetRows.length }} accounts</div>
                        </div>
                        <div class="bs-stat-label">Total Assets</div>
                        <div class="bs-stat-value">{{ fmt(data.total_assets) }}</div>
                        <div class="bs-stat-sub">Everything the company owns</div>
                    </div>

                    <!-- Total Liabilities -->
                    <div class="bs-stat-card bs-stat-liabilities">
                        <div class="bs-stat-top">
                            <div class="bs-stat-icon-wrap">
                                <CreditCardOutlined />
                            </div>
                            <div class="bs-stat-badge">{{ liabilityRows.length }} accounts</div>
                        </div>
                        <div class="bs-stat-label">Total Liabilities</div>
                        <div class="bs-stat-value">{{ fmt(data.total_liabilities) }}</div>
                        <div class="bs-stat-sub">Everything the company owes</div>
                    </div>

                    <!-- Total Equity -->
                    <div class="bs-stat-card bs-stat-equity">
                        <div class="bs-stat-top">
                            <div class="bs-stat-icon-wrap">
                                <PieChartOutlined />
                            </div>
                            <div class="bs-stat-badge">{{ equityRows.length }} accounts</div>
                        </div>
                        <div class="bs-stat-label">Total Equity</div>
                        <div class="bs-stat-value">{{ fmt(data.total_equity) }}</div>
                        <div class="bs-stat-sub">Owners' stake in the company</div>
                    </div>

                    <!-- Balance Status -->
                    <div class="bs-stat-card" :class="isBalanced ? 'bs-stat-balanced' : 'bs-stat-unbalanced'">
                        <div class="bs-stat-top">
                            <div class="bs-stat-icon-wrap">
                                <CheckCircleOutlined v-if="isBalanced" />
                                <WarningOutlined v-else />
                            </div>
                            <div class="bs-stat-badge">{{ isBalanced ? 'Verified' : 'Error' }}</div>
                        </div>
                        <div class="bs-stat-label">Balance Status</div>
                        <div class="bs-stat-value bs-balance-eq">A = L + E</div>
                        <div class="bs-stat-sub">{{ isBalanced ? 'Sheet is balanced' : 'Sheet is NOT balanced' }}</div>
                    </div>
                </div>

                <!-- ── Equation Banner ────────────────────────────────────── -->
                <div class="bs-equation-bar no-print">
                    <div class="bs-eq-left">
                        <CalendarOutlined class="bs-eq-icon" />
                        <span class="bs-eq-date">As of {{ formatDate(data.as_of) }}</span>
                    </div>
                    <div class="bs-eq-formula">
                        <span class="bs-eq-box bs-eq-assets">
                            <span class="bs-eq-label">Assets</span>
                            <span class="bs-eq-num">{{ fmt(data.total_assets) }}</span>
                        </span>
                        <span class="bs-eq-sign">=</span>
                        <span class="bs-eq-box bs-eq-liab">
                            <span class="bs-eq-label">Liabilities</span>
                            <span class="bs-eq-num">{{ fmt(data.total_liabilities) }}</span>
                        </span>
                        <span class="bs-eq-sign">+</span>
                        <span class="bs-eq-box bs-eq-equity">
                            <span class="bs-eq-label">Equity</span>
                            <span class="bs-eq-num">{{ fmt(data.total_equity) }}</span>
                        </span>
                        <span class="bs-eq-status" :class="isBalanced ? 'bs-eq-ok' : 'bs-eq-err'">
                            {{ isBalanced ? '✓ Balanced' : '⚠ Unbalanced' }}
                        </span>
                    </div>
                </div>

                <!-- ── Two-Column Layout ──────────────────────────────────── -->
                <div class="bs-columns">

                    <!-- LEFT — Assets ────────────────────────────────────── -->
                    <div class="bs-column">
                        <div class="bs-section-card bs-card-assets">
                            <div class="bs-section-header bs-header-assets">
                                <div class="bs-section-title">
                                    <BankOutlined class="bs-section-icon" />
                                    <span>ASSETS</span>
                                </div>
                                <div class="bs-section-count">{{ assetRows.length }} accounts</div>
                            </div>

                            <div class="bs-table-wrap">
                                <table class="bs-table">
                                    <thead>
                                        <tr>
                                            <th class="bs-th-code">Code</th>
                                            <th class="bs-th-name">Account Name</th>
                                            <th class="bs-th-amount">Balance (PKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="assetRows.length === 0">
                                            <td colspan="3" class="bs-empty">No asset accounts found</td>
                                        </tr>
                                        <tr v-for="r in assetRows" :key="r.account_code" class="bs-row bs-row-asset">
                                            <td class="bs-td-code">
                                                <span class="bs-code-badge">{{ r.account_code }}</span>
                                            </td>
                                            <td class="bs-td-name">{{ r.account_name }}</td>
                                            <td class="bs-td-amount bs-amount-blue">{{ fmt(r.balance) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bs-total-row bs-total-assets">
                                            <td colspan="2" class="bs-total-label">
                                                <BankOutlined /> &nbsp;Total Assets
                                            </td>
                                            <td class="bs-total-amount bs-amount-blue">
                                                {{ fmt(data.total_assets) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT — Liabilities + Equity ─────────────────────── -->
                    <div class="bs-column">

                        <!-- Liabilities -->
                        <div class="bs-section-card bs-card-liab" style="margin-bottom:20px">
                            <div class="bs-section-header bs-header-liab">
                                <div class="bs-section-title">
                                    <CreditCardOutlined class="bs-section-icon" />
                                    <span>LIABILITIES</span>
                                </div>
                                <div class="bs-section-count">{{ liabilityRows.length }} accounts</div>
                            </div>
                            <div class="bs-table-wrap">
                                <table class="bs-table">
                                    <thead>
                                        <tr>
                                            <th class="bs-th-code">Code</th>
                                            <th class="bs-th-name">Account Name</th>
                                            <th class="bs-th-amount">Balance (PKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="liabilityRows.length === 0">
                                            <td colspan="3" class="bs-empty">No liability accounts found</td>
                                        </tr>
                                        <tr v-for="r in liabilityRows" :key="r.account_code" class="bs-row bs-row-liab">
                                            <td class="bs-td-code">
                                                <span class="bs-code-badge">{{ r.account_code }}</span>
                                            </td>
                                            <td class="bs-td-name">{{ r.account_name }}</td>
                                            <td class="bs-td-amount bs-amount-red">{{ fmt(Math.abs(r.balance)) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bs-total-row bs-total-liab">
                                            <td colspan="2" class="bs-total-label">
                                                <CreditCardOutlined /> &nbsp;Total Liabilities
                                            </td>
                                            <td class="bs-total-amount bs-amount-red">
                                                {{ fmt(data.total_liabilities) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Equity -->
                        <div class="bs-section-card bs-card-equity">
                            <div class="bs-section-header bs-header-equity">
                                <div class="bs-section-title">
                                    <PieChartOutlined class="bs-section-icon" />
                                    <span>EQUITY</span>
                                </div>
                                <div class="bs-section-count">{{ equityRows.length }} accounts</div>
                            </div>
                            <div class="bs-table-wrap">
                                <table class="bs-table">
                                    <thead>
                                        <tr>
                                            <th class="bs-th-code">Code</th>
                                            <th class="bs-th-name">Account Name</th>
                                            <th class="bs-th-amount">Balance (PKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="equityRows.length === 0">
                                            <td colspan="3" class="bs-empty">No equity accounts found</td>
                                        </tr>
                                        <tr v-for="r in equityRows" :key="r.account_code" class="bs-row bs-row-equity">
                                            <td class="bs-td-code">
                                                <span class="bs-code-badge">{{ r.account_code }}</span>
                                            </td>
                                            <td class="bs-td-name">{{ r.account_name }}</td>
                                            <td class="bs-td-amount bs-amount-purple">{{ fmt(Math.abs(r.balance)) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bs-total-row bs-total-equity">
                                            <td colspan="2" class="bs-total-label">
                                                <PieChartOutlined /> &nbsp;Total Equity
                                            </td>
                                            <td class="bs-total-amount bs-amount-purple">
                                                {{ fmt(data.total_equity) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Liabilities + Equity Total -->
                        <div class="bs-le-total" :class="isBalanced ? 'bs-le-balanced' : 'bs-le-unbalanced'">
                            <div class="bs-le-left">
                                <div class="bs-le-icon">
                                    <CheckCircleOutlined v-if="isBalanced" />
                                    <WarningOutlined v-else />
                                </div>
                                <div>
                                    <div class="bs-le-title">Total Liabilities + Equity</div>
                                    <div class="bs-le-sub">{{ isBalanced ? '✓ Balance sheet is balanced — Assets = Liabilities + Equity' : '⚠ Balance sheet is NOT balanced' }}</div>
                                </div>
                            </div>
                            <div class="bs-le-amount">
                                {{ fmt(+data.total_liabilities + +data.total_equity) }}
                            </div>
                        </div>

                    </div><!-- /right column -->
                </div><!-- /bs-columns -->

            </div><!-- /#printable-area -->
        </a-spin>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import dayjs from 'dayjs';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import {
    PrinterOutlined, SearchOutlined, FilePdfOutlined,
    BankOutlined, CreditCardOutlined, PieChartOutlined,
    CheckCircleOutlined, WarningOutlined, CalendarOutlined,
} from '@ant-design/icons-vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        PrinterOutlined, SearchOutlined, FilePdfOutlined,
        BankOutlined, CreditCardOutlined, PieChartOutlined,
        CheckCircleOutlined, WarningOutlined, CalendarOutlined,
    },

    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading = ref(false);
        const filters = ref({ as_of: dayjs() });
        const data = ref({
            data: [],
            total_assets: 0,
            total_liabilities: 0,
            total_equity: 0,
            as_of: '',
        });

        const assetRows     = computed(() => (data.value.data || []).filter(r => r.account_type === 'Asset'));
        const liabilityRows = computed(() => (data.value.data || []).filter(r => r.account_type === 'Liability'));
        const equityRows    = computed(() => (data.value.data || []).filter(r => r.account_type === 'Equity'));
        const isBalanced    = computed(() =>
            Math.abs((+data.value.total_assets) - ((+data.value.total_liabilities) + (+data.value.total_equity))) < 1
        );

        const fmt        = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        const setQuick = (preset) => {
            const now = dayjs();
            const map = {
                today:    now,
                month:    now.endOf('month'),
                quarter:  now.endOf('quarter'),
                year:     now.endOf('year'),
                lastyear: now.subtract(1, 'year').endOf('year'),
            };
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
            } catch (e) {
                /* silent */
            } finally {
                loading.value = false;
            }
        };

        const print = () => window.print();

        onMounted(load);

        return {
            loading, filters, data,
            assetRows, liabilityRows, equityRows, isBalanced,
            fmt, formatDate,
            load, setQuick, print,
        };
    },
});
</script>

<style scoped>
/* ── Page Wrap ────────────────────────────────────────────────────────────── */
.bs-page-wrap {
    padding: 0 24px 48px;
    background: linear-gradient(150deg, #f0f6ff 0%, #f8f4ff 50%, #f0fff8 100%);
    min-height: 100%;
}

/* ── Filter Bar ───────────────────────────────────────────────────────────── */
.bs-filter-bar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.bs-filter-inner { max-width: 1200px; }
.bs-filter-fields {
    display: flex; align-items: flex-end; gap: 16px;
    flex-wrap: wrap; margin-bottom: 12px;
}
.bs-field-group { display: flex; flex-direction: column; gap: 4px; }
.bs-label {
    font-size: 11px; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .5px;
}
.bs-datepicker { width: 200px !important; }

.bs-generate-btn {
    height: 36px !important; padding: 0 20px !important;
    border-radius: 8px !important; font-weight: 700 !important;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(29,78,216,.35) !important;
    transition: all .15s !important;
}
.bs-generate-btn:hover {
    background: linear-gradient(135deg, #60a5fa, #2563eb) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 18px rgba(29,78,216,.45) !important;
}

.bs-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.bs-quick-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.bs-quick-btn {
    padding: 4px 13px; border-radius: 20px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    color: #475569; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: all .15s;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.bs-quick-btn:hover {
    border-color: #3b82f6; color: #1d4ed8;
    background: #eff6ff; box-shadow: 0 3px 8px rgba(59,130,246,.2);
    transform: translateY(-1px);
}
.bs-quick-btn:active { transform: translateY(1px); box-shadow: none; }

/* ── Header Buttons ───────────────────────────────────────────────────────── */
.bs-btn {
    height: 34px !important; border-radius: 8px !important;
    font-weight: 600 !important;
}
.bs-btn-outline { border-color: #cbd5e1 !important; color: #475569 !important; }

/* ── Summary Cards ────────────────────────────────────────────────────────── */
.bs-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}
.bs-stat-card {
    border-radius: 16px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
    cursor: default;
}
.bs-stat-card:hover { transform: translateY(-3px); }

/* Shine overlay */
.bs-stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 50%;
    background: linear-gradient(180deg, rgba(255,255,255,.2) 0%, rgba(255,255,255,0) 100%);
    border-radius: 16px 16px 0 0;
    pointer-events: none;
}

.bs-stat-assets {
    background: linear-gradient(145deg, #60a5fa 0%, #2563eb 55%, #1d4ed8 100%);
    box-shadow: 0 8px 24px rgba(37,99,235,.35);
}
.bs-stat-liabilities {
    background: linear-gradient(145deg, #f87171 0%, #dc2626 55%, #b91c1c 100%);
    box-shadow: 0 8px 24px rgba(220,38,38,.35);
}
.bs-stat-equity {
    background: linear-gradient(145deg, #c084fc 0%, #9333ea 55%, #7e22ce 100%);
    box-shadow: 0 8px 24px rgba(147,51,234,.35);
}
.bs-stat-balanced {
    background: linear-gradient(145deg, #4ade80 0%, #16a34a 55%, #15803d 100%);
    box-shadow: 0 8px 24px rgba(22,163,74,.35);
}
.bs-stat-unbalanced {
    background: linear-gradient(145deg, #fbbf24 0%, #d97706 55%, #b45309 100%);
    box-shadow: 0 8px 24px rgba(217,119,6,.35);
}

.bs-stat-top {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 14px;
}
.bs-stat-icon-wrap {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(0,0,0,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
}
.bs-stat-badge {
    padding: 3px 10px; border-radius: 20px;
    background: rgba(0,0,0,.18); color: rgba(255,255,255,.9);
    font-size: 11px; font-weight: 700;
}
.bs-stat-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: rgba(255,255,255,.8); margin-bottom: 6px;
}
.bs-stat-value {
    font-size: 20px; font-weight: 900; color: #fff;
    font-family: 'Courier New', monospace; line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0,0,0,.2);
}
.bs-balance-eq { font-size: 24px; letter-spacing: 2px; }
.bs-stat-sub { font-size: 11px; color: rgba(255,255,255,.65); margin-top: 6px; }

/* ── Equation Banner ──────────────────────────────────────────────────────── */
.bs-equation-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 22px;
    margin-bottom: 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    flex-wrap: wrap;
    gap: 12px;
}
.bs-eq-left { display: flex; align-items: center; gap: 8px; }
.bs-eq-icon { color: #3b82f6; font-size: 17px; }
.bs-eq-date { font-size: 13px; font-weight: 700; color: #1e293b; }
.bs-eq-formula {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.bs-eq-box {
    display: flex; flex-direction: column; align-items: center;
    padding: 8px 16px; border-radius: 10px; min-width: 110px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.bs-eq-assets  { background: #eff6ff; border: 1.5px solid #bfdbfe; }
.bs-eq-liab    { background: #fff1f2; border: 1.5px solid #fecdd3; }
.bs-eq-equity  { background: #faf5ff; border: 1.5px solid #e9d5ff; }
.bs-eq-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #64748b;
}
.bs-eq-num {
    font-size: 13px; font-weight: 800; font-family: 'Courier New', monospace;
    margin-top: 3px;
}
.bs-eq-assets .bs-eq-num  { color: #1d4ed8; }
.bs-eq-liab   .bs-eq-num  { color: #dc2626; }
.bs-eq-equity .bs-eq-num  { color: #7c3aed; }
.bs-eq-sign {
    font-size: 22px; font-weight: 900; color: #94a3b8;
    line-height: 1;
}
.bs-eq-status {
    padding: 6px 16px; border-radius: 20px;
    font-size: 12px; font-weight: 800;
}
.bs-eq-ok  { background: #dcfce7; color: #15803d; }
.bs-eq-err { background: #fff3cd; color: #92400e; }

/* ── Two-Column Layout ────────────────────────────────────────────────────── */
.bs-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
    align-items: start;
}

/* ── Section Cards ────────────────────────────────────────────────────────── */
.bs-section-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    border: 1px solid #e8ecf5;
    transition: box-shadow .2s;
}
.bs-section-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.12); }

.bs-section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; color: #fff; font-weight: 800; font-size: 14px;
    position: relative; overflow: hidden;
}
.bs-section-header::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 45%;
    background: linear-gradient(180deg, rgba(255,255,255,.18) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}
.bs-section-title {
    display: flex; align-items: center; gap: 9px;
    text-shadow: 0 1px 3px rgba(0,0,0,.25);
    letter-spacing: .5px;
}
.bs-section-icon { font-size: 17px; }
.bs-section-count {
    font-size: 12px; font-weight: 600;
    background: rgba(0,0,0,.18); color: rgba(255,255,255,.9);
    padding: 2px 10px; border-radius: 20px;
}

.bs-header-assets  { background: linear-gradient(135deg, #60a5fa, #1d4ed8); }
.bs-header-liab    { background: linear-gradient(135deg, #f87171, #b91c1c); }
.bs-header-equity  { background: linear-gradient(135deg, #c084fc, #7e22ce); }

/* ── Tables ───────────────────────────────────────────────────────────────── */
.bs-table-wrap { overflow-x: auto; }
.bs-table { width: 100%; border-collapse: collapse; font-size: 13px; }

.bs-table thead tr {
    background: linear-gradient(180deg, #f8fafc, #f1f5f9);
    border-bottom: 2px solid #e2e8f0;
}
.bs-table th {
    padding: 10px 16px;
    font-size: 10px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .6px; color: #64748b;
}
.bs-th-code   { width: 120px; }
.bs-th-name   { text-align: left; }
.bs-th-amount { width: 160px; text-align: right; }

.bs-row { border-bottom: 1px solid #f0f4f8; transition: background .12s, transform .12s; }
.bs-row:last-child { border-bottom: none; }

.bs-row-asset:hover  { background: #eff6ff; transform: translateX(3px); box-shadow: -3px 0 0 #3b82f6; }
.bs-row-liab:hover   { background: #fff1f2; transform: translateX(3px); box-shadow: -3px 0 0 #dc2626; }
.bs-row-equity:hover { background: #faf5ff; transform: translateX(3px); box-shadow: -3px 0 0 #9333ea; }

.bs-td-code   { padding: 11px 16px; }
.bs-td-name   { padding: 11px 16px; color: #334155; font-weight: 500; }
.bs-td-amount {
    padding: 11px 16px; text-align: right;
    font-family: 'Courier New', monospace; font-weight: 700; font-size: 13px;
}

.bs-code-badge {
    display: inline-block; padding: 2px 8px; border-radius: 6px;
    background: #f1f5f9; color: #475569;
    font-family: 'Courier New', monospace; font-size: 11px; font-weight: 700;
    border: 1px solid #e2e8f0;
}

.bs-amount-blue   { color: #1d4ed8; }
.bs-amount-red    { color: #b91c1c; }
.bs-amount-purple { color: #7e22ce; }

.bs-empty {
    text-align: center; padding: 24px;
    color: #94a3b8; font-style: italic; font-size: 13px;
}

/* Total Row */
.bs-total-row { border-top: 2px solid #e2e8f0; }
.bs-total-assets { background: linear-gradient(90deg, #eff6ff, #dbeafe); }
.bs-total-liab   { background: linear-gradient(90deg, #fff1f2, #ffe4e6); }
.bs-total-equity { background: linear-gradient(90deg, #faf5ff, #f3e8ff); }

.bs-total-label {
    padding: 13px 16px; font-weight: 800; color: #1e293b; font-size: 13px;
    text-align: right;
}
.bs-total-amount {
    padding: 13px 16px; font-weight: 900; font-size: 15px;
    text-align: right; font-family: 'Courier New', monospace;
}

/* ── L + E Total Banner ───────────────────────────────────────────────────── */
.bs-le-total {
    margin-top: 0;
    display: flex; align-items: center; justify-content: space-between;
    border-radius: 14px; padding: 20px 22px;
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
    transition: transform .2s;
}
.bs-le-total:hover { transform: translateY(-2px); }

.bs-le-balanced   { background: linear-gradient(135deg, #14532d, #16a34a); }
.bs-le-unbalanced { background: linear-gradient(135deg, #92400e, #d97706); }

.bs-le-left { display: flex; align-items: center; gap: 14px; }
.bs-le-icon {
    width: 48px; height: 48px; border-radius: 13px;
    background: rgba(0,0,0,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff; flex-shrink: 0;
}
.bs-le-title {
    font-size: 15px; font-weight: 900; color: #fff;
    text-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.bs-le-sub { font-size: 11px; color: rgba(255,255,255,.7); margin-top: 3px; }
.bs-le-amount {
    font-size: 22px; font-weight: 900; color: #fff;
    font-family: 'Courier New', monospace;
    text-shadow: 0 2px 6px rgba(0,0,0,.3);
}

/* ── Print ────────────────────────────────────────────────────────────────── */
.bs-print-header { display: none; }

@media print {
    .no-print { display: none !important; }
    .bs-page-wrap { padding: 0 !important; background: #fff !important; }
    .bs-print-header { display: block; text-align: center; margin-bottom: 20px; }
    .bs-print-header h1 { font-size: 22px; margin: 0; }
    .bs-print-header p  { color: #555; margin: 4px 0 0; }
    .bs-section-card    { box-shadow: none !important; border: 1px solid #ccc !important; page-break-inside: avoid; }
    .bs-le-total        { box-shadow: none !important; background: #f0f0f0 !important; }
    .bs-le-title, .bs-le-sub, .bs-le-amount { color: #000 !important; text-shadow: none !important; }
    .bs-stat-card       { display: none; }
    .bs-equation-bar    { box-shadow: none !important; }
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 1100px) {
    .bs-summary-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 860px) {
    .bs-columns { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .bs-summary-grid { grid-template-columns: 1fr; }
    .bs-page-wrap { padding: 0 12px 36px; }
    .bs-filter-bar { padding: 12px 16px; }
    .bs-filter-fields { flex-direction: column; align-items: flex-start; }
    .bs-datepicker { width: 100% !important; }
    .bs-equation-bar { flex-direction: column; align-items: flex-start; }
    .bs-eq-formula { justify-content: flex-start; }
    .bs-le-total { flex-direction: column; gap: 12px; }
    .bs-le-amount { text-align: left; }
}
</style>
