<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Create Dispatch" class="p-0" @back="() => $router.go(-1)" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.stock.dispatches.index' }">Dispatches</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Create</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card style="margin: 16px">
        <a-form layout="vertical" :model="form">
            <!-- Step 1: Select Sale -->
            <a-row :gutter="[16, 0]">
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Select Sale Invoice" required>
                        <a-select
                            v-model:value="form.sale_id"
                            show-search
                            :filter-option="false"
                            placeholder="Search invoice number..."
                            :loading="salesLoading"
                            @search="searchSales"
                            @change="onSaleSelected"
                            style="width: 100%"
                        >
                            <a-select-option
                                v-for="sale in salesList"
                                :key="sale.xid"
                                :value="sale.xid"
                                :title="sale.invoice_number"
                            >
                                {{ sale.invoice_number }} — {{ sale.user?.name || '' }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Dispatch Date" required>
                        <a-date-picker
                            v-model:value="form.dispatch_date"
                            style="width: 100%"
                            :format="appSetting.date_format || 'DD-MM-YYYY'"
                        />
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="12" :md="4">
                    <a-form-item label="Driver Name">
                        <a-input v-model:value="form.driver_name" placeholder="Driver name" />
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="12" :md="4">
                    <a-form-item label="Vehicle No">
                        <a-input v-model:value="form.vehicle_no" placeholder="e.g. ABC-1234" />
                    </a-form-item>
                </a-col>

                <a-col :xs="24">
                    <a-form-item label="Remarks">
                        <a-textarea v-model:value="form.remarks" :rows="2" />
                    </a-form-item>
                </a-col>
            </a-row>

            <!-- Sale Info Banner -->
            <a-alert
                v-if="saleInfo"
                type="info"
                style="margin-bottom: 16px"
                :message="`Customer: ${saleInfo.customer_name}  |  Salesman: ${saleInfo.salesman_name}`"
                show-icon
            />

            <!-- Items Table -->
            <template v-if="form.items.length > 0">
                <a-divider>
                    Dispatch Items — Select Warehouse Per Product
                </a-divider>

                <a-alert
                    type="warning"
                    message="Group by Warehouse: Items with the same warehouse will be combined into one Dispatch. Items with different warehouses will create separate Dispatches."
                    show-icon
                    style="margin-bottom: 12px"
                />

                <a-table
                    :dataSource="form.items"
                    :columns="itemColumns"
                    :pagination="false"
                    rowKey="xid"
                    size="small"
                    :scroll="{ x: 700 }"
                >
                    <template #bodyCell="{ column, record, index }">
                        <template v-if="column.dataIndex === 'product_name'">
                            <strong>{{ record.product_name }}</strong>
                            <div style="font-size: 11px; color: #888">{{ record.item_code }}</div>
                        </template>
                        <template v-else-if="column.dataIndex === 'quantity'">
                            <a-input-number
                                v-model:value="record.quantity"
                                :min="0.01"
                                :max="record.original_quantity"
                                style="width: 90px"
                            />
                            <span style="color: #888; font-size: 11px; margin-left: 4px">
                                / {{ record.original_quantity }}
                            </span>
                        </template>
                        <template v-else-if="column.dataIndex === 'x_warehouse_id'">
                            <a-select
                                v-model:value="record.x_warehouse_id"
                                placeholder="Select warehouse"
                                style="width: 180px"
                                show-search
                                option-filter-prop="title"
                            >
                                <a-select-option
                                    v-for="wh in warehouses"
                                    :key="wh.xid"
                                    :value="wh.xid"
                                    :title="wh.name"
                                >
                                    {{ wh.name }}
                                </a-select-option>
                            </a-select>
                        </template>
                    </template>
                </a-table>

                <!-- Preview: Groups by warehouse -->
                <a-divider>Dispatch Preview (Grouped by Warehouse)</a-divider>
                <a-row :gutter="[16, 8]" style="margin-bottom: 16px">
                    <a-col
                        v-for="(group, warehouseXid) in groupedItems"
                        :key="warehouseXid"
                        :xs="24" :sm="12" :md="8"
                    >
                        <a-card size="small" :title="warehouseName(warehouseXid)" style="border: 1px solid #1890ff">
                            <div v-for="item in group" :key="item.xid" style="font-size: 12px">
                                {{ item.product_name }} × {{ item.quantity }}
                            </div>
                        </a-card>
                    </a-col>
                </a-row>

                <a-row justify="end">
                    <a-button
                        type="primary"
                        size="large"
                        :loading="submitting"
                        @click="submitDispatch"
                        :disabled="!canSubmit"
                    >
                        <SendOutlined />
                        Create {{ Object.keys(groupedItems).length }} Dispatch(es)
                    </a-button>
                </a-row>
            </template>

            <a-empty
                v-else-if="form.sale_id && !itemsLoading"
                description="No items found for this sale"
            />

            <div v-if="itemsLoading" style="text-align: center; padding: 40px">
                <a-spin size="large" />
                <div style="margin-top: 8px; color: #888">Loading sale items...</div>
            </div>
        </a-form>
    </a-card>
</template>

<script>
import { defineComponent, ref, reactive, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useStore } from "vuex";
import { message } from "ant-design-vue";
import dayjs from "dayjs";
import { SendOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default defineComponent({
    name: "DispatchCreate",
    components: { AdminPageHeader, SendOutlined },
    setup() {
        const router     = useRouter();
        const store      = useStore();
        const appSetting = computed(() => store.state.auth.appSetting);

        const salesList    = ref([]);
        const salesLoading = ref(false);
        const itemsLoading = ref(false);
        const submitting   = ref(false);
        const warehouses   = ref([]);
        const saleInfo     = ref(null);

        const form = reactive({
            sale_id:       undefined,
            dispatch_date: dayjs(),
            driver_name:   "",
            vehicle_no:    "",
            remarks:       "",
            items:         [],
        });

        const itemColumns = [
            { title: "#",           dataIndex: "index",          width: 50,  customRender: ({ index }) => index + 1 },
            { title: "Product",     dataIndex: "product_name",   width: 200 },
            { title: "Sale Qty",    dataIndex: "quantity",       width: 140 },
            { title: "Warehouse",   dataIndex: "x_warehouse_id", width: 200 },
        ];

        const groupedItems = computed(() => {
            const groups = {};
            form.items.forEach((item) => {
                if (item.x_warehouse_id) {
                    if (!groups[item.x_warehouse_id]) groups[item.x_warehouse_id] = [];
                    groups[item.x_warehouse_id].push(item);
                }
            });
            return groups;
        });

        const canSubmit = computed(() => {
            if (!form.sale_id || !form.dispatch_date || form.items.length === 0) return false;
            return form.items.every((i) => i.x_warehouse_id && i.quantity > 0);
        });

        const warehouseName = (xid) => warehouses.value.find((w) => w.xid === xid)?.name || xid;

        const searchSales = async (val) => {
            if (!val || val.length < 2) return;
            salesLoading.value = true;
            try {
                const res  = await window.axiosAdmin.get("sales", {
                    params: { search: val, order_type: "sales", limit: 20, offset: 0 },
                });
                salesList.value = res.data?.data || [];
            } catch {
                salesList.value = [];
            } finally {
                salesLoading.value = false;
            }
        };

        const onSaleSelected = async (xid) => {
            if (!xid) { form.items = []; saleInfo.value = null; return; }
            itemsLoading.value = true;
            try {
                const res    = await window.axiosAdmin.get("dispatches/sale-items", { params: { sale_id: xid } });
                saleInfo.value = res.data.sale;
                warehouses.value = res.data.warehouses;
                form.items = res.data.items.map((item) => ({
                    ...item,
                    original_quantity: item.quantity,
                    x_warehouse_id:    item.x_warehouse_id,
                }));
            } catch (e) {
                message.error(e?.response?.data?.message || "Failed to load sale items");
                form.items = [];
            } finally {
                itemsLoading.value = false;
            }
        };

        const submitDispatch = async () => {
            if (!canSubmit.value) {
                message.warning("Please select a warehouse for every item");
                return;
            }
            submitting.value = true;
            try {
                const payload = {
                    sale_id:       form.sale_id,
                    dispatch_date: dayjs(form.dispatch_date).format("YYYY-MM-DD"),
                    driver_name:   form.driver_name,
                    vehicle_no:    form.vehicle_no,
                    remarks:       form.remarks,
                    items: form.items.map((i) => ({
                        xid:             i.xid,
                        x_warehouse_id:  i.x_warehouse_id,
                        quantity:        i.quantity,
                    })),
                };
                const res = await window.axiosAdmin.post("dispatches", payload);
                message.success(res.data.message || "Dispatches created successfully");
                router.push({ name: "admin.stock.dispatches.index" });
            } catch (e) {
                message.error(e?.response?.data?.message || "Failed to create dispatch");
            } finally {
                submitting.value = false;
            }
        };

        return {
            form, salesList, salesLoading, itemsLoading, submitting,
            warehouses, saleInfo, itemColumns, groupedItems, canSubmit,
            appSetting, searchSales, onSaleSelected, submitDispatch, warehouseName,
        };
    },
});
</script>
