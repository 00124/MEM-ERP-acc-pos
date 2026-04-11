<template>
    <a-modal
        :open="visible"
        :closable="false"
        :centered="true"
        title="Log Warranty / Damage"
        @ok="onSubmit"
    >
        <a-form layout="vertical">
            <!-- Product -->
            <a-form-item
                label="Product"
                :help="rules.product_id ? rules.product_id.message : null"
                :validateStatus="rules.product_id ? 'error' : null"
                class="required"
            >
                <ProductSearchInput
                    @valueChanged="(pid) => (formData.product_id = pid)"
                    @valueSuccess="fetchStock"
                    :productData="data"
                />
            </a-form-item>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="8" :md="8">
                    <a-form-item label="Current Stock">
                        <span style="font-weight:600">{{ stockValue }}</span>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="8" :md="8">
                    <a-form-item
                        label="Quantity"
                        :help="rules.quantity ? rules.quantity.message : null"
                        :validateStatus="rules.quantity ? 'error' : null"
                        class="required"
                    >
                        <a-input-number
                            v-model:value="formData.quantity"
                            :min="1"
                            style="width:100%"
                            placeholder="Qty"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="8" :md="8">
                    <a-form-item
                        label="Type"
                        :help="rules.warranty_type ? rules.warranty_type.message : null"
                        :validateStatus="rules.warranty_type ? 'error' : null"
                        class="required"
                    >
                        <a-select
                            v-model:value="formData.warranty_type"
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
                v-if="formData.warranty_type"
                :message="typeDesc[formData.warranty_type]"
                type="info"
                show-icon
                style="margin-bottom:14px"
            />

            <a-form-item label="Remarks / Notes">
                <a-textarea
                    v-model:value="formData.remarks"
                    placeholder="Vendor name, invoice, damage description..."
                    :rows="3"
                />
            </a-form-item>
        </a-form>

        <template #footer>
            <a-button key="submit" type="primary" :loading="loading" @click="onSubmit">
                <template #icon><SaveOutlined /></template>
                {{ addEditType === 'add' ? 'Save Record' : 'Update' }}
            </a-button>
            <a-button key="back" @click="onClose">Cancel</a-button>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent, ref } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import apiAdmin from "../../../../common/composable/apiAdmin";
import ProductSearchInput from "../../../../common/components/product/ProductSearchInput.vue";
import common from "../../../../common/composable/common";

export default defineComponent({
    props: [
        "formData",
        "data",
        "visible",
        "url",
        "addEditType",
        "pageTitle",
        "successMessage",
    ],
    emits: ["addEditSuccess", "closed"],
    components: {
        SaveOutlined,
        ProductSearchInput,
    },
    setup(props, { emit }) {
        const { selectedWarehouse } = common();
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const stockValue = ref("-");

        const typeDesc = {
            damage:           "Damaged stock will be removed from available inventory upon approval.",
            expired:          "Expired-warranty stock will be removed from available inventory upon approval.",
            claimable:        "Stock under vendor claim — removed until the vendor replaces it.",
            return_to_vendor: "Items being returned to vendor — stock restored when replacement arrives.",
        };

        const fetchStock = () => {
            if (props.formData && props.formData.product_id) {
                axiosAdmin
                    .post("product-warehouse-stock", {
                        warehouse_id: selectedWarehouse.value.id,
                        product_id:   props.formData.product_id,
                    })
                    .then((res) => {
                        stockValue.value = res.data.stock;
                    });
            } else {
                stockValue.value = "-";
            }
        };

        const onSubmit = () => {
            addEditRequestAdmin({
                url:            props.url,
                data:           props.formData,
                successMessage: props.successMessage,
                success: (res) => {
                    emit("addEditSuccess", res.xid);
                },
            });
        };

        const onClose = () => {
            rules.value = {};
            emit("closed");
        };

        return {
            loading,
            rules,
            stockValue,
            typeDesc,
            fetchStock,
            onSubmit,
            onClose,
        };
    },
});
</script>
