<template>
    <div class="bsd-root">
        <AdminPageHeader />

        <!-- ── Filter Bar ─────────────────────────────────────────────── -->
        <div class="bsd-filter-bar">
            <div class="bsd-filter-row">
                <div class="bsd-title">
                    <span class="bsd-title-icon"><BarChartOutlined /></span>
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
                        <template #icon><SearchOutlined /></template> Refresh
                    </a-button>
                    <a-button size="small" type="default" style="font-weight:600;color:#1677ff;border-color:#1677ff"
                        @click="$router.push({ name: 'admin.accounting.balance_sheet_report' })">
                        <template #icon><FileTextOutlined /></template> Reports
                    </a-button>
                    <a-button size="small" @click="print">
                        <template #icon><PrinterOutlined /></template>
                    </a-button>
                </div>
            </div>
        </div>

        <div class="bsd-body" v-if="!loading && data">
            <!-- ── Top KPI Row ─────────────────────────────────────────── -->
            <div class="bsd-kpi-row">
                <div class="bsd-kpi-card">
                    <div class="bsd-kpi-label">Working Capital <InfoCircleOutlined class="bsd-info" /></div>
                    <div class="bsd-kpi-value">${{ fmtNum(r.working_capital) }}</div>
                </div>
                <div class="bsd-kpi-card">
                    <div class="bsd-kpi-label">Current Ratio <InfoCircleOutlined class="bsd-info" /></div>
                    <div class="bsd-kpi-value">{{ r.current_ratio }} %</div>
                </div>
                <div class="bsd-kpi-card">
                    <div class="bsd-kpi-label">Liquidity Ratio <InfoCircleOutlined class="bsd-info" /></div>
                    <div class="bsd-kpi-value">{{ r.liquidity_ratio }}%</div>
                </div>
                <div class="bsd-kpi-card">
                    <div class="bsd-kpi-label">Quick Ratio <InfoCircleOutlined class="bsd-info" /></div>
                    <div class="bsd-kpi-value">{{ r.quick_ratio }}%</div>
                </div>
                <div class="bsd-kpi-card">
                    <div class="bsd-kpi-label">Debt to Asset Ratio (DAR) <InfoCircleOutlined class="bsd-info" /></div>
                    <div class="bsd-kpi-value">{{ r.dar }}%</div>
                </div>
            </div>

            <!-- ── Main 3-column Row ───────────────────────────────────── -->
            <div class="bsd-main-row">
                <!-- Left: Balance Sheet Table -->
                <div class="bsd-card bsd-bs-table">
                    <div class="bsd-card-title">Balance Sheet</div>
                    <table class="bsd-tbl">
                        <thead>
                            <tr><th>Line Items</th><th class="bsd-right">Balance</th></tr>
                        </thead>
                        <tbody>
                            <!-- Assets -->
                            <tr class="bsd-section-hdr"><td colspan="2"><strong>1 &nbsp; Assets</strong></td></tr>
                            <tr v-for="row in assetRows" :key="row.id">
                                <td>&nbsp;&nbsp;{{ row.account_name }}</td>
                                <td class="bsd-right">$ {{ fmtNum(row.balance) }}</td>
                            </tr>
                            <tr class="bsd-total-row bsd-blue">
                                <td><strong>Total Assets</strong></td>
                                <td class="bsd-right"><strong>$ {{ fmtNum(snap.total_assets) }}</strong></td>
                            </tr>

                            <!-- Liabilities -->
                            <tr class="bsd-section-hdr"><td colspan="2"><strong>2 &nbsp; Liabilities</strong></td></tr>
                            <tr v-for="row in liabRows" :key="row.id">
                                <td>&nbsp;&nbsp;{{ row.account_name }}</td>
                                <td class="bsd-right">$ {{ fmtNum(Math.abs(row.balance)) }}</td>
                            </tr>
                            <tr class="bsd-total-row bsd-red">
                                <td><strong>Total Liabilities</strong></td>
                                <td class="bsd-right"><strong>$ {{ fmtNum(snap.total_liabilities) }}</strong></td>
                            </tr>

                            <!-- Equity -->
                            <tr class="bsd-section-hdr"><td colspan="2"><strong>3 &nbsp; Equity</strong></td></tr>
                            <tr>
                                <td>&nbsp;&nbsp;Total Assets</td>
                                <td class="bsd-right">$ {{ fmtNum(snap.total_assets) }}</td>
                            </tr>
                            <tr>
                                <td>&nbsp;&nbsp;Total Liabilities</td>
                                <td class="bsd-right bsd-minus">(-) &nbsp; $ {{ fmtNum(snap.total_liabilities) }}</td>
                            </tr>
                            <tr class="bsd-total-row bsd-purple">
                                <td><strong>Total Equity</strong></td>
                                <td class="bsd-right"><strong>$ {{ fmtNum(snap.total_equity) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Center: Charts -->
                <div class="bsd-center-col">
                    <!-- AR vs AP Turnover chart -->
                    <div class="bsd-card bsd-chart-card">
                        <div class="bsd-card-title">AR Turnover vs. AP Turnover <InfoCircleOutlined class="bsd-info" /></div>
                        <div style="height:160px">
                            <BarChart v-if="arApChartData" :chart-data="arApChartData" :options="barOpts" />
                        </div>
                    </div>
                    <!-- Cash by Year chart -->
                    <div class="bsd-card bsd-chart-card">
                        <div class="bsd-card-title">Overall Cash by Year <InfoCircleOutlined class="bsd-info" /></div>
                        <div style="height:160px">
                            <BarChart v-if="cashChartData" :chart-data="cashChartData" :options="cashBarOpts" />
                        </div>
                    </div>
                </div>

                <!-- Right: Ratio tiles -->
                <div class="bsd-right-col">
                    <div class="bsd-ratio-tile">
                        <div class="bsd-ratio-label">Debt to Equity Ratio (DER) <InfoCircleOutlined class="bsd-info" /></div>
                        <div class="bsd-ratio-value">{{ r.der }}%</div>
                    </div>
                    <div class="bsd-ratio-tile">
                        <div class="bsd-ratio-label">Inventory to Sales <InfoCircleOutlined class="bsd-info" /></div>
                        <div class="bsd-ratio-value">{{ r.inv_to_sales }}%</div>
                    </div>
                    <div class="bsd-ratio-tile">
                        <div class="bsd-ratio-label">InventoryTurnover <InfoCircleOutlined class="bsd-info" /></div>
                        <div class="bsd-ratio-value">{{ r.inv_turnover }}</div>
                    </div>
                    <div class="bsd-ratio-tile">
                        <div class="bsd-ratio-label">Days Sales in Inventory (DSI) <InfoCircleOutlined class="bsd-info" /></div>
                        <div class="bsd-ratio-value">{{ r.dsi }} Day(s)</div>
                    </div>
                    <div class="bsd-ratio-tile">
                        <div class="bsd-ratio-label">Cash Balance <InfoCircleOutlined class="bsd-info" /></div>
                        <div class="bsd-ratio-value bsd-green">${{ fmtNum(r.cash_balance) }}</div>
                    </div>
                </div>
            </div>

            <!-- ── Gauge Row ───────────────────────────────────────────── -->
            <div class="bsd-gauge-row">
                <div class="bsd-gauge-card">
                    <div class="bsd-gauge-title">Days Sales Outstand... <InfoCircleOutlined class="bsd-info" /></div>
                    <Gauge :value="r.dso" :max="gaugeMax(r.dso)" color="#7c3aed" />
                    <div class="bsd-gauge-footer">
                        <span>0</span>
                        <span class="bsd-gauge-center">{{ r.dso }} day(s)</span>
                        <span>{{ gaugeMax(r.dso) }}</span>
                    </div>
                </div>
                <div class="bsd-gauge-card">
                    <div class="bsd-gauge-title">Days Inventory Outst... <InfoCircleOutlined class="bsd-info" /></div>
                    <Gauge :value="r.dsi" :max="gaugeMax(r.dsi)" color="#2563eb" />
                    <div class="bsd-gauge-footer">
                        <span>0</span>
                        <span class="bsd-gauge-center">{{ r.dsi }} day(s)</span>
                        <span>{{ gaugeMax(r.dsi) }}</span>
                    </div>
                </div>
                <div class="bsd-gauge-card">
                    <div class="bsd-gauge-title">Days Payable Outsta... <InfoCircleOutlined class="bsd-info" /></div>
                    <Gauge :value="r.dpo" :max="gaugeMax(r.dpo)" color="#0d9488" />
                    <div class="bsd-gauge-footer">
                        <span>0</span>
                        <span class="bsd-gauge-center">{{ r.dpo }} day(s)</span>
                        <span>{{ gaugeMax(r.dpo) }}</span>
                    </div>
                </div>
                <div class="bsd-gauge-card">
                    <div class="bsd-gauge-title">Cash-to-Cash Cycle (... <InfoCircleOutlined class="bsd-info" /></div>
                    <Gauge :value="Math.abs(r.c2c)" :max="gaugeMax(Math.abs(r.c2c))" color="#ea580c" />
                    <div class="bsd-gauge-footer">
                        <span>0</span>
                        <span class="bsd-gauge-center">{{ r.c2c }} day(s)</span>
                        <span>{{ gaugeMax(Math.abs(r.c2c)) }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Inline Balance Sheet Breakdown ─────────────────────── -->
            <div class="bsd-card bsd-bk-card">
                <div class="bsd-bk-header-row">
                    <div class="bsd-card-title" style="margin-bottom:0">Balance Sheet — Detailed Breakdown</div>
                    <div class="bsd-bk-badge" :class="isBalanced ? 'bsd-bk-badge-ok' : 'bsd-bk-badge-err'">
                        <template v-if="isBalanced">✓ Balanced (A = L + E)</template>
                        <template v-else>⚠ Not Balanced</template>
                    </div>
                </div>
                <div class="bsd-equation-row">
                    <span class="bsd-eq-chip bsd-eq-assets">Assets &nbsp;<strong>${{ fmtNum(snap.total_assets) }}</strong></span>
                    <span class="bsd-eq-op">=</span>
                    <span class="bsd-eq-chip bsd-eq-liab">Liabilities &nbsp;<strong>${{ fmtNum(snap.total_liabilities) }}</strong></span>
                    <span class="bsd-eq-op">+</span>
                    <span class="bsd-eq-chip bsd-eq-equity">Equity &nbsp;<strong>${{ fmtNum(snap.total_equity) }}</strong></span>
                </div>

                <div class="bsd-bk-grid">
                    <!-- ── Assets column ─── -->
                    <div class="bsd-bk-col">
                        <div class="bsd-bk-col-hdr bsd-col-asset">
                            <span>Assets</span>
                            <span>${{ fmtNum(snap.total_assets) }}</span>
                        </div>

                        <div class="bsd-bk-group-label">Current Assets</div>
                        <div v-for="row in currentAssets" :key="row.id" class="bsd-bk-row">
                            <span class="bsd-bk-code">{{ row.account_code }}</span>
                            <span class="bsd-bk-name">{{ row.account_name }}</span>
                            <span class="bsd-bk-amt bsd-c-blue">${{ fmtNum(row.balance) }}</span>
                        </div>
                        <div v-if="!currentAssets.length" class="bsd-bk-empty">—</div>
                        <div class="bsd-bk-subtotal">
                            <span>Total Current Assets</span>
                            <span class="bsd-c-blue">${{ fmtNum(totalCurrentAssets) }}</span>
                        </div>

                        <div class="bsd-bk-group-label">Non-Current Assets</div>
                        <div v-for="row in nonCurrentAssets" :key="row.id" class="bsd-bk-row">
                            <span class="bsd-bk-code">{{ row.account_code }}</span>
                            <span class="bsd-bk-name">{{ row.account_name }}</span>
                            <span class="bsd-bk-amt bsd-c-blue">${{ fmtNum(row.balance) }}</span>
                        </div>
                        <div v-if="!nonCurrentAssets.length" class="bsd-bk-empty">—</div>
                        <div class="bsd-bk-subtotal">
                            <span>Total Non-Current Assets</span>
                            <span class="bsd-c-blue">${{ fmtNum(totalNonCurrentAssets) }}</span>
                        </div>

                        <div class="bsd-bk-total bsd-total-assets">
                            <span>Total Assets</span>
                            <span>${{ fmtNum(snap.total_assets) }}</span>
                        </div>
                    </div>

                    <!-- ── Liabilities + Equity column ─── -->
                    <div class="bsd-bk-col">
                        <!-- Liabilities -->
                        <div class="bsd-bk-col-hdr bsd-col-liab">
                            <span>Liabilities</span>
                            <span>${{ fmtNum(snap.total_liabilities) }}</span>
                        </div>

                        <div class="bsd-bk-group-label">Current Liabilities</div>
                        <div v-for="row in currentLiabilities" :key="row.id" class="bsd-bk-row">
                            <span class="bsd-bk-code">{{ row.account_code }}</span>
                            <span class="bsd-bk-name">{{ row.account_name }}</span>
                            <span class="bsd-bk-amt bsd-c-red">${{ fmtNum(Math.abs(row.balance)) }}</span>
                        </div>
                        <div v-if="!currentLiabilities.length" class="bsd-bk-empty">—</div>
                        <div class="bsd-bk-subtotal">
                            <span>Total Current Liabilities</span>
                            <span class="bsd-c-red">${{ fmtNum(totalCurrentLiabilities) }}</span>
                        </div>

                        <div class="bsd-bk-group-label">Long-Term Liabilities</div>
                        <div v-for="row in longTermLiabilities" :key="row.id" class="bsd-bk-row">
                            <span class="bsd-bk-code">{{ row.account_code }}</span>
                            <span class="bsd-bk-name">{{ row.account_name }}</span>
                            <span class="bsd-bk-amt bsd-c-red">${{ fmtNum(Math.abs(row.balance)) }}</span>
                        </div>
                        <div v-if="!longTermLiabilities.length" class="bsd-bk-empty">—</div>
                        <div class="bsd-bk-subtotal">
                            <span>Total Long-Term Liabilities</span>
                            <span class="bsd-c-red">${{ fmtNum(totalLongTermLiabilities) }}</span>
                        </div>
                        <div class="bsd-bk-total bsd-total-liab">
                            <span>Total Liabilities</span>
                            <span>${{ fmtNum(snap.total_liabilities) }}</span>
                        </div>

                        <!-- Equity -->
                        <div class="bsd-bk-col-hdr bsd-col-equity" style="margin-top:16px">
                            <span>Equity</span>
                            <span>${{ fmtNum(snap.total_equity) }}</span>
                        </div>
                        <div v-for="row in equityBreakRows" :key="row.id" class="bsd-bk-row">
                            <span class="bsd-bk-code">{{ row.account_code }}</span>
                            <span class="bsd-bk-name">{{ row.account_name }}</span>
                            <span class="bsd-bk-amt bsd-c-purple">${{ fmtNum(Math.abs(row.balance)) }}</span>
                        </div>
                        <div v-if="!equityBreakRows.length" class="bsd-bk-empty">—</div>
                        <div class="bsd-bk-total bsd-total-equity">
                            <span>Total Equity</span>
                            <span>${{ fmtNum(snap.total_equity) }}</span>
                        </div>

                        <!-- L + E check -->
                        <div class="bsd-bk-le-check" :class="isBalanced ? 'bsd-bk-le-ok' : 'bsd-bk-le-err'">
                            <span>Total Liabilities + Equity</span>
                            <span>${{ fmtNum(+snap.total_liabilities + +snap.total_equity) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Yearly Financial Table ──────────────────────────────── -->
            <div class="bsd-card bsd-yearly-card">
                <div class="bsd-card-title">Overall Financial Statement <InfoCircleOutlined class="bsd-info" /></div>
                <div class="bsd-yearly-table-wrap">
                    <table class="bsd-yearly-tbl">
                        <thead>
                            <tr>
                                <th class="bsd-yt-year">Year <span class="bsd-sort-icon">↑ ▼</span></th>
                                <th>Cash</th>
                                <th>Accounts Receiv...</th>
                                <th>Inventory</th>
                                <th>Current Assets</th>
                                <th>Property &amp; Equip...</th>
                                <th>Accounts Payable</th>
                                <th>Debt</th>
                                <th>Current Liabilities</th>
                                <th>Equity Capital</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in yearlyTable" :key="row.year">
                                <td class="bsd-yt-year-val">▶ {{ row.year }}</td>
                                <td>${{ fmtNum(row.cash) }}</td>
                                <td>${{ fmtNum(row.accounts_receivable) }}</td>
                                <td>${{ fmtNum(row.inventory) }}</td>
                                <td>${{ fmtNum(row.current_assets) }}</td>
                                <td>${{ fmtNum(row.property_equipment) }}</td>
                                <td>${{ fmtNum(row.accounts_payable) }}</td>
                                <td>${{ fmtNum(row.debt) }}</td>
                                <td>${{ fmtNum(row.current_liabilities) }}</td>
                                <td>${{ fmtNum(row.equity_capital) }}</td>
                            </tr>
                            <tr v-if="!yearlyTable.length">
                                <td colspan="10" class="bsd-empty">No data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="bsd-loading">
            <a-spin size="large" />
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import dayjs from 'dayjs';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import { BarChart } from 'vue-chart-3';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
import {
    BarChartOutlined, SearchOutlined, PrinterOutlined, InfoCircleOutlined, FileTextOutlined,
} from '@ant-design/icons-vue';

/* ── SVG Gauge component (render-fn, no runtime compiler needed) ──── */
import { h } from 'vue';
const Gauge = defineComponent({
    name: 'Gauge',
    props: { value: Number, max: Number, color: String },
    setup(props) {
        const R = 68, CX = 100, CY = 90;
        const circ = Math.PI * R;
        const trackD = `M ${CX - R} ${CY} A ${R} ${R} 0 0 1 ${CX + R} ${CY}`;
        return () => {
            const pct = Math.max(0, Math.min(1, (props.value || 0) / (props.max || 1)));
            const offset = circ * (1 - pct);
            const rot = (pct * 180) - 90;
            return h('svg', { viewBox: '0 0 200 100', style: 'width:100%;max-width:240px;display:block;margin:0 auto' }, [
                h('path', { d: trackD, fill: 'none', stroke: '#E3E6EF', 'stroke-width': 16, 'stroke-linecap': 'round' }),
                h('path', { d: trackD, fill: 'none', stroke: props.color, 'stroke-width': 16, 'stroke-linecap': 'round',
                    'stroke-dasharray': circ, 'stroke-dashoffset': offset,
                    style: 'transition:stroke-dashoffset .5s ease' }),
                h('g', { transform: `rotate(${rot} ${CX} ${CY})` }, [
                    h('line', { x1: CX, y1: CY, x2: CX, y2: CY - R + 14, stroke: '#272B41', 'stroke-width': 2, 'stroke-linecap': 'round' }),
                    h('circle', { cx: CX, cy: CY, r: 5, fill: '#272B41' }),
                ]),
            ]);
        };
    },
});

export default defineComponent({
    components: {
        AdminPageHeader, BarChart, Gauge,
        BarChartOutlined, SearchOutlined, PrinterOutlined, InfoCircleOutlined, FileTextOutlined,
    },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading    = ref(true);
        const data       = ref(null);

        const now = dayjs();
        const currentYear = now.year();
        const yearOptions = Array.from({ length: 8 }, (_, i) => currentYear - 7 + i).reverse();

        const filters = ref({
            year:  currentYear,
            as_of: now,
        });

        const snap        = computed(() => data.value?.snapshot  ?? {});
        const r           = computed(() => data.value?.ratios     ?? {});
        const charts      = computed(() => data.value?.charts     ?? {});
        const yearlyTable = computed(() => data.value?.yearly_table ?? []);

        const assetRows = computed(() => (snap.value.data || []).filter(x => x.account_type === 'Asset'));
        const liabRows  = computed(() => (snap.value.data || []).filter(x => x.account_type === 'Liability'));

        /* ── Breakdown helpers ───────────────────────────────────────── */
        const isCurAsset = (n) => /cash|receivable|inventor|stock|prepaid|current|advance|deposit/i.test(n);
        const isCurLiab  = (n) => /payable|accrued|current|tax|advance rec|deferred rev|short.term/i.test(n);

        const currentAssets       = computed(() => assetRows.value.filter(r => isCurAsset(r.account_name)));
        const nonCurrentAssets    = computed(() => assetRows.value.filter(r => !isCurAsset(r.account_name)));
        const currentLiabilities  = computed(() => liabRows.value.filter(r => isCurLiab(r.account_name)));
        const longTermLiabilities = computed(() => liabRows.value.filter(r => !isCurLiab(r.account_name)));
        const equityBreakRows     = computed(() => (snap.value.data || []).filter(x => x.account_type === 'Equity'));

        const totalCurrentAssets      = computed(() => currentAssets.value.reduce((s, x) => s + Number(x.balance), 0));
        const totalNonCurrentAssets   = computed(() => nonCurrentAssets.value.reduce((s, x) => s + Number(x.balance), 0));
        const totalCurrentLiabilities = computed(() => currentLiabilities.value.reduce((s, x) => s + Math.abs(Number(x.balance)), 0));
        const totalLongTermLiabilities= computed(() => longTermLiabilities.value.reduce((s, x) => s + Math.abs(Number(x.balance)), 0));
        const isBalanced = computed(() => Math.abs(Number(snap.value.total_assets || 0) - (Number(snap.value.total_liabilities || 0) + Number(snap.value.total_equity || 0))) < 1);

        const barOpts = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } } },
            scales: {
                x: { ticks: { font: { size: 10 } }, grid: { display: false } },
                y: { ticks: { font: { size: 10 } }, grid: { color: '#F1F2F6' } },
            },
        };
        const cashBarOpts = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 10 } }, grid: { display: false } },
                y: {
                    ticks: {
                        font: { size: 10 },
                        callback: v => v >= 1000 ? '$' + (v / 1000).toFixed(0) + 'K' : '$' + v,
                    },
                    grid: { color: '#F1F2F6' },
                },
            },
        };

        const arApChartData = computed(() => {
            const c = charts.value;
            if (!c.years?.length) return null;
            return {
                labels: c.years,
                datasets: [
                    { label: 'AR Turnover', data: c.ar_turnover, backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 3 },
                    { label: 'AP Turnover', data: c.ap_turnover, backgroundColor: 'rgba(37,99,235,0.4)',  borderRadius: 3 },
                ],
            };
        });

        const cashChartData = computed(() => {
            const c = charts.value;
            if (!c.years?.length) return null;
            return {
                labels: c.years,
                datasets: [{
                    label: 'Cash',
                    data: c.cash_by_year,
                    backgroundColor: 'rgba(124,58,237,0.6)',
                    borderRadius: 3,
                }],
            };
        });

        const gaugeMax = (val) => {
            const v = Math.abs(val) || 0;
            if (v === 0) return 100;
            return Math.ceil(v * 1.3 / 10) * 10;
        };

        const fmtNum = (v) => {
            const n = Number(v || 0);
            if (Math.abs(n) >= 1000000) return (n / 1000000).toFixed(2) + 'M';
            if (Math.abs(n) >= 1000)    return n.toLocaleString('en-PK', { minimumFractionDigits: 0 });
            return n.toLocaleString('en-PK', { minimumFractionDigits: 0 });
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
                data.value = res.data.data;
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
            assetRows, liabRows,
            currentAssets, nonCurrentAssets, currentLiabilities, longTermLiabilities,
            equityBreakRows, totalCurrentAssets, totalNonCurrentAssets,
            totalCurrentLiabilities, totalLongTermLiabilities, isBalanced,
            arApChartData, cashChartData,
            barOpts, cashBarOpts,
            gaugeMax, fmtNum, load, print,
        };
    },
});
</script>

