<template>
    <a-drawer
        :title="$t('payments.order_payment')"
        :width="drawerWidth"
        :maskClosable="false"
        :open="visible"
        @close="drawerClosed"
    >
        <a-row :gutter="[16, 16]">
            <!-- LEFT: Order Summary -->
            <a-col :xs="24" :sm="24" :md="8" :lg="8">
                <a-card size="small" style="background: #f8f9fa;">
                    <a-statistic
                        :title="$t('stock.total_items')"
                        :value="selectedProducts.length"
                        style="margin-bottom: 16px"
                    />
                    <a-divider style="margin: 8px 0" />
                    <a-statistic
                        :title="$t('stock.payable_amount')"
                        :value="formatAmountCurrency(data.subtotal)"
                        style="margin-bottom: 12px"
                    />
                    <a-statistic
                        :title="$t('stock.paying_amount')"
                        :value="formatAmountCurrency(totalEnteredAmount)"
                        style="margin-bottom: 12px"
                    />
                    <a-divider style="margin: 8px 0" />
                    <a-statistic
                        v-if="totalEnteredAmount <= data.subtotal"
                        :title="$t('payments.due_amount')"
                        :value="formatAmountCurrency(data.subtotal - totalEnteredAmount)"
                        :value-style="{ color: data.subtotal - totalEnteredAmount > 0 ? '#cf1322' : '#3f8600' }"
                    />
                    <a-statistic
                        v-else
                        :title="$t('stock.change_return')"
                        :value="formatAmountCurrency(totalEnteredAmount - data.subtotal)"
                        :value-style="{ color: '#3f8600' }"
                    />
                </a-card>
            </a-col>

            <!-- RIGHT: Payment Form -->
            <a-col :xs="24" :sm="24" :md="16" :lg="16">
                <!-- Salesman -->
                <a-form-item label="Salesman" style="margin-bottom: 12px;">
                    <a-select
                        v-model:value="selectedSalesmanXid"
                        placeholder="Select Salesman (optional)"
                        style="width: 100%"
                        optionFilterProp="label"
                        show-search
                        allow-clear
                    >
                        <a-select-option
                            v-for="salesman in salesmen"
                            :key="salesman.xid"
                            :value="salesman.xid"
                            :label="salesman.name"
                        >
                            {{ salesman.name }}
                        </a-select-option>
                    </a-select>
                </a-form-item>

                <a-divider style="margin: 8px 0" />

                <!-- Quick Pay: always visible -->
                <div style="background: #f0f5ff; border: 1px solid #adc6ff; border-radius: 6px; padding: 14px; margin-bottom: 14px;">
                    <div style="font-weight: 600; margin-bottom: 10px; color: #2f54eb;">
                        Payment
                    </div>
                    <a-row :gutter="12">
                        <a-col :xs="24" :sm="12">
                            <a-form-item
                                :label="$t('payments.payment_mode')"
                                style="margin-bottom: 8px"
                            >
                                <a-select
                                    v-model:value="formData.payment_mode_id"
                                    placeholder="Select Mode"
                                    style="width: 100%"
                                    allow-clear
                                >
                                    <a-select-option
                                        v-for="mode in paymentModes"
                                        :key="mode.xid"
                                        :value="mode.xid"
                                    >
                                        {{ mode.name }}
                                    </a-select-option>
                                </a-select>
                            </a-form-item>
                        </a-col>
                        <a-col :xs="24" :sm="12">
                            <a-form-item
                                :label="$t('stock.paying_amount')"
                                style="margin-bottom: 8px"
                            >
                                <a-input-number
                                    v-model:value="formData.amount"
                                    :prefix="appSetting.currency.symbol"
                                    style="width: 100%"
                                    :min="0"
                                    :precision="2"
                                />
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <a-row :gutter="12">
                        <a-col :span="24">
                            <a-form-item label="Notes (optional)" style="margin-bottom: 4px">
                                <a-input
                                    v-model:value="formData.notes"
                                    placeholder="Notes"
                                />
                            </a-form-item>
                        </a-col>
                    </a-row>
                </div>

                <!-- Split payments list -->
                <div v-if="allPaymentRecords.length > 0" style="margin-bottom: 12px;">
                    <div style="font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #595959;">
                        Additional Payments
                    </div>
                    <a-table
                        :dataSource="allPaymentRecords"
                        :columns="paymentRecordsColumns"
                        :pagination="false"
                        size="small"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'payment_mode'">
                                {{ getPaymentModeName(record.payment_mode_id) }}
                            </template>
                            <template v-if="column.dataIndex === 'amount'">
                                {{ formatAmountCurrency(record.amount) }}
                            </template>
                            <template v-if="column.dataIndex === 'action'">
                                <a-button
                                    type="link"
                                    danger
                                    size="small"
                                    @click="deletePayment(record.id)"
                                >
                                    <template #icon><DeleteOutlined /></template>
                                </a-button>
                            </template>
                        </template>
                    </a-table>
                </div>

                <!-- Add Split Payment button -->
                <a-button
                    v-if="!showSplitForm"
                    size="small"
                    style="margin-bottom: 14px;"
                    @click="showSplitForm = true"
                >
                    <PlusOutlined />
                    Add Split Payment
                </a-button>

                <!-- Split payment form -->
                <div v-if="showSplitForm" style="border: 1px solid #d9d9d9; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                    <div style="font-size: 13px; font-weight: 600; margin-bottom: 10px;">Add Another Payment Method</div>
                    <a-row :gutter="12">
                        <a-col :xs="24" :sm="12">
                            <a-form-item label="Mode" style="margin-bottom: 8px">
                                <a-select
                                    v-model:value="splitFormData.payment_mode_id"
                                    placeholder="Select Mode"
                                    style="width: 100%"
                                    allow-clear
                                >
                                    <a-select-option
                                        v-for="mode in paymentModes"
                                        :key="mode.xid"
                                        :value="mode.xid"
                                    >
                                        {{ mode.name }}
                                    </a-select-option>
                                </a-select>
                            </a-form-item>
                        </a-col>
                        <a-col :xs="24" :sm="12">
                            <a-form-item label="Amount" style="margin-bottom: 8px">
                                <a-input-number
                                    v-model:value="splitFormData.amount"
                                    style="width: 100%"
                                    :min="0"
                                    :precision="2"
                                />
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <a-space>
                        <a-button type="primary" size="small" @click="addSplitPayment">
                            <CheckOutlined /> Add
                        </a-button>
                        <a-button size="small" @click="showSplitForm = false; splitFormData = { payment_mode_id: undefined, amount: 0, notes: '' }">
                            Cancel
                        </a-button>
                    </a-space>
                </div>

                <!-- Complete Order Buttons -->
                <a-row :gutter="[8, 8]">
                    <a-col :span="24">
                        <a-button
                            :loading="loading"
                            block
                            type="primary"
                            size="large"
                            style="height: 48px; font-size: 16px; font-weight: 600;"
                            @click="() => completeOrder('full')"
                        >
                            {{ $t("stock.complete_order") }}
                            <RightOutlined />
                        </a-button>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-tooltip :title="hasCustomer
                            ? 'Full amount goes to customer ledger as DUE. Generates CREDIT INVOICE.'
                            : '⚠ Customer phone/name required for Credit Sale'">
                            <a-button
                                :loading="loading"
                                :disabled="!hasCustomer"
                                block
                                :style="hasCustomer
                                    ? 'border-color: #d48806; color: #d48806; font-weight: 600;'
                                    : 'border-color: #d9d9d9; color: #bfbfbf; font-weight: 600; cursor: not-allowed;'"
                                @click="() => completeOrder('credit')"
                            >
                                Credit Sale
                            </a-button>
                        </a-tooltip>
                        <div v-if="!hasCustomer" style="font-size: 10px; color: #cf1322; margin-top: 2px; text-align: center; font-weight: 500;">
                            ⚠ Requires customer name &amp; phone
                        </div>
                        <div v-else style="font-size: 10px; color: #888; margin-top: 2px; text-align: center;">
                            Full amount on credit — generates Credit Invoice
                        </div>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-tooltip :title="hasCustomer
                            ? 'Advance deposit collected. Order stays pending. Generates ADVANCE RECEIPT.'
                            : '⚠ Customer phone/name required for Advance Booking'">
                            <a-button
                                :loading="loading"
                                :disabled="!hasCustomer"
                                block
                                type="dashed"
                                :style="hasCustomer
                                    ? 'border-color: #722ed1; color: #722ed1; font-weight: 600;'
                                    : 'border-color: #d9d9d9; color: #bfbfbf; font-weight: 600; cursor: not-allowed;'"
                                @click="() => completeOrder('advance')"
                            >
                                Advance Booking
                            </a-button>
                        </a-tooltip>
                        <div v-if="!hasCustomer" style="font-size: 10px; color: #cf1322; margin-top: 2px; text-align: center; font-weight: 500;">
                            ⚠ Requires customer name &amp; phone
                        </div>
                        <div v-else style="font-size: 10px; color: #888; margin-top: 2px; text-align: center;">
                            Collect deposit above — generates Advance Receipt
                        </div>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </a-drawer>
