<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header
                title="GRN"
                @back="() => $router.go(-1)"
                class="p-0"
            >
                <template #extra>
                    <a-button type="primary" :loading="loading" @click="onSubmit" block>
                        <template #icon><SaveOutlined /></template>
                        {{ $t('common.save') }}
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t('menu.purchases') }}</a-breadcrumb-item>
                <a-breadcrumb-item><router-link :to="{ name: 'admin.stock.grn.index' }">GRN</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t('common.edit') }}</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card v-if="!fetchError" class="page-content-container mt-20 mb-20">
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Document No">
                        <a-input :value="formData.invoice_number" disabled />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="GRN Date" class="required">
                        <a-date-picker
                            v-model:value="formData.order_date"
                            style="width: 100%"
                            :format="dateFormat"
                            value-format="YYYY-MM-DD"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="PO Number">
                        <a-select
                            v-model:value="formData.parent_order_id"
                            placeholder="Select Purchase Order"
                            allow-clear
                            show-search
                            option-filter-prop="label"
                            style="width: 100%"
                        >
                            <a-select-option
                                v-for="po in purchaseOrders"
                                :key="po.xid"
                                :value="po.xid"
                                :label="po.invoice_number"
                            >
                                {{ po.invoice_number }} ({{ formatDate(po.order_date) }})
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Supplier Name" class="required">
                        <a-select
                            v-model:value="formData.user_id"
                            placeholder="Supplier Name"
                            allow-clear
                            show-search
                            option-filter-prop="label"
                            style="width: 100%"
                        >
                            <a-select-option
                                v-for="s in suppliers"
                                :key="s.xid"
                                :value="s.xid"
                                :label="s.name"
                            >
                                {{ s.name }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Supplier Invoice Number">
                        <a-input v-model:value="formData.supplier_invoice_number" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Delivery Challan No">
                        <a-input v-model:value="formData.delivery_challan_no" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Warehouse / Store" class="required">
                        <a-select
                            v-model:value="formData.warehouse_id"
                            placeholder="Warehouse"
                            allow-clear
                            show-search
                            option-filter-prop="label"
                            style="width: 100%"
                        >
                            <a-select-option
                                v-for="w in warehouses"
                                :key="w.xid"
                                :value="w.xid"
                                :label="w.name"
                            >
                                {{ w.name }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-divider>Item Details</a-divider>
            <a-table
                :row-key="(r) => r.item_id || r.xid || r.sn"
                :data-source="selectedProducts"
                :columns="grnItemColumns"
                :pagination="false"
                size="small"
            >
                <template #bodyCell="{ column, record, index }">
                    <template v-if="column.dataIndex === 'sn'">{{ index + 1 }}</template>
                    <template v-else-if="column.dataIndex === 'item_code'">{{ record.item_code || '-' }}</template>
                    <template v-else-if="column.dataIndex === 'name'">{{ record.name }}</template>
                    <template v-else-if="column.dataIndex === 'po_qty'">
                        <a-input-number v-model:value="record.quantity" :min="0" style="width: 90px" />
                    </template>
                    <template v-else-if="column.dataIndex === 'received_qty'">
                        <a-input-number v-model:value="record.received_quantity" :min="0" style="width: 90px" />
                    </template>
                    <template v-else-if="column.dataIndex === 'short_damaged'">
                        <a-input-number v-model:value="record.short_damaged_quantity" :min="0" style="width: 90px" />
                    </template>
                    <template v-else-if="column.dataIndex === 'action'">
                        <a-button type="link" danger size="small" @click="removeItem(record)">{{ $t('common.delete') }}</a-button>
                    </template>
                </template>
            </a-table>

            <a-divider>Summary</a-divider>
            <a-row :gutter="16">
                <a-col :span="8"><strong>Total PO Items:</strong> {{ summary.totalPo }}</a-col>
                <a-col :span="8"><strong>Total Received:</strong> {{ summary.totalReceived }}</a-col>
                <a-col :span="8"><strong>Total Short/Damaged:</strong> {{ summary.totalShortDamaged }}</a-col>
            </a-row>

            <a-divider>Receiver Information</a-divider>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="8">
                    <a-form-item label="Received By (Name)">
                        <a-input v-model:value="formData.received_by_name" />
                    </a-form-item>
                    <a-form-item label="Received By (Date)">
                        <a-date-picker v-model:value="formData.received_by_date" style="width: 100%" :format="dateFormat" value-format="YYYY-MM-DD" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="8">
                    <a-form-item label="Checked By (Name)">
                        <a-input v-model:value="formData.checked_by_name" />
                    </a-form-item>
                    <a-form-item label="Checked By (Date)">
                        <a-date-picker v-model:value="formData.checked_by_date" style="width: 100%" :format="dateFormat" value-format="YYYY-MM-DD" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="8">
                    <a-form-item label="Approved By (Name)">
                        <a-input v-model:value="formData.approved_by_name" />
                    </a-form-item>
                    <a-form-item label="Approved By (Date)">
                        <a-date-picker v-model:value="formData.approved_by_date" style="width: 100%" :format="dateFormat" value-format="YYYY-MM-DD" />
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
    </a-card>
    <a-spin v-else-if="loadingData" :spinning="true" style="min-height: 200px" />
    <a-result v-else status="404" :title="$t('common.error')" />
</template>
<script>
import { ref, reactive, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { SaveOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import { message } from "ant-design-vue";
import dayjs from "dayjs";

export default {
    components: { SaveOutlined, AdminPageHeader },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const { t } = useI18n();
        const loading = ref(false);
        const loadingData = ref(true);
        const fetchError = ref(false);
        const dateFormat = "YYYY-MM-DD";
        const warehouses = ref([]);
        const suppliers = ref([]);
        const purchaseOrders = ref([]);
        const selectedProducts = ref([]);

        const formData = reactive({
            invoice_number: "",
            order_date: null,
            parent_order_id: undefined,
            user_id: undefined,
            warehouse_id: undefined,
            supplier_invoice_number: "",
            delivery_challan_no: "",
            received_by_name: "",
            received_by_date: null,
            checked_by_name: "",
            checked_by_date: null,
            approved_by_name: "",
            approved_by_date: null,
        });

        const grnItemColumns = [
            { title: "#", dataIndex: "sn", width: 50 },
            { title: "Item Code", dataIndex: "item_code", width: 120 },
            { title: "Name", dataIndex: "name" },
            { title: "PO Qty", dataIndex: "po_qty", width: 120 },
            { title: "Received Qty", dataIndex: "received_qty", width: 120 },
            { title: "Short/Damaged", dataIndex: "short_damaged", width: 120 },
            { title: t("common.action"), dataIndex: "action", width: 80 },
        ];

        const summary = computed(() => {
            let totalPo = 0, totalReceived = 0, totalShort = 0;
            selectedProducts.value.forEach((p) => {
                totalPo += Number(p.quantity) || 0;
                totalReceived += Number(p.received_quantity ?? p.quantity) || 0;
                totalShort += Number(p.short_damaged_quantity) || 0;
            });
            return { totalPo, totalReceived, totalShortDamaged: totalShort };
        });

        function formatDate(d) {
            if (!d) return "-";
            return dayjs(d).format(dateFormat);
        }

        function removeItem(record) {
            selectedProducts.value = selectedProducts.value.filter((p) => (p.item_id || p.xid) !== (record.item_id || record.xid));
        }

        function onSubmit() {
            if (!formData.user_id || !formData.warehouse_id) {
                message.error("Please fill required fields (Supplier and Warehouse).");
                return;
            }
            if (!selectedProducts.value.length) {
                message.error("Add at least one item.");
                return;
            }
            const product_items = selectedProducts.value.map((p) => ({
                xid: p.xid,
                item_id: p.item_id || "",
                quantity: Number(p.quantity) || 0,
                received_quantity: Number(p.received_quantity ?? p.quantity) || 0,
                short_damaged_quantity: Number(p.short_damaged_quantity) || 0,
                unit_price: 0,
                subtotal: 0,
                tax_rate: 0,
                tax_type: "exclusive",
                discount_rate: 0,
                total_discount: 0,
                total_tax: 0,
                single_unit_price: 0,
                x_unit_id: p.x_unit_id || "",
                x_tax_id: "",
            }));

            const payload = {
                order_date: dayjs(formData.order_date).format("YYYY-MM-DDTHH:mm:ssZ"),
                order_status: "received",
                user_id: formData.user_id,
                warehouse_id: formData.warehouse_id,
                parent_order_id: formData.parent_order_id || undefined,
                supplier_invoice_number: formData.supplier_invoice_number || undefined,
                delivery_challan_no: formData.delivery_challan_no || undefined,
                received_by_name: formData.received_by_name || undefined,
                received_by_date: formData.received_by_date ? dayjs(formData.received_by_date).format("YYYY-MM-DD") : undefined,
                checked_by_name: formData.checked_by_name || undefined,
                checked_by_date: formData.checked_by_date ? dayjs(formData.checked_by_date).format("YYYY-MM-DD") : undefined,
                approved_by_name: formData.approved_by_name || undefined,
                approved_by_date: formData.approved_by_date ? dayjs(formData.approved_by_date).format("YYYY-MM-DD") : undefined,
                product_items,
                total_items: product_items.length,
                subtotal: 0,
                total: 0,
                all_payments: [],
            };

            loading.value = true;
            axiosAdmin
                .put(`grn/${route.params.id}`, payload)
                .then(() => {
                    message.success(t("common.updated"));
                    router.push({ name: "admin.stock.grn.details", params: { id: route.params.id } });
                })
                .catch((e) => message.error(e.response?.data?.message || t("common.error")))
                .finally(() => (loading.value = false));
        }

        onMounted(() => {
            const id = route.params.id;
            Promise.all([
                axiosAdmin.get("warehouses?limit=10000"),
                axiosAdmin.get("suppliers?limit=10000"),
                axiosAdmin.get("purchases?limit=1000"),
            ]).then(([w, s, p]) => {
                warehouses.value = Array.isArray(w.data) ? w.data : (w.data?.data ? w.data.data : []) || [];
                suppliers.value = Array.isArray(s.data) ? s.data : (s.data?.data ? s.data.data : []) || [];
                const raw = p.data;
                const poList = Array.isArray(raw) ? raw : (raw?.data ? (Array.isArray(raw.data) ? raw.data : Object.values(raw.data || {})) : []);
                purchaseOrders.value = Array.isArray(poList) ? poList : [];
            });

            if (id) {
                axiosAdmin
                    .get(`grn/${id}`)
                    .then((res) => {
                        const d = res.data || res;
                        const order = d.order || d;
                        const items = d.items || [];
                        formData.invoice_number = order.invoice_number || "";
                        formData.order_date = order.order_date ? dayjs(order.order_date) : null;
                        formData.parent_order_id = order.x_parent_order_id || order.parent_order_id || undefined;
                        formData.user_id = order.x_user_id || order.user_id || undefined;
                        formData.warehouse_id = order.x_warehouse_id || order.warehouse_id || undefined;
                        formData.supplier_invoice_number = order.supplier_invoice_number || "";
                        formData.delivery_challan_no = order.delivery_challan_no || "";
                        formData.received_by_name = order.received_by_name || "";
                        formData.received_by_date = order.received_by_date ? dayjs(order.received_by_date) : null;
                        formData.checked_by_name = order.checked_by_name || "";
                        formData.checked_by_date = order.checked_by_date ? dayjs(order.checked_by_date) : null;
                        formData.approved_by_name = order.approved_by_name || "";
                        formData.approved_by_date = order.approved_by_date ? dayjs(order.approved_by_date) : null;
                        selectedProducts.value = items.map((it, i) => ({
                            xid: it.xid,
                            item_id: it.item_id || it.xid,
                            name: it.name || it.product?.name || "",
                            item_code: it.item_code || it.product?.item_code || "",
                            sn: i + 1,
                            quantity: Number(it.quantity) || 0,
                            po_qty: Number(it.quantity) || 0,
                            received_quantity: Number(it.received_quantity ?? it.quantity) || 0,
                            short_damaged_quantity: Number(it.short_damaged_quantity) || 0,
                            x_unit_id: it.x_unit_id || "",
                        }));
                    })
                    .catch(() => (fetchError.value = true))
                    .finally(() => (loadingData.value = false));
            } else {
                loadingData.value = false;
                fetchError.value = true;
            }
        });

        return {
            loading,
            loadingData,
            fetchError,
            formData,
            selectedProducts,
            grnItemColumns,
            summary,
            warehouses,
            suppliers,
            purchaseOrders,
            dateFormat,
            formatDate,
            removeItem,
            onSubmit,
        };
    },
};
</script>
