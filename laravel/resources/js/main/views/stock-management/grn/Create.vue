<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header
                title="GRN"
                @back="() => $router.push({ name: 'admin.stock.grn.index' })"
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
                <a-breadcrumb-item>{{ $t('common.create') }}</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-table-content>
    <a-card class="page-content-container mt-20 mb-20">
        <a-form layout="vertical">
            <a-row :gutter="16">
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
                            @change="onPoSelect"
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
            <a-row :gutter="16">
                <a-col :span="24">
                    <a-form-item label="Add items">
                        <a-select
                            v-model:value="productSearchSelected"
                            placeholder="Search product by name or item code (type to search)"
                            allow-clear
                            show-search
                            :filter-option="false"
                            style="width: 100%"
                            :loading="productSearchLoading"
                            @search="onProductSearch"
                            @select="onProductSelect"
                        >
                            <template v-if="!productSearchLoading && productSearchList.length === 0" #notFoundContent>
                                Type at least one character to search for products.
                            </template>
                            <a-select-option
                                v-for="prod in productSearchList"
                                :key="prod.xid"
                                :value="prod.xid"
                                :label="prod.name"
                            >
                                {{ prod.name }} ({{ prod.item_code || '-' }})
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item label="Or load from PO">
                        <a-button
                            type="default"
                            :disabled="!formData.parent_order_id || poLoading"
                            :loading="poLoading"
                            @click="loadPoItems"
                        >
                            Load PO Items
                        </a-button>
                    </a-form-item>
                    <a-table
                        :row-key="(r) => r.xid || r.sn"
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
                                <a-input-number
                                    v-model:value="record.quantity"
                                    :min="0"
                                    style="width: 90px"
                                    @change="recalculateSummary"
                                />
                            </template>
                            <template v-else-if="column.dataIndex === 'received_qty'">
                                <a-input-number
                                    v-model:value="record.received_quantity"
                                    :min="0"
                                    style="width: 90px"
                                    @change="recalculateSummary"
                                />
                            </template>
                            <template v-else-if="column.dataIndex === 'short_damaged'">
                                <a-input-number
                                    v-model:value="record.short_damaged_quantity"
                                    :min="0"
                                    style="width: 90px"
                                    @change="recalculateSummary"
                                />
                            </template>
                            <template v-else-if="column.dataIndex === 'action'">
                                <a-button type="link" danger size="small" @click="removeItem(record)">{{ $t('common.delete') }}</a-button>
                            </template>
                        </template>
                    </a-table>
                </a-col>
            </a-row>

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
    </admin-page-table-content>