<style scoped>
/* ── Root ─────────────────────────────────────────────────────── */
.bsd-root { background: #F4F5F7; min-height: 100%; font-family: 'Inter', sans-serif; padding-bottom: 48px; }

/* ── Filter Bar ───────────────────────────────────────────────── */
.bsd-filter-bar { background: #fff; border-bottom: 1px solid #E3E6EF; padding: 12px 24px; }
.bsd-filter-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.bsd-title { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 700; color: #272B41; }
.bsd-title-icon { width: 28px; height: 28px; background: #e6f4ff; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #1677ff; font-size: 14px; }
.bsd-filter-right { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
.bsd-filter-group { display: flex; flex-direction: column; gap: 2px; }
.bsd-filter-label { font-size: 10px; font-weight: 700; color: #5A5F7D; text-transform: uppercase; letter-spacing: .5px; }

/* ── Body ─────────────────────────────────────────────────────── */
.bsd-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }
.bsd-loading { display: flex; align-items: center; justify-content: center; padding: 80px; }

/* ── Top KPI row ──────────────────────────────────────────────── */
.bsd-kpi-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
.bsd-kpi-card {
    background: #fff; border: 1px solid #E3E6EF; border-radius: 10px;
    padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.bsd-kpi-label { font-size: 11px; color: #5A5F7D; margin-bottom: 6px; line-height: 1.4; }
.bsd-kpi-value { font-size: 20px; font-weight: 700; color: #272B41; }
.bsd-info { color: #ADB4D2; font-size: 11px; margin-left: 2px; cursor: help; }

/* ── Main 3-col row ───────────────────────────────────────────── */
.bsd-main-row { display: grid; grid-template-columns: 1fr 1.1fr 0.65fr; gap: 14px; align-items: start; }
.bsd-card {
    background: #fff; border: 1px solid #E3E6EF; border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04); padding: 16px;
}
.bsd-card-title { font-size: 13px; font-weight: 700; color: #272B41; margin-bottom: 12px; }

/* BS table */
.bsd-tbl { width: 100%; border-collapse: collapse; font-size: 12px; }
.bsd-tbl th { background: #F8F9FB; color: #5A5F7D; font-size: 11px; font-weight: 700; padding: 8px 10px; border-bottom: 2px solid #E3E6EF; text-align: left; }
.bsd-tbl th:last-child, .bsd-tbl td.bsd-right { text-align: right; }
.bsd-tbl td { padding: 6px 10px; color: #272B41; border-bottom: 1px solid #F1F2F6; }
.bsd-section-hdr td { background: #F8F9FB; font-size: 12px; color: #5A5F7D; padding: 7px 10px; border-bottom: 1px solid #E3E6EF; }
.bsd-total-row td { font-weight: 700; border-top: 2px solid #E3E6EF; border-bottom: 2px solid #E3E6EF; padding: 8px 10px; }
.bsd-blue td { background: #e6f4ff; color: #1677ff; }
.bsd-red  td { background: #fff1f0; color: #FF4D4F; }
.bsd-purple td { background: #f5f0ff; color: #722ed1; }
.bsd-minus { color: #FF4D4F; }

/* Center charts */
.bsd-center-col { display: flex; flex-direction: column; gap: 12px; }
.bsd-chart-card { padding: 14px 16px; }

/* Right ratio tiles */
.bsd-right-col { display: flex; flex-direction: column; gap: 10px; }
.bsd-ratio-tile {
    background: #fff; border: 1px solid #E3E6EF; border-radius: 10px;
    padding: 12px 16px; box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.bsd-ratio-label { font-size: 11px; color: #5A5F7D; margin-bottom: 4px; line-height: 1.4; }
.bsd-ratio-value { font-size: 18px; font-weight: 700; color: #272B41; }
.bsd-green { color: #0CAB7C !important; }

/* ── Gauge row ────────────────────────────────────────────────── */
.bsd-gauge-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.bsd-gauge-card {
    background: #fff; border: 1px solid #E3E6EF; border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04); padding: 16px 12px 10px;
}
.bsd-gauge-title { font-size: 12px; font-weight: 600; color: #272B41; margin-bottom: 8px; }
.bsd-gauge-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 4px; font-size: 11px; color: #ADB4D2; }
.bsd-gauge-center { color: #272B41; font-weight: 700; font-size: 13px; }

/* ── Yearly table ─────────────────────────────────────────────── */
.bsd-yearly-card { padding: 16px; }
.bsd-yearly-table-wrap { overflow-x: auto; border: 1px solid #E3E6EF; border-radius: 8px; }
.bsd-yearly-tbl { width: 100%; border-collapse: collapse; font-size: 12px; }
.bsd-yearly-tbl th {
    background: #F8F9FB; color: #5A5F7D; font-size: 11px; font-weight: 700;
    padding: 10px 12px; border-bottom: 2px solid #E3E6EF; white-space: nowrap;
    text-align: right;
}
.bsd-yearly-tbl th.bsd-yt-year { text-align: left; }
.bsd-yearly-tbl td { padding: 10px 12px; color: #272B41; border-bottom: 1px solid #F1F2F6; text-align: right; }
.bsd-yt-year-val { text-align: left !important; font-weight: 600; color: #5A5F7D; cursor: pointer; }
.bsd-sort-icon { color: #ADB4D2; margin-left: 4px; }
.bsd-yearly-tbl tbody tr:hover { background: #F8F9FB; }
.bsd-empty { text-align: center; color: #ADB4D2; padding: 24px; }

/* ── Breakdown section ────────────────────────────────────────── */
.bsd-bk-card { padding: 18px 20px; }
.bsd-bk-header-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.bsd-bk-badge { font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; }
.bsd-bk-badge-ok  { background: #d6fff4; color: #0CAB7C; }
.bsd-bk-badge-err { background: #fff1f0; color: #FF4D4F; }

.bsd-equation-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; padding: 12px; background: #F8F9FB; border-radius: 8px; border: 1px solid #E3E6EF; }
.bsd-eq-chip { font-size: 12px; padding: 5px 12px; border-radius: 6px; font-weight: 600; }
.bsd-eq-assets { background: #e6f4ff; color: #1677ff; }
.bsd-eq-liab   { background: #fff1f0; color: #FF4D4F; }
.bsd-eq-equity { background: #f5f0ff; color: #722ed1; }
.bsd-eq-op { font-size: 16px; font-weight: 700; color: #ADB4D2; }

.bsd-bk-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.bsd-bk-col { display: flex; flex-direction: column; }
.bsd-bk-col-hdr { display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 700; padding: 10px 12px; border-radius: 8px; margin-bottom: 8px; }
.bsd-col-asset  { background: #e6f4ff; color: #1677ff; }
.bsd-col-liab   { background: #fff1f0; color: #FF4D4F; }
.bsd-col-equity { background: #f5f0ff; color: #722ed1; }

.bsd-bk-group-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #ADB4D2; padding: 8px 4px 4px; border-bottom: 1px dashed #E3E6EF; margin-bottom: 4px; }
.bsd-bk-row { display: flex; align-items: center; gap: 8px; padding: 5px 4px; border-bottom: 1px solid #F1F2F6; font-size: 12px; color: #272B41; }
.bsd-bk-code { font-size: 10px; background: #F4F5F7; padding: 1px 5px; border-radius: 3px; color: #ADB4D2; flex-shrink: 0; }
.bsd-bk-name { flex: 1; }
.bsd-bk-amt { font-weight: 600; flex-shrink: 0; font-variant-numeric: tabular-nums; }
.bsd-bk-empty { font-size: 12px; color: #ADB4D2; padding: 6px 4px; font-style: italic; }
.bsd-bk-subtotal { display: flex; justify-content: space-between; padding: 7px 8px; background: #F8F9FB; border-radius: 4px; font-size: 12px; font-weight: 600; color: #5A5F7D; margin: 6px 0; }
.bsd-bk-total { display: flex; justify-content: space-between; padding: 9px 10px; border-radius: 6px; font-size: 13px; font-weight: 700; margin-top: 8px; }
.bsd-total-assets { background: #e6f4ff; color: #1677ff; }
.bsd-total-liab   { background: #fff1f0; color: #FF4D4F; }
.bsd-total-equity { background: #f5f0ff; color: #722ed1; }
.bsd-c-blue   { color: #1677ff; }
.bsd-c-red    { color: #FF4D4F; }
.bsd-c-purple { color: #722ed1; }

.bsd-bk-le-check { display: flex; justify-content: space-between; padding: 10px 12px; border-radius: 8px; font-size: 13px; font-weight: 700; margin-top: 12px; border: 1.5px solid; }
.bsd-bk-le-ok  { background: #d6fff4; border-color: #87e8ca; color: #0CAB7C; }
.bsd-bk-le-err { background: #fff1f0; border-color: #ffccc7; color: #FF4D4F; }

/* ── Responsive ───────────────────────────────────────────────── */
@media (max-width: 1100px) {
    .bsd-kpi-row { grid-template-columns: repeat(3, 1fr); }
    .bsd-main-row { grid-template-columns: 1fr 1fr; }
    .bsd-right-col { flex-direction: row; flex-wrap: wrap; }
    .bsd-ratio-tile { min-width: 140px; flex: 1; }
    .bsd-gauge-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 700px) {
    .bsd-kpi-row { grid-template-columns: 1fr 1fr; }
    .bsd-main-row { grid-template-columns: 1fr; }
    .bsd-gauge-row { grid-template-columns: 1fr 1fr; }
    .bsd-bk-grid { grid-template-columns: 1fr; }
}
</style>