</template>

<script>
import { ref, onMounted, computed, watch } from "vue";
import {
    CheckOutlined,
    PlusOutlined,
    RightOutlined,
    DeleteOutlined,
} from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import { find, filter, sumBy } from "lodash-es";
import { notification } from "ant-design-vue";
import common from "../../../../common/composable/common";
import apiAdmin from "../../../../common/composable/apiAdmin";

export default {
    props: ["visible", "data", "selectedProducts", "sellingWarehouseXid", "quickAddPhone", "quickAddName"],
    emits: ["closed", "success"],
    components: {
        CheckOutlined,
        PlusOutlined,
        RightOutlined,
        DeleteOutlined,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const { appSetting, formatAmountCurrency } = common();
        const paymentModes = ref([]);
        const salesmen = ref([]);
        const selectedSalesmanXid = ref(undefined);

        const formData = ref({
            payment_mode_id: undefined,
            amount: 0,
            notes: "",
        });

        const splitFormData = ref({
            payment_mode_id: undefined,
            amount: 0,
            notes: "",
        });

        const showSplitForm = ref(false);

        const { t } = useI18n();
        const allPaymentRecords = ref([]);
        const paymentRecordsColumns = ref([
            { title: t("payments.payment_mode"), dataIndex: "payment_mode" },
            { title: t("payments.amount"), dataIndex: "amount" },
            { title: t("common.action"), dataIndex: "action" },
        ]);

        onMounted(() => {
            axiosAdmin.get("payment-modes").then((response) => {
                paymentModes.value = response.data;
                // Auto-select the first payment mode (usually Cash)
                if (paymentModes.value.length > 0 && !formData.value.payment_mode_id) {
                    formData.value.payment_mode_id = paymentModes.value[0].xid;
                }
            });
            axiosAdmin.get("users?user_type=staff_members&limit=10000").then((response) => {
                salesmen.value = response.data || [];
            });
        });

        // Auto-fill amount with grand total when drawer opens
        watch(() => props.visible, (newVal) => {
            if (newVal) {
                formData.value.amount = props.data.subtotal || 0;
                // Auto-select first payment mode
                if (paymentModes.value.length > 0 && !formData.value.payment_mode_id) {
                    formData.value.payment_mode_id = paymentModes.value[0].xid;
                }
            }
        });

        // Also watch subtotal so if products change, amount updates
        watch(() => props.data && props.data.subtotal, (newVal) => {
            if (props.visible && allPaymentRecords.value.length === 0) {
                formData.value.amount = newVal || 0;
            }
        });

        const drawerClosed = () => {
            formData.value = { payment_mode_id: paymentModes.value[0]?.xid || undefined, amount: 0, notes: "" };
            splitFormData.value = { payment_mode_id: undefined, amount: 0, notes: "" };
            allPaymentRecords.value = [];
            selectedSalesmanXid.value = undefined;
            showSplitForm.value = false;
            emit("closed");
        };

        const addSplitPayment = () => {
            if (!splitFormData.value.payment_mode_id || !splitFormData.value.amount) return;
            allPaymentRecords.value = [
                ...allPaymentRecords.value,
                { ...splitFormData.value, id: Math.random().toString(36).slice(2) },
            ];
            splitFormData.value = { payment_mode_id: undefined, amount: 0, notes: "" };
            showSplitForm.value = false;
        };

        // True when the order has a customer (existing or quick-add via phone/name)
        const hasCustomer = computed(() => {
            const hasUserId = !!(props.data && props.data.user_id);
            const hasPhone  = !!(props.quickAddPhone && String(props.quickAddPhone).trim().length >= 3);
            return hasUserId || hasPhone;
        });

        const completeOrder = (saleMode = "full") => {
            // Credit and Advance require a customer name + phone
            if ((saleMode === "credit" || saleMode === "advance") && !hasCustomer.value) {
                notification.error({
                    message: "Customer Required",
                    description: "Please enter Customer Phone Number and Name before creating a Credit Sale or Advance Booking.",
                    duration: 5,
                });
                return;
            }

            // Combine split records + the current quick-pay form entry
            // For 'full' and 'advance' modes include the entered payment amount;
            // for 'credit' no payment is collected upfront.
            let allPayments = [...allPaymentRecords.value];
            if (
                (saleMode === "full" || saleMode === "advance") &&
                formData.value.amount > 0 &&
                formData.value.payment_mode_id
            ) {
                allPayments = [...allPayments, { ...formData.value }];
            }

            const newFormDataObject = {
                all_payments: allPayments,
                product_items: props.selectedProducts,
                details: props.data,
                selected_warehouse_xid: props.sellingWarehouseXid || null,
                salesman_xid: selectedSalesmanXid.value || null,
                sale_mode: saleMode || "full",
            };

            addEditRequestAdmin({
                url: "pos/save",
                data: newFormDataObject,
                successMessage: props.successMessage,
                success: (res) => {
                    formData.value = { payment_mode_id: paymentModes.value[0]?.xid || undefined, amount: 0, notes: "" };
                    splitFormData.value = { payment_mode_id: undefined, amount: 0, notes: "" };
                    allPaymentRecords.value = [];
                    showSplitForm.value = false;
                    selectedSalesmanXid.value = undefined;
                    emit("success", res.order);
                },
            });
        };

        const getPaymentModeName = (paymentId) => {
            var selectedMode = find(paymentModes.value, ["xid", paymentId]);
            return selectedMode ? selectedMode.name : "-";
        };

        const deletePayment = (paymentId) => {
            allPaymentRecords.value = filter(
                allPaymentRecords.value,
                (p) => p.id !== paymentId
            );
        };

        const totalEnteredAmount = computed(() => {
            const splitSum = sumBy(allPaymentRecords.value, (p) => parseFloat(p.amount) || 0);
            const quickAmt = parseFloat(formData.value.amount) || 0;
            return splitSum + quickAmt;
        });

        return {
            loading,
            rules,
            drawerClosed,
            paymentModes,
            salesmen,
            selectedSalesmanXid,
            formData,
            splitFormData,
            showSplitForm,
            appSetting,
            formatAmountCurrency,
            addSplitPayment,
            allPaymentRecords,
            paymentRecordsColumns,
            completeOrder,
            getPaymentModeName,
            deletePayment,
            totalEnteredAmount,
            hasCustomer,
            drawerWidth: window.innerWidth <= 991 ? "90%" : "55%",
        };
    },
};
</script>

<style></style>