</template>
<script>
import { ref, reactive, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { SaveOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import common from "../../../../common/composable/common";
import { message } from "ant-design-vue";

export default {
    components: { SaveOutlined, AdminPageHeader },
    setup() {
        const router = useRouter();
        const { t } = useI18n();
        const { dayjs } = common();
        const loading = ref(false);
        const poLoading = ref(false);
        const dateFormat = "YYYY-MM-DD";
        const warehouses = ref([]);
        const suppliers = ref([]);
        const purchaseOrders = ref([]);
        const selectedProducts = ref([]);

        const formData = reactive({
            order_date: dayjs ? dayjs().format("YYYY-MM-DD") : new Date().toISOString().slice(0, 10),
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

        function recalculateSummary() {}
        function formatDate(d) {
            if (!d) return "-";
            return dayjs ? dayjs(d).format(dateFormat) : String(d);
        }

        function loadPoItems() {
            if (!formData.parent_order_id) return;
            poLoading.value = true;
            axiosAdmin
                .get(`grn/purchase-order/${formData.parent_order_id}`)
                .then((res) => {
                    const data = res.data || res;
                    formData.user_id = data.order?.x_user_id || data.order?.user_id;
                    formData.warehouse_id = data.order?.x_warehouse_id || data.order?.warehouse_id;
                    selectedProducts.value = (data.items || []).map((it, i) => ({
                        ...it,
                        sn: i + 1,
                        short_damaged_quantity: it.short_damaged_quantity ?? 0,
                    }));
                })
                .finally(() => (poLoading.value = false));
        }

        function onPoSelect() {
            if (formData.parent_order_id) loadPoItems();
            else selectedProducts.value = [];
        }

        function removeItem(record) {
            selectedProducts.value = selectedProducts.value.filter((p) => (p.xid || p.sn) !== (record.xid || record.sn));
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
                unit_price: Number(p.unit_price) || 0,
                subtotal: Number(p.subtotal) || 0,
                tax_rate: Number(p.tax_rate) || 0,
                tax_type: p.tax_type || "exclusive",
                discount_rate: Number(p.discount_rate) || 0,
                total_discount: Number(p.total_discount) || 0,
                total_tax: Number(p.total_tax) || 0,
                single_unit_price: Number(p.single_unit_price || p.unit_price) || 0,
                x_unit_id: p.x_unit_id || "",
                x_tax_id: p.x_tax_id || "",
            }));

            const orderDate = formData.order_date
                ? (dayjs ? dayjs(formData.order_date).format("YYYY-MM-DDTHH:mm:ssZ") : String(formData.order_date).slice(0, 10) + "T00:00:00.000Z")
                : (dayjs ? dayjs().format("YYYY-MM-DDTHH:mm:ssZ") : new Date().toISOString());
            const payload = {
                order_date: orderDate,
                order_status: "received",
                user_id: formData.user_id,
                warehouse_id: formData.warehouse_id,
                product_items,
                total_items: product_items.length,
                subtotal: product_items.reduce((s, i) => s + (Number(i.subtotal) || 0), 0),
                total: product_items.reduce((s, i) => s + (Number(i.subtotal) || 0), 0),
                all_payments: [],
            };
            if (formData.parent_order_id) payload.parent_order_id = formData.parent_order_id;
            if (formData.supplier_invoice_number) payload.supplier_invoice_number = formData.supplier_invoice_number;
            if (formData.delivery_challan_no) payload.delivery_challan_no = formData.delivery_challan_no;
            if (formData.received_by_name) payload.received_by_name = formData.received_by_name;
            if (formData.received_by_date) payload.received_by_date = dayjs ? dayjs(formData.received_by_date).format("YYYY-MM-DD") : formData.received_by_date;
            if (formData.checked_by_name) payload.checked_by_name = formData.checked_by_name;
            if (formData.checked_by_date) payload.checked_by_date = dayjs ? dayjs(formData.checked_by_date).format("YYYY-MM-DD") : formData.checked_by_date;
            if (formData.approved_by_name) payload.approved_by_name = formData.approved_by_name;
            if (formData.approved_by_date) payload.approved_by_date = dayjs ? dayjs(formData.approved_by_date).format("YYYY-MM-DD") : formData.approved_by_date;

            loading.value = true;
            axiosAdmin
                .post("grn", payload)
                .then((res) => {
                    message.success("GRN created successfully.");
                    const xid = (res.data && res.data.xid) || (res.data && res.data.order && res.data.order.xid);
                    if (xid) router.push({ name: "admin.stock.grn.details", params: { id: xid } });
                    else router.push({ name: "admin.stock.grn.index" });
                })
                .catch((e) => {
                    const data = e?.data || {};
                    const msg = data?.error?.message || data?.message || (data?.errors && Object.values(data.errors).flat().length ? Object.values(data.errors).flat()[0] : null) || (typeof e?.message === "string" ? e.message : null) || "An error occurred while saving the GRN.";
                    message.error(msg);
                })
                .finally(() => (loading.value = false));
        }

        onMounted(() => {
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
        });

        const productSearchSelected = ref(null);
        const productSearchList = ref([]);
        const productSearchLoading = ref(false);
        let productSearchTimer = null;

        function onProductSearch(term) {
            const searchTerm = typeof term === "string" ? term.trim() : "";
            if (!searchTerm) {
                productSearchList.value = [];
                return;
            }
            if (productSearchTimer) clearTimeout(productSearchTimer);
            productSearchTimer = setTimeout(() => {
                productSearchLoading.value = true;
                axiosAdmin
                    .post("search-product", {
                        order_type: "purchases",
                        search_term: searchTerm,
                        warehouse_id: formData.warehouse_id || undefined,
                    })
                    .then((res) => {
                        // axiosAdmin interceptor returns response.data, so res = { message, data: [...] }
                        const list = (res && Array.isArray(res.data) ? res.data : null)
                            ?? (Array.isArray(res) ? res : []);
                        productSearchList.value = list;
                    })
                    .catch(() => (productSearchList.value = []))
                    .finally(() => (productSearchLoading.value = false));
            }, 300);
        }

        function onProductSelect(xid) {
            const product = productSearchList.value.find((p) => p.xid === xid);
            if (!product) return;
            const already = selectedProducts.value.some((p) => (p.xid || p.item_id) === (product.xid || product.id));
            if (already) {
                message.warning("Item already added.");
                productSearchSelected.value = null;
                return;
            }
            const qty = Number(product.quantity) || 1;
            const unitPrice = Number(product.unit_price) || Number(product.single_unit_price) || 0;
            selectedProducts.value = [
                ...selectedProducts.value,
                {
                    ...product,
                    sn: selectedProducts.value.length + 1,
                    quantity: qty,
                    po_qty: qty,
                    received_quantity: qty,
                    short_damaged_quantity: 0,
                    unit_price: unitPrice,
                    single_unit_price: unitPrice,
                    subtotal: unitPrice * qty,
                    tax_rate: product.tax_rate || 0,
                    tax_type: product.tax_type || "exclusive",
                    discount_rate: product.discount_rate || 0,
                    total_discount: product.total_discount || 0,
                    total_tax: product.total_tax || 0,
                    x_unit_id: product.x_unit_id || "",
                    x_tax_id: product.x_tax_id || "",
                },
            ];
            productSearchSelected.value = null;
            productSearchList.value = [];
        }

        return {
            loading,
            formData,
            selectedProducts,
            grnItemColumns,
            summary,
            warehouses,
            suppliers,
            purchaseOrders,
            poLoading,
            dateFormat,
            formatDate,
            recalculateSummary,
            loadPoItems,
            onPoSelect,
            removeItem,
            onSubmit,
            productSearchSelected,
            productSearchList,
            productSearchLoading,
            onProductSearch,
            onProductSelect,
        };
    },
};
</script>
