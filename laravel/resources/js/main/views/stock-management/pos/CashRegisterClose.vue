<template>
    <a-modal
        :open="visible"
        :title="null"
        :footer="null"
        width="680px"
        centered
        @cancel="$emit('cancelled')"
    >
        <!-- ══ STORE HEADER ══════════════════════════════════════════ -->
        <div class="crc-header">
            <div class="crc-store-name">{{ appSetting?.name || 'MA Electronics' }}</div>
            <div class="crc-sub">Point of Sale — Register Closing Report</div>
        </div>

        <a-spin :spinning="reportLoading">
            <div v-if="report">

                <!-- ══ SESSION META ROW ════════════════════════════════ -->
                <div class="crc-meta-row">
                    <div class="crc-meta-item">
                        <span class="crc-meta-icon">👤</span>
                        <div>
                            <div class="crc-meta-label">Cashier</div>
                            <div class="crc-meta-val">{{ user?.name || '—' }}</div>
                        </div>
                    </div>
                    <div class="crc-meta-sep"></div>
                    <div class="crc-meta-item">
                        <span class="crc-meta-icon">🏬</span>
                        <div>
                            <div class="crc-meta-label">Branch</div>
                            <div class="crc-meta-val">{{ warehouse?.name || '—' }}</div>
                            <div v-if="warehouse?.address" style="font-size:9px; color:#bbb;">{{ warehouse.address }}</div>
                        </div>
                    </div>
                    <div class="crc-meta-sep"></div>
                    <div class="crc-meta-item">
                        <span class="crc-meta-icon">🕐</span>
                        <div>
                            <div class="crc-meta-label">Shift Opened</div>
                            <div class="crc-meta-val">{{ fmtDt(report.register?.opened_at) }}</div>
                        </div>
                    </div>
                    <div class="crc-meta-sep"></div>
                    <div class="crc-meta-item">
                        <span class="crc-meta-icon">📅</span>
                        <div>
                            <div class="crc-meta-label">Closing Date</div>
                            <div class="crc-meta-val">{{ todayDate }}</div>
                        </div>
                    </div>
                </div>

                <!-- ══ TOP 3 SUMMARY CARDS ════════════════════════════ -->
                <a-row :gutter="[8,8]" style="margin-bottom:14px;">
                    <a-col :span="8">
                        <div class="crc-card crc-card-blue">
                            <div class="crc-card-icon">💼</div>
                            <div class="crc-card-label">Opening Balance</div>
                            <div class="crc-card-val">{{ fmt(report.register?.opening_balance) }}</div>
                        </div>
                    </a-col>
                    <a-col :span="8">
                        <div class="crc-card crc-card-green">
                            <div class="crc-card-icon">📈</div>
                            <div class="crc-card-label">Total Sales</div>
                            <div class="crc-card-val">{{ fmt(report.register?.total_sales) }}</div>
                        </div>
                    </a-col>
                    <a-col :span="8">
                        <div class="crc-card crc-card-red">
                            <div class="crc-card-icon">💸</div>
                            <div class="crc-card-label">Total Expenses</div>
                            <div class="crc-card-val">{{ fmt(report.register?.total_expense) }}</div>
                        </div>
                    </a-col>
                </a-row>

                <!-- ══ PAYMENT BREAKDOWN ══════════════════════════════ -->
                <div class="crc-section-head">
                    <span>💳 Cash Received by Payment Method</span>
                    <a-tag v-if="colTotals.grand > 0" color="green">
                        Total {{ fmt(colTotals.grand) }}
                    </a-tag>
                </div>

                <table class="crc-table">
                    <thead>
                        <tr>
                            <th class="crc-th-name">Method</th>
                            <th v-if="colVisible.normal"  class="crc-th-type">Normal Sales</th>
                            <th v-if="colVisible.advance" class="crc-th-type crc-advance">Advance</th>
                            <th v-if="colVisible.credit"  class="crc-th-type crc-credit">Credit Paid</th>
                            <th class="crc-th-total">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, i) in paymentRows"
                            :key="i"
                            class="crc-tbody-row"
                        >
                            <td class="crc-td-name">
                                <span class="crc-dot" :style="{ background: dotColor(row.name) }"></span>
                                {{ row.name }}
                            </td>
                            <td v-if="colVisible.normal"  class="crc-td-amt">{{ row.normal  > 0 ? fmt(row.normal)  : '—' }}</td>
                            <td v-if="colVisible.advance" class="crc-td-amt crc-advance">{{ row.advance > 0 ? fmt(row.advance) : '—' }}</td>
                            <td v-if="colVisible.credit"  class="crc-td-amt crc-credit">{{ row.credit  > 0 ? fmt(row.credit)  : '—' }}</td>
                            <td class="crc-td-row-total">{{ fmt(row.total) }}</td>
                        </tr>
                        <tr v-if="paymentRows.length === 0">
                            <td :colspan="2 + (colVisible.normal?1:0) + (colVisible.advance?1:0) + (colVisible.credit?1:0)" class="crc-empty">
                                No payments recorded this session
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="crc-tfoot-row">
                            <td class="crc-td-name" style="font-weight:700;">TOTAL</td>
                            <td v-if="colVisible.normal"  class="crc-td-amt crc-foot-cell">{{ fmt(colTotals.normal) }}</td>
                            <td v-if="colVisible.advance" class="crc-td-amt crc-foot-cell crc-advance">{{ fmt(colTotals.advance) }}</td>
                            <td v-if="colVisible.credit"  class="crc-td-amt crc-foot-cell crc-credit">{{ fmt(colTotals.credit) }}</td>
                            <td class="crc-td-grand">{{ fmt(colTotals.grand) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- ══ EXPENSE BREAKDOWN (collapsible) ═══════════════ -->
                <a-collapse
                    v-if="expenseBreakdown.length > 0"
                    ghost
                    style="margin-top:10px;"
                >
                    <a-collapse-panel key="1">
                        <template #header>
                            <span class="crc-section-head" style="margin-bottom:0; display:inline-flex; gap:8px; align-items:center;">
                                💸 Expense Breakdown
                                <a-tag color="red">{{ fmt(report.register?.total_expense) }}</a-tag>
                            </span>
                        </template>
                        <table class="crc-table">
                            <thead><tr>
                                <th class="crc-th-name">Category</th>
                                <th class="crc-th-total">Amount</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="(e, i) in expenseBreakdown" :key="i" class="crc-tbody-row">
                                    <td class="crc-td-name">{{ e.name }}</td>
                                    <td class="crc-td-row-total" style="color:#cf1322;">{{ fmt(e.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </a-collapse-panel>
                </a-collapse>

                <!-- ══ EXPECTED CLOSING BALANCE ═══════════════════════ -->
                <div class="crc-expected-box">
                    <div>
                        <div class="crc-expected-label">Expected Closing Balance</div>
                        <div class="crc-expected-formula">Opening ({{ fmt(report.register?.opening_balance) }}) + Received ({{ fmt(colTotals.grand) }}) − Expenses ({{ fmt(report.register?.total_expense) }})</div>
                    </div>
                    <div class="crc-expected-amount">{{ fmt(report.expected_closing) }}</div>
                </div>

                <a-divider style="margin:14px 0 12px;">Cash Count &amp; Verify</a-divider>

                <!-- ══ ACTUAL CASH INPUT ════════════════════════════════ -->
                <div class="crc-field-label">💵 Actual Cash Counted in Drawer</div>
                <a-input-number
                    v-model:value="actualCash"
                    :min="0"
                    :precision="0"
                    :step="500"
                    size="large"
                    style="width:100%; font-size:22px; font-weight:700;"
                    placeholder="Count and enter cash in drawer"
                    @pressEnter="handleClose"
                >
                    <template #prefix>
                        <span style="color:#999; font-size:14px;">Rs.</span>
                    </template>
                </a-input-number>

                <!-- ══ DIFFERENCE INDICATOR ════════════════════════════ -->
                <div
                    v-if="actualCash !== null && actualCash !== undefined && actualCash !== ''"
                    class="crc-diff"
                    :class="difference === 0 ? 'crc-diff-ok' : difference > 0 ? 'crc-diff-over' : 'crc-diff-short'"
                    style="margin-top:10px;"
                >
                    <div class="crc-diff-emoji">
                        {{ difference === 0 ? '✅' : difference > 0 ? '📈' : '📉' }}
                    </div>
                    <div class="crc-diff-center">
                        <div class="crc-diff-status">
                            {{ difference === 0 ? 'Perfectly Balanced' : difference > 0 ? 'Cash Over' : 'Cash Short' }}
                        </div>
                        <div class="crc-diff-detail">
                            Counted <strong>{{ fmt(actualCash) }}</strong> · Expected <strong>{{ fmt(report.expected_closing) }}</strong>
                        </div>
                    </div>
                    <div class="crc-diff-amount">
                        {{ difference > 0 ? '+' : '' }}{{ fmt(difference) }}
                    </div>
                </div>

                <!-- ══ NOTES ══════════════════════════════════════════ -->
                <div style="margin-top:12px;">
                    <div class="crc-field-label">📝 Closing Notes <span style="font-weight:400; color:#bbb;">(optional)</span></div>
                    <a-input v-model:value="closeNotes" placeholder="End-of-day remarks" allow-clear />
                </div>

                <a-alert v-if="errorMsg" type="error" :message="errorMsg" show-icon style="margin:12px 0;" />

                <!-- ══ ACTION BUTTONS ═══════════════════════════════════ -->
                <a-row :gutter="10" style="margin-top:16px;">
                    <a-col :span="10">
                        <a-button block size="large" style="height:48px;" @click="$emit('cancelled')">
                            Cancel
                        </a-button>
                    </a-col>
                    <a-col :span="14">
                        <a-button
                            type="primary" danger block size="large"
                            style="height:48px; font-size:15px; font-weight:700;"
                            :loading="closeLoading"
                            :disabled="actualCash === null || actualCash === undefined || actualCash === ''"
                            @click="handleClose"
                        >
                            🔒 &nbsp; Close Register
                        </a-button>
                    </a-col>
                </a-row>

            </div>

            <a-empty v-else-if="!reportLoading" description="No open cash register found." style="padding:40px 0;" />
        </a-spin>
    </a-modal>
</template>

<script>
import { ref, computed, watch } from "vue";
import { useStore } from "vuex";

const DOT_COLORS = {
    'cash':           '#52c41a',
    'easy paisa':     '#13c2c2',
    'easypaisa':      '#13c2c2',
    'jazzcash':       '#fa8c16',
    'jazz cash':      '#fa8c16',
    'card':           '#1677ff',
    'bank':           '#722ed1',
    'bank transfer':  '#722ed1',
    'cheque':         '#9254de',
    'online':         '#36cfc9',
};

export default {
    props: { visible: { type: Boolean, default: false } },
    emits: ['cancelled', 'closed'],
    setup(props, { emit }) {
        const store = useStore();

        const user        = computed(() => store.state.auth.user);
        const warehouse   = computed(() => store.state.auth.warehouse);
        const appSetting  = computed(() => store.state.auth.appSetting);

        const report           = ref(null);
        const paymentRows      = ref([]);
        const colTotals        = ref({ normal: 0, credit: 0, advance: 0, grand: 0 });
        const expenseBreakdown = ref([]);
        const reportLoading    = ref(false);
        const closeLoading     = ref(false);
        const actualCash       = ref(null);
        const closeNotes       = ref('');
        const errorMsg         = ref('');

        const todayDate = computed(() =>
            new Date().toLocaleDateString('en-PK', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
        );

        const colVisible = computed(() => ({
            normal:  (colTotals.value.normal  || 0) > 0,
            credit:  (colTotals.value.credit  || 0) > 0,
            advance: (colTotals.value.advance || 0) > 0,
        }));

        const difference = computed(() => {
            if (!report.value) return 0;
            return (parseFloat(actualCash.value) || 0) - (report.value.expected_closing || 0);
        });

        const fmt = (v) => {
            if (v === null || v === undefined) return 'Rs. 0';
            return 'Rs. ' + parseFloat(v).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        };

        const fmtDt = (dt) => {
            if (!dt) return '—';
            return new Date(dt).toLocaleString('en-PK', {
                day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: true,
            });
        };

        const dotColor = (name) =>
            DOT_COLORS[(name || '').toLowerCase()] || '#8c8c8c';

        const loadReport = () => {
            if (!props.visible) return;
            reportLoading.value    = true;
            report.value           = null;
            paymentRows.value      = [];
            colTotals.value        = { normal: 0, credit: 0, advance: 0, grand: 0 };
            expenseBreakdown.value = [];
            actualCash.value       = null;
            closeNotes.value       = '';
            errorMsg.value         = '';

            axiosAdmin.get('cash-register/report').then(res => {
                report.value = res.data;
                const pb = res.data.payment_breakdown || {};
                paymentRows.value      = pb.rows          || [];
                colTotals.value        = pb.column_totals || { normal: 0, credit: 0, advance: 0, grand: 0 };
                expenseBreakdown.value = res.data.expense_breakdown || [];
            }).finally(() => {
                reportLoading.value = false;
            });
        };

        const handleClose = () => {
            errorMsg.value     = '';
            closeLoading.value = true;
            axiosAdmin.post('cash-register/close', {
                actual_cash: actualCash.value,
                notes: closeNotes.value || '',
            }).then(res => {
                emit('closed', res.data);
            }).catch(() => {
                errorMsg.value = 'Failed to close register. Please try again.';
            }).finally(() => {
                closeLoading.value = false;
            });
        };

        watch(() => props.visible, (v) => { if (v) loadReport(); });

        return {
            user, warehouse, appSetting,
            report, paymentRows, colTotals, expenseBreakdown,
            reportLoading, closeLoading,
            actualCash, closeNotes, errorMsg,
            colVisible, difference, todayDate,
            fmt, fmtDt, dotColor, handleClose,
        };
    },
};
</script>

<style scoped>
/* ── Header ─────────────────────────────────────────────── */
.crc-header {
    text-align: center;
    padding: 6px 0 12px;
    border-bottom: 2px solid #cf1322;
    margin-bottom: 14px;
}
.crc-store-name {
    font-size: 18px;
    font-weight: 800;
    color: #cf1322;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.crc-sub {
    font-size: 10px;
    color: #888;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-top: 2px;
}

/* ── Meta Row ───────────────────────────────────────────── */
.crc-meta-row {
    display: flex;
    background: #fafafa;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 12px;
    gap: 6px;
    align-items: center;
}
.crc-meta-item {
    display: flex;
    align-items: center;
    gap: 7px;
    flex: 1;
}
.crc-meta-sep {
    width: 1px;
    height: 28px;
    background: #e8e8e8;
}
.crc-meta-icon { font-size: 18px; line-height: 1; }
.crc-meta-label { font-size: 9px; color: #bbb; text-transform: uppercase; letter-spacing: 0.8px; }
.crc-meta-val   { font-size: 12px; font-weight: 700; color: #333; }

/* ── Summary Cards ──────────────────────────────────────── */
.crc-card {
    border-radius: 8px;
    padding: 10px 12px;
    text-align: center;
    border: 1px solid transparent;
}
.crc-card-icon  { font-size: 18px; margin-bottom: 4px; }
.crc-card-label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
.crc-card-val   { font-size: 15px; font-weight: 800; margin-top: 2px; }
.crc-card-blue  { background: #e6f4ff; border-color: #91caff; } .crc-card-blue  .crc-card-val { color: #1677ff; }
.crc-card-green { background: #f6ffed; border-color: #b7eb8f; } .crc-card-green .crc-card-val { color: #389e0d; }
.crc-card-red   { background: #fff1f0; border-color: #ffa39e; } .crc-card-red   .crc-card-val { color: #cf1322; }

/* ── Section Heading ────────────────────────────────────── */
.crc-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 700;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 6px;
}

/* ── Payment Matrix Table ───────────────────────────────── */
.crc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.crc-table th, .crc-table td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; }
.crc-th-name  { text-align: left;  background: #fafafa; color: #555; font-weight: 700; }
.crc-th-type  { text-align: right; background: #fafafa; color: #555; font-weight: 600; min-width: 100px; }
.crc-th-total { text-align: right; background: #f5f5f5; color: #333; font-weight: 700; min-width: 115px; }
.crc-tbody-row:nth-child(odd) { background: #fff; }
.crc-tbody-row:nth-child(even) { background: #fafafa; }
.crc-td-name      { display: flex; align-items: center; gap: 7px; color: #333; white-space: nowrap; }
.crc-td-amt       { text-align: right; color: #555; }
.crc-td-row-total { text-align: right; font-weight: 700; color: #1677ff; }
.crc-td-grand     { text-align: right; font-weight: 800; color: #1d39c4; font-size: 14px; }
.crc-advance      { color: #722ed1 !important; }
.crc-credit       { color: #d46b08 !important; }
.crc-tfoot-row    { background: #f0f5ff !important; border-top: 2px solid #adc6ff; }
.crc-foot-cell    { font-weight: 700; color: #1d39c4 !important; }
.crc-empty        { text-align: center; color: #bbb; padding: 16px; font-style: italic; }
.crc-dot          { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; display: inline-block; }

/* ── Expected Closing ───────────────────────────────────── */
.crc-expected-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #e6f4ff;
    border: 1px solid #91caff;
    border-radius: 8px;
    padding: 12px 16px;
    margin-top: 12px;
}
.crc-expected-label   { font-size: 11px; font-weight: 700; color: #1d39c4; text-transform: uppercase; letter-spacing: 0.8px; }
.crc-expected-formula { font-size: 10px; color: #91caff; margin-top: 2px; }
.crc-expected-amount  { font-size: 24px; font-weight: 800; color: #1d39c4; }

/* ── Field Label ─────────────────────────────────────────── */
.crc-field-label {
    font-size: 11px;
    font-weight: 700;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 6px;
}

/* ── Difference Box ─────────────────────────────────────── */
.crc-diff {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 8px;
    padding: 12px 16px;
    border: 1px solid transparent;
}
.crc-diff-ok    { background: #f6ffed; border-color: #b7eb8f; }
.crc-diff-over  { background: #f6ffed; border-color: #b7eb8f; }
.crc-diff-short { background: #fff1f0; border-color: #ffa39e; }
.crc-diff-emoji  { font-size: 26px; line-height: 1; }
.crc-diff-center { flex: 1; }
.crc-diff-status { font-size: 13px; font-weight: 700; }
.crc-diff-ok    .crc-diff-status, .crc-diff-over .crc-diff-status { color: #389e0d; }
.crc-diff-short .crc-diff-status { color: #cf1322; }
.crc-diff-detail { font-size: 11px; color: #888; margin-top: 2px; }
.crc-diff-amount { font-size: 22px; font-weight: 800; }
.crc-diff-ok    .crc-diff-amount, .crc-diff-over .crc-diff-amount { color: #389e0d; }
.crc-diff-short .crc-diff-amount { color: #cf1322; }
</style>
