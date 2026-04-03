<template>
    <a-modal
        :open="visible"
        :title="null"
        :footer="null"
        width="660px"
        centered
        @cancel="$emit('cancelled')"
    >
        <!-- ── Modal Header ─────────────────────────────────────────── -->
        <div style="display:flex; align-items:center; gap:10px; padding-bottom:12px; border-bottom:1px solid #f0f0f0; margin-bottom:14px;">
            <div style="font-size:28px; line-height:1;">🔒</div>
            <div>
                <div style="font-size:16px; font-weight:700; color:#1d1d1d;">Close Cash Register</div>
                <div style="font-size:12px; color:#999;">
                    {{ report?.register?.opened_at ? 'Opened: ' + formatDt(report.register.opened_at) : 'Daily Summary' }}
                </div>
            </div>
        </div>

        <a-spin :spinning="reportLoading">
            <div v-if="report">

                <!-- ── Top 3 Stat Cards ───────────────────────────── -->
                <a-row :gutter="[8,8]" style="margin-bottom:14px;">
                    <a-col :span="8">
                        <div class="cr-card cr-blue">
                            <div class="cr-card-label">Opening Balance</div>
                            <div class="cr-card-value">{{ fmt(report.register?.opening_balance) }}</div>
                        </div>
                    </a-col>
                    <a-col :span="8">
                        <div class="cr-card cr-green">
                            <div class="cr-card-label">Total Sales</div>
                            <div class="cr-card-value">{{ fmt(report.register?.total_sales) }}</div>
                        </div>
                    </a-col>
                    <a-col :span="8">
                        <div class="cr-card cr-red">
                            <div class="cr-card-label">Total Expenses</div>
                            <div class="cr-card-value">{{ fmt(report.register?.total_expense) }}</div>
                        </div>
                    </a-col>
                </a-row>

                <!-- ── Payment Method × Invoice Type Matrix ──────── -->
                <div class="cr-section-label">💳 Payment Summary — Received by Method</div>

                <table class="cr-matrix-table">
                    <thead>
                        <tr>
                            <th class="cr-th-method">Payment Method</th>
                            <th v-if="colVisible.normal"  class="cr-th-type cr-col-normal">Normal Sales</th>
                            <th v-if="colVisible.advance" class="cr-th-type cr-col-advance">Advance</th>
                            <th v-if="colVisible.credit"  class="cr-th-type cr-col-credit">Credit Paid</th>
                            <th class="cr-th-total">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, i) in paymentRows"
                            :key="i"
                            :class="i % 2 === 0 ? 'cr-row-even' : 'cr-row-odd'"
                        >
                            <td class="cr-td-method">
                                <span class="cr-method-dot" :style="{ background: methodColor(row.name) }"></span>
                                {{ row.name }}
                            </td>
                            <td v-if="colVisible.normal"  class="cr-td-amount">
                                {{ row.normal  > 0 ? fmt(row.normal)  : '—' }}
                            </td>
                            <td v-if="colVisible.advance" class="cr-td-amount cr-col-advance">
                                {{ row.advance > 0 ? fmt(row.advance) : '—' }}
                            </td>
                            <td v-if="colVisible.credit"  class="cr-td-amount cr-col-credit">
                                {{ row.credit  > 0 ? fmt(row.credit)  : '—' }}
                            </td>
                            <td class="cr-td-total">{{ fmt(row.total) }}</td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="paymentRows.length === 0">
                            <td colspan="5" style="text-align:center; color:#bbb; padding:14px; font-style:italic;">
                                No payments received yet
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="cr-footer-row">
                            <td class="cr-td-method" style="font-weight:700;">TOTAL RECEIVED</td>
                            <td v-if="colVisible.normal"  class="cr-td-amount cr-footer-cell">
                                {{ fmt(colTotals.normal) }}
                            </td>
                            <td v-if="colVisible.advance" class="cr-td-amount cr-footer-cell">
                                {{ fmt(colTotals.advance) }}
                            </td>
                            <td v-if="colVisible.credit"  class="cr-td-amount cr-footer-cell">
                                {{ fmt(colTotals.credit) }}
                            </td>
                            <td class="cr-td-total cr-footer-grand">{{ fmt(colTotals.grand) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- ── Expense Breakdown (collapsed by default) ───── -->
                <div v-if="expenseBreakdown.length > 0" style="margin-top:14px;">
                    <a-collapse ghost>
                        <a-collapse-panel key="1">
                            <template #header>
                                <span class="cr-section-label" style="margin-bottom:0;">
                                    💸 Expense Breakdown
                                    <a-tag color="red" style="margin-left:8px;">
                                        {{ fmt(report.register?.total_expense) }}
                                    </a-tag>
                                </span>
                            </template>
                            <table class="cr-matrix-table">
                                <thead>
                                    <tr>
                                        <th class="cr-th-method">Category</th>
                                        <th class="cr-th-total">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(e, i) in expenseBreakdown" :key="i" :class="i%2===0?'cr-row-even':'cr-row-odd'">
                                        <td class="cr-td-method">{{ e.name }}</td>
                                        <td class="cr-td-total" style="color:#cf1322;">{{ fmt(e.total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </a-collapse-panel>
                    </a-collapse>
                </div>

                <!-- ── Expected Closing ───────────────────────────── -->
                <div class="cr-expected-box" style="margin-top:14px;">
                    <div>
                        <div style="font-size:11px; color:#1d39c4; text-transform:uppercase; letter-spacing:1px; font-weight:600;">Expected Closing Balance</div>
                        <div style="font-size:11px; color:#91caff; margin-top:2px;">
                            Opening + Received − Expenses
                        </div>
                    </div>
                    <div style="font-size:22px; font-weight:800; color:#1d39c4;">
                        {{ fmt(report.expected_closing) }}
                    </div>
                </div>

                <a-divider style="margin:14px 0 10px;">Count &amp; Close</a-divider>

                <!-- ── Actual Cash Input ──────────────────────────── -->
                <a-form layout="vertical">
                    <a-form-item label="Actual Cash in Drawer (physically counted)">
                        <a-input-number
                            v-model:value="actualCash"
                            :min="0"
                            :precision="2"
                            :step="100"
                            size="large"
                            style="width:100%; font-size:18px;"
                            placeholder="Enter the cash you counted in the drawer"
                        />
                    </a-form-item>

                    <!-- Difference Indicator -->
                    <div
                        v-if="actualCash !== null && actualCash !== undefined && actualCash !== ''"
                        class="cr-diff-box"
                        :class="difference >= 0 ? 'cr-diff-positive' : 'cr-diff-negative'"
                    >
                        <div class="cr-diff-label">
                            {{ difference === 0 ? '✅ Balanced' : difference > 0 ? '📈 Excess Cash' : '📉 Cash Short' }}
                        </div>
                        <div class="cr-diff-amount">
                            {{ difference > 0 ? '+' : '' }}{{ fmt(difference) }}
                        </div>
                        <div class="cr-diff-sub">
                            Counted <strong>{{ fmt(actualCash) }}</strong>
                            &nbsp;·&nbsp; Expected <strong>{{ fmt(report.expected_closing) }}</strong>
                        </div>
                    </div>

                    <a-form-item label="Closing Notes (optional)" style="margin-top:10px;">
                        <a-input v-model:value="closeNotes" placeholder="Any end-of-day remarks" allow-clear />
                    </a-form-item>

                    <a-alert v-if="errorMsg" type="error" :message="errorMsg" show-icon style="margin-bottom:12px;" />

                    <a-row :gutter="10">
                        <a-col :span="12">
                            <a-button block size="large" @click="$emit('cancelled')">Cancel</a-button>
                        </a-col>
                        <a-col :span="12">
                            <a-button
                                type="primary" danger block size="large"
                                :loading="closeLoading"
                                :disabled="actualCash === null || actualCash === undefined || actualCash === ''"
                                @click="handleClose"
                            >
                                🔒 Close Register
                            </a-button>
                        </a-col>
                    </a-row>
                </a-form>

            </div>

            <a-empty v-else-if="!reportLoading" description="No open register found." />
        </a-spin>
    </a-modal>
</template>

<script>
import { ref, computed, watch } from "vue";

// Colour palette for payment method dots
const METHOD_COLORS = {
    cash:         '#52c41a',
    'easy paisa': '#13c2c2',
    easypaisa:    '#13c2c2',
    jazzcash:     '#fa8c16',
    card:         '#1677ff',
    bank:         '#722ed1',
    'bank transfer': '#722ed1',
    cheque:       '#9254de',
    online:       '#36cfc9',
};
function methodColor(name) {
    return METHOD_COLORS[(name || '').toLowerCase()] || '#8c8c8c';
}

export default {
    props: { visible: { type: Boolean, default: false } },
    emits: ['cancelled', 'closed'],
    setup(props, { emit }) {
        const report           = ref(null);
        const paymentRows      = ref([]);
        const colTotals        = ref({ normal: 0, credit: 0, advance: 0, grand: 0 });
        const expenseBreakdown = ref([]);
        const reportLoading    = ref(false);
        const closeLoading     = ref(false);
        const actualCash       = ref(null);
        const closeNotes       = ref('');
        const errorMsg         = ref('');

        // Only show columns that actually have data
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
            if (v === null || v === undefined) return 'Rs. 0.00';
            return 'Rs. ' + parseFloat(v).toLocaleString('en-PK', {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
        };

        const formatDt = (dt) => {
            if (!dt) return '-';
            return new Date(dt).toLocaleString('en-PK', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        };

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
            report, paymentRows, colTotals, expenseBreakdown,
            reportLoading, closeLoading,
            actualCash, closeNotes, errorMsg,
            colVisible, difference,
            fmt, formatDt, methodColor, handleClose,
        };
    },
};
</script>

<style scoped>
/* ── Stat Cards ─────────────────────────────────────────── */
.cr-card { border-radius: 8px; padding: 10px 12px; text-align: center; border: 1px solid transparent; }
.cr-card-label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
.cr-card-value { font-size: 15px; font-weight: 700; }
.cr-blue  { background: #e6f4ff; border-color: #91caff; } .cr-blue  .cr-card-value { color: #1677ff; }
.cr-green { background: #f6ffed; border-color: #b7eb8f; } .cr-green .cr-card-value { color: #389e0d; }
.cr-red   { background: #fff1f0; border-color: #ffa39e; } .cr-red   .cr-card-value { color: #cf1322; }

/* ── Section Label ──────────────────────────────────────── */
.cr-section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #555; margin-bottom: 6px; }

/* ── Matrix Table ───────────────────────────────────────── */
.cr-matrix-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.cr-matrix-table th, .cr-matrix-table td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; }
.cr-th-method { text-align: left; color: #555; font-weight: 600; background: #fafafa; }
.cr-th-type   { text-align: right; color: #555; font-weight: 600; background: #fafafa; min-width: 110px; }
.cr-th-total  { text-align: right; color: #333; font-weight: 700; background: #f5f5f5; min-width: 120px; }
.cr-td-method { color: #333; display: flex; align-items: center; gap: 7px; }
.cr-td-amount { text-align: right; color: #555; }
.cr-td-total  { text-align: right; font-weight: 700; color: #1677ff; }
.cr-row-even  { background: #fff; }
.cr-row-odd   { background: #fafafa; }
.cr-col-advance { color: #722ed1 !important; }
.cr-col-credit  { color: #d46b08 !important; }
.cr-footer-row  { background: #f0f5ff !important; border-top: 2px solid #adc6ff; }
.cr-footer-cell { font-weight: 700; color: #1d39c4; }
.cr-footer-grand{ text-align: right; font-weight: 800; color: #1d39c4; font-size: 14px; }

/* ── Method dot ─────────────────────────────────────────── */
.cr-method-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }

/* ── Expected Closing Box ───────────────────────────────── */
.cr-expected-box {
    background: #e6f4ff; border: 1px solid #91caff; border-radius: 8px;
    padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;
}

/* ── Difference Indicator ───────────────────────────────── */
.cr-diff-box { border-radius: 8px; padding: 12px 16px; text-align: center; margin-bottom: 12px; }
.cr-diff-positive { background: #f6ffed; border: 1px solid #b7eb8f; }
.cr-diff-negative { background: #fff1f0; border: 1px solid #ffa39e; }
.cr-diff-label  { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
.cr-diff-amount { font-size: 26px; font-weight: 800; }
.cr-diff-positive .cr-diff-amount { color: #389e0d; }
.cr-diff-negative .cr-diff-amount { color: #cf1322; }
.cr-diff-sub    { font-size: 11px; color: #888; margin-top: 4px; }
</style>
