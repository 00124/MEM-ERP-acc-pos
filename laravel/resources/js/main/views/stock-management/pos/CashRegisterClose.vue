<template>
    <a-modal
        :open="visible"
        title="Close Cash Register — Daily Summary"
        :footer="null"
        width="600px"
        centered
        @cancel="$emit('cancelled')"
    >
        <a-spin :spinning="reportLoading">
            <div v-if="report">

                <!-- ── Header: Opened At ──────────────────────────────── -->
                <a-alert
                    type="info"
                    :message="`Session opened: ${formatDt(report.register?.opened_at)}`"
                    show-icon
                    style="margin-bottom: 14px;"
                />

                <!-- ── Top Summary Row ────────────────────────────────── -->
                <a-row :gutter="[10, 10]" style="margin-bottom: 14px;">
                    <a-col :span="8">
                        <div class="cr-stat-card cr-stat-blue">
                            <div class="cr-stat-label">Opening Balance</div>
                            <div class="cr-stat-value">{{ fmt(report.register?.opening_balance) }}</div>
                        </div>
                    </a-col>
                    <a-col :span="8">
                        <div class="cr-stat-card cr-stat-green">
                            <div class="cr-stat-label">Total Sales</div>
                            <div class="cr-stat-value">{{ fmt(report.register?.total_sales) }}</div>
                        </div>
                    </a-col>
                    <a-col :span="8">
                        <div class="cr-stat-card cr-stat-red">
                            <div class="cr-stat-label">Total Expenses</div>
                            <div class="cr-stat-value">{{ fmt(report.register?.total_expense) }}</div>
                        </div>
                    </a-col>
                </a-row>

                <!-- ── Payment Method Breakdown ───────────────────────── -->
                <div class="cr-section-title">💳 Received by Payment Method</div>
                <a-table
                    :dataSource="paymentBreakdown"
                    :columns="paymentColumns"
                    :pagination="false"
                    size="small"
                    :rowKey="(r, i) => i"
                    style="margin-bottom: 14px;"
                    :locale="{ emptyText: 'No cash received yet' }"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'name'">
                            <span style="font-weight: 600;">{{ record.name }}</span>
                        </template>
                        <template v-if="column.key === 'total'">
                            <span style="color: #389e0d; font-weight: 700;">{{ fmt(record.total) }}</span>
                        </template>
                    </template>
                    <template #summary>
                        <a-table-summary fixed>
                            <a-table-summary-row style="background: #f6ffed;">
                                <a-table-summary-cell :index="0">
                                    <strong>Total Received</strong>
                                </a-table-summary-cell>
                                <a-table-summary-cell :index="1">
                                    <strong style="color: #389e0d; font-size: 14px;">
                                        {{ fmt(report.register?.total_received) }}
                                    </strong>
                                </a-table-summary-cell>
                            </a-table-summary-row>
                        </a-table-summary>
                    </template>
                </a-table>

                <!-- ── Expense Category Breakdown ─────────────────────── -->
                <div
                    v-if="expenseBreakdown.length > 0"
                    class="cr-section-title"
                >
                    💸 Expenses by Category
                </div>
                <a-table
                    v-if="expenseBreakdown.length > 0"
                    :dataSource="expenseBreakdown"
                    :columns="expenseColumns"
                    :pagination="false"
                    size="small"
                    :rowKey="(r, i) => i"
                    style="margin-bottom: 14px;"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'name'">
                            <span style="font-weight: 600;">{{ record.name }}</span>
                        </template>
                        <template v-if="column.key === 'total'">
                            <span style="color: #cf1322; font-weight: 700;">{{ fmt(record.total) }}</span>
                        </template>
                    </template>
                    <template #summary>
                        <a-table-summary fixed>
                            <a-table-summary-row style="background: #fff1f0;">
                                <a-table-summary-cell :index="0">
                                    <strong>Total Expenses</strong>
                                </a-table-summary-cell>
                                <a-table-summary-cell :index="1">
                                    <strong style="color: #cf1322; font-size: 14px;">
                                        {{ fmt(report.register?.total_expense) }}
                                    </strong>
                                </a-table-summary-cell>
                            </a-table-summary-row>
                        </a-table-summary>
                    </template>
                </a-table>

                <!-- ── Expected Closing ───────────────────────────────── -->
                <div
                    style="
                        background: #f0f5ff; border: 1px solid #adc6ff;
                        border-radius: 8px; padding: 12px 16px;
                        margin-bottom: 16px; display: flex;
                        justify-content: space-between; align-items: center;
                    "
                >
                    <span style="font-weight: 600; color: #1d39c4;">🏦 Expected Closing Balance</span>
                    <span style="font-size: 20px; font-weight: 800; color: #1d39c4;">
                        {{ fmt(report.expected_closing) }}
                    </span>
                </div>

                <a-divider style="margin: 12px 0;">Enter Actual Cash Count</a-divider>

                <!-- ── Actual Cash Input ──────────────────────────────── -->
                <a-form layout="vertical">
                    <a-form-item label="Actual Cash in Drawer (physically counted)">
                        <a-input-number
                            v-model:value="actualCash"
                            :min="0"
                            :precision="2"
                            :step="100"
                            size="large"
                            style="width: 100%; font-size: 18px;"
                            placeholder="Count your cash and enter here"
                        />
                    </a-form-item>

                    <!-- Difference indicator -->
                    <div
                        v-if="actualCash !== null && actualCash !== undefined && actualCash !== ''"
                        style="border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; text-align: center;"
                        :style="{
                            background: difference >= 0 ? '#f6ffed' : '#fff1f0',
                            border: difference >= 0 ? '1px solid #b7eb8f' : '1px solid #ffa39e',
                        }"
                    >
                        <div style="font-size: 11px; color: #666; margin-bottom: 2px; letter-spacing: 1px; text-transform: uppercase;">
                            {{ difference === 0 ? '✅ Balanced' : difference > 0 ? '📈 Excess' : '📉 Short' }}
                        </div>
                        <div
                            style="font-size: 26px; font-weight: 800;"
                            :style="{ color: difference >= 0 ? '#389e0d' : '#cf1322' }"
                        >
                            {{ difference > 0 ? '+' : '' }}{{ fmt(difference) }}
                        </div>
                        <div style="font-size: 11px; color: #888; margin-top: 4px;">
                            Counted <strong>{{ fmt(actualCash) }}</strong>
                            &nbsp;·&nbsp; Expected <strong>{{ fmt(report.expected_closing) }}</strong>
                        </div>
                    </div>

                    <a-form-item label="Closing Notes (optional)">
                        <a-input
                            v-model:value="closeNotes"
                            placeholder="Any end-of-day remarks"
                            allow-clear
                        />
                    </a-form-item>

                    <a-alert
                        v-if="errorMsg"
                        type="error"
                        :message="errorMsg"
                        show-icon
                        style="margin-bottom: 12px;"
                    />

                    <a-row :gutter="10">
                        <a-col :span="12">
                            <a-button block size="large" @click="$emit('cancelled')">
                                Cancel
                            </a-button>
                        </a-col>
                        <a-col :span="12">
                            <a-button
                                type="primary"
                                danger
                                block
                                size="large"
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

