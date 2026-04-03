<template>
    <a-modal
        :open="visible"
        title="Close Cash Register"
        :footer="null"
        width="520px"
        centered
        @cancel="$emit('cancelled')"
    >
        <a-spin :spinning="reportLoading">
            <!-- Summary Section -->
            <div v-if="report" style="margin-bottom: 16px;">
                <a-descriptions
                    bordered
                    size="small"
                    :column="1"
                    style="margin-bottom: 16px;"
                >
                    <a-descriptions-item label="📅 Opened At">
                        {{ formatDt(report.register?.opened_at) }}
                    </a-descriptions-item>
                    <a-descriptions-item label="💵 Opening Balance">
                        <strong>{{ fmt(report.register?.opening_balance) }}</strong>
                    </a-descriptions-item>
                    <a-descriptions-item label="🛒 Total Sales">
                        <span style="color: #389e0d; font-weight: 600;">
                            {{ fmt(report.register?.total_sales) }}
                        </span>
                    </a-descriptions-item>
                    <a-descriptions-item label="✅ Cash Received">
                        <span style="color: #1890ff; font-weight: 600;">
                            {{ fmt(report.register?.total_received) }}
                        </span>
                    </a-descriptions-item>
                    <a-descriptions-item label="💸 Total Expenses">
                        <span style="color: #cf1322; font-weight: 600;">
                            {{ fmt(report.register?.total_expense) }}
                        </span>
                    </a-descriptions-item>
                    <a-descriptions-item label="🏦 Expected Closing">
                        <strong style="font-size: 15px;">
                            {{ fmt(report.expected_closing) }}
                        </strong>
                    </a-descriptions-item>
                </a-descriptions>

                <a-divider>Enter Actual Cash Count</a-divider>

                <a-form layout="vertical">
                    <a-form-item label="Actual Cash in Hand (counted)">
                        <a-input-number
                            v-model:value="actualCash"
                            :min="0"
                            :precision="2"
                            :step="100"
                            size="large"
                            style="width: 100%; font-size: 18px;"
                            placeholder="Enter amount you have in drawer"
                            @change="calcDifference"
                        />
                    </a-form-item>

                    <!-- Difference box -->
                    <div
                        v-if="actualCash !== null && actualCash !== undefined && actualCash !== ''"
                        style="border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; text-align: center;"
                        :style="{
                            background: difference >= 0 ? '#f6ffed' : '#fff1f0',
                            border: difference >= 0 ? '1px solid #b7eb8f' : '1px solid #ffa39e',
                        }"
                    >
                        <div style="font-size: 12px; color: #666; margin-bottom: 4px;">
                            {{ difference >= 0 ? '📈 EXCESS' : '📉 SHORT' }}
                        </div>
                        <div
                            style="font-size: 22px; font-weight: 700;"
                            :style="{ color: difference >= 0 ? '#389e0d' : '#cf1322' }"
                        >
                            {{ difference >= 0 ? '+' : '' }}{{ fmt(Math.abs(difference)) }}
                        </div>
                        <div style="font-size: 11px; color: #888; margin-top: 2px;">
                            Actual <strong>{{ fmt(actualCash) }}</strong>
                            vs Expected <strong>{{ fmt(report.expected_closing) }}</strong>
                        </div>
                    </div>

                    <a-form-item label="Notes (optional)">
                        <a-input v-model:value="closeNotes" placeholder="End-of-day notes" allow-clear />
                    </a-form-item>

                    <a-alert
                        v-if="errorMsg"
                        type="error"
                        :message="errorMsg"
                        show-icon
                        style="margin-bottom: 12px;"
                    />

                    <a-row :gutter="8">
                        <a-col :span="12">
                            <a-button block @click="$emit('cancelled')">
                                Cancel
                            </a-button>
                        </a-col>
                        <a-col :span="12">
                            <a-button
                                type="primary"
                                danger
                                block
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
        </a-spin>
    </a-modal>
</template>

<script>
import { ref, computed, watch } from "vue";
export default {
    props: {
        visible: { type: Boolean, default: false },
    },
    emits: ["cancelled", "closed"],
    setup(props, { emit }) {
        const report       = ref(null);
        const reportLoading = ref(false);
        const closeLoading = ref(false);
        const actualCash   = ref(null);
        const closeNotes   = ref('');
        const errorMsg     = ref('');

        const difference = computed(() => {
            if (!report.value) return 0;
            return (parseFloat(actualCash.value) || 0) - (report.value.expected_closing || 0);
        });

        const fmt = (v) => {
            if (v === null || v === undefined) return 'Rs. 0';
            return 'Rs. ' + parseFloat(v).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        };

        const formatDt = (dt) => {
            if (!dt) return '-';
            return new Date(dt).toLocaleString('en-PK');
        };

        const loadReport = () => {
            if (!props.visible) return;
            reportLoading.value = true;
            report.value = null;
            actualCash.value = null;
            closeNotes.value = '';
            errorMsg.value   = '';
            axiosAdmin.get('cash-register/report').then(res => {
                report.value = res.data;
            }).finally(() => {
                reportLoading.value = false;
            });
        };

        const calcDifference = () => { /* computed handles it */ };

        const handleClose = () => {
            errorMsg.value = '';
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
            report, reportLoading, closeLoading,
            actualCash, closeNotes, errorMsg,
            difference, fmt, formatDt, calcDifference, handleClose,
        };
    },
};
</script>
