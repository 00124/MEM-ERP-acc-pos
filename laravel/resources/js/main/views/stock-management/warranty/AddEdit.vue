<template>
    <a-modal
        :open="visible"
        :title="editRecord ? 'Edit Warranty Record' : 'Log Warranty / Damage'"
        :closable="false"
        :centered="true"
        width="560px"
        @ok="onSubmit"
    >
        <a-form layout="vertical">
            <a-form-item
                label="Product"
                :help="errors.product_id"
                :validate-status="errors.product_id ? 'error' : ''"
                class="required"
            >
                <ProductSearchInput
                    @valueChanged="(id) => { form.product_id = id; }"
                    @valueSuccess="fetchStock"
                    :productData="editRecord"
                />
            </a-form-item>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="8">
                    <a-form-item label="Current Stock">
                        <span style="font-weight:600;font-size:14px">{{ currentStock ?? '—' }}</span>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="8">
                    <a-form-item
                        label="Quantity"
                        :help="errors.quantity"
                        :validate-status="errors.quantity ? 'error' : ''"
                        class="required"
                    >
                        <a-input-number
                            v-model:value="form.quantity"
                            :min="1"
                            style="width:100%"
                            placeholder="Qty"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="8">
                    <a-form-item
                        label="Type"
                        :help="errors.warranty_type"
                        :validate-status="errors.warranty_type ? 'error' : ''"
                        class="required"
                    >
                        <a-select
                            v-model:value="form.warranty_type"
                            placeholder="Select type"
                            style="width:100%"
                        >
                            <a-select-option value="damage">Damaged</a-select-option>
                            <a-select-option value="expired">Expired Warranty</a-select-option>
                            <a-select-option value="claimable">Claimable</a-select-option>
                            <a-select-option value="return_to_vendor">Return to Vendor</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-alert
                v-if="form.warranty_type"
                :message="typeDesc[form.warranty_type]"
                type="info"
                show-icon
                style="margin-bottom:14px"
            />

            <a-form-item label="Remarks / Notes">
                <a-textarea
                    v-model:value="form.remarks"
                    placeholder="Vendor name, invoice, damage description..."
                    :rows="3"
                />
            </a-form-item>
        </a-form>

        <template #footer>
            <a-button type="primary" :loading="loading" @click="onSubmit">
                <template #icon><SaveOutlined /></template>
                {{ editRecord ? 'Update' : 'Save Record' }}
            </a-button>
            <a-button @click="$emit('closed')">Cancel</a-button>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent, ref, watch, reactive } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import { message } from "ant-design-vue";
import ProductSearchInput from "../../../../common/components/product/ProductSearchInput.vue";
import common from "../../../../common/composable/common";

export default defineComponent({
    props: ["visible", "editRecord"],
    emits: ["saved", "closed"],
    components: { SaveOutlined, ProductSearchInput },
    setup(props, { emit }) {
        const { selectedWarehouse } = common();
        const loading      = ref(false);
        const currentStock = ref(null);
        const errors       = reactive({});

        const blankForm = () => ({
            product_id:    undefined,
            quantity:      1,
            warranty_type: undefined,
            remarks:       "",
        });

        const form = reactive(blankForm());

        const typeDesc = {
            damage:           "Damaged stock will be removed from available inventory upon approval.",
            expired:          "Expired-warranty stock will be removed from available inventory upon approval.",
            claimable:        "Stock under vendor claim — removed until the vendor replaces it.",
            return_to_vendor: "Items being returned to vendor — stock restored when replacement arrives.",
        };

        watch(() => props.visible, (v) => {
            if (v) {
                Object.assign(form, blankForm());
                Object.keys(errors).forEach((k) => delete errors[k]);
                currentStock.value = null;

                if (props.editRecord) {
                    form.product_id    = props.editRecord.x_product_id;
                    form.quantity      = props.editRecord.quantity;
                    form.warranty_type = props.editRecord.warranty_type;
                    form.remarks       = props.editRecord.remarks ?? "";
                }
            }
        });

        const fetchStock = () => {
            if (!form.product_id) { currentStock.value = null; return; }
            axiosAdmin
                .post("product-warehouse-stock", {
                    warehouse_id: selectedWarehouse.value.id,
                    product_id:   form.product_id,
                })
                .then((r) => { currentStock.value = r.data.stock; })
                .catch(() => {});
        };

        const onSubmit = async () => {
            Object.keys(errors).forEach((k) => delete errors[k]);
            if (!form.product_id)    errors.product_id    = "Product is required";
            if (!form.quantity)      errors.quantity      = "Quantity is required";
            if (!form.warranty_type) errors.warranty_type = "Type is required";
            if (Object.keys(errors).length) return;

            loading.value = true;
            try {
                const payload = { ...form };
                if (props.editRecord) {
                    await axiosAdmin.put(`stock-adjustments/${props.editRecord.xid}`, payload);
                    message.success("Record updated.");
                } else {
                    await axiosAdmin.post("stock-adjustments", payload);
                    message.success("Warranty record logged. Awaiting approval.");
                }
                emit("saved");
            } catch (e) {
                const data = e?.response?.data ?? e?.data;
                if (data?.error?.details) {
                    Object.assign(errors, data.error.details);
                } else {
                    message.error(data?.message ?? "Save failed");
                }
            } finally {
                loading.value = false;
            }
        };

        return { form, errors, loading, currentStock, typeDesc, fetchStock, onSubmit };
    },
});
</script>