const paymentColumns = [
    { title: 'Payment Method', dataIndex: 'name', key: 'name' },
    { title: 'Amount Received', dataIndex: 'total', key: 'total', align: 'right' },
];
const expenseColumns = [
    { title: 'Expense Category', dataIndex: 'name', key: 'name' },
    { title: 'Amount', dataIndex: 'total', key: 'total', align: 'right' },
];

export default {
    props: {
        visible: { type: Boolean, default: false },
    },
    emits: ["cancelled", "closed"],
    setup(props, { emit }) {
        const report          = ref(null);
        const paymentBreakdown = ref([]);
        const expenseBreakdown = ref([]);
        const reportLoading   = ref(false);
        const closeLoading    = ref(false);
        const actualCash      = ref(null);
        const closeNotes      = ref('');
        const errorMsg        = ref('');

        const difference = computed(() => {
            if (!report.value) return 0;
            const exp = report.value.expected_closing ?? 0;
            return (parseFloat(actualCash.value) || 0) - exp;
        });

        const fmt = (v) => {
            if (v === null || v === undefined) return 'Rs. 0.00';
            const n = parseFloat(v);
            return 'Rs. ' + n.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
            reportLoading.value  = true;
            report.value         = null;
            paymentBreakdown.value = [];
            expenseBreakdown.value = [];
            actualCash.value     = null;
            closeNotes.value     = '';
            errorMsg.value       = '';

            axiosAdmin.get('cash-register/report').then(res => {
                report.value           = res.data;
                paymentBreakdown.value = res.data.payment_breakdown || [];
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
            report, paymentBreakdown, expenseBreakdown,
            reportLoading, closeLoading,
            actualCash, closeNotes, errorMsg,
            difference, fmt, formatDt,
            paymentColumns, expenseColumns,
            handleClose,
        };
    },
};
</script>

<style scoped>
.cr-stat-card {
    border-radius: 8px;
    padding: 10px 12px;
    text-align: center;
    border: 1px solid transparent;
}
.cr-stat-label {
    font-size: 11px;
    color: #666;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.cr-stat-value {
    font-size: 16px;
    font-weight: 700;
}
.cr-stat-blue  { background: #e6f4ff; border-color: #91caff; }
.cr-stat-blue .cr-stat-value  { color: #1677ff; }
.cr-stat-green { background: #f6ffed; border-color: #b7eb8f; }
.cr-stat-green .cr-stat-value { color: #389e0d; }
.cr-stat-red   { background: #fff1f0; border-color: #ffa39e; }
.cr-stat-red .cr-stat-value   { color: #cf1322; }
.cr-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #555;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>
