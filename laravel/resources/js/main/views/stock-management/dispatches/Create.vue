<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header
                title="Create Dispatch"
                class="p-0"
                @back="() => $router.push({ name: 'admin.stock.dispatches.index' })"
            />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t('menu.dashboard') }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.stock.dispatches.index' }">
                        Dispatches
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Create</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card style="margin: 16px">
        <a-form layout="vertical">

            <!-- Top form row -->
            <a-row :gutter="[16, 0]">
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Select Sale Invoice" required>
                        <a-select
                            v-model:value="form.sale_id"
                            show-search
                            :filter-option="false"
                            placeholder="Type invoice number to search..."
                            :loading="salesLoading"
                            @search="searchSales"
                            @change="onSaleSelected"
                            style="width: 100%"
                            :not-found-content="salesLoading ? 'Searching...' : 'Type to search sales'"
                        >
                            <a-select-option
                                v-for="sale in salesList"
                                :key="sale.xid"
                                :value="sale.xid"
                            >
                                {{ sale.invoice_number }}
                                <span v-if="sale.user" style="color: #888; font-size: 11px">
                                    — {{ sale.user.name }}
                                </span>
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="12" :md="6">
                    <a-form-item label="Dispatch Date" required>
                        <a-date-picker
                            v-model:value="form.dispatch_date"
                            style="width: 100%"
                            :format="dateFormat"
                            :value-format="'YYYY-MM-DD'"
                        />
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="12" :md="5">
                    <a-form-item label="Driver Name">
                        <a-input v-model:value="form.driver_name" placeholder="Driver name" />
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="12" :md="5">
                    <a-form-item label="Vehicle No">
                        <a-input v-model:value="form.vehicle_no" placeholder="e.g. ABC-1234" />
                    </a-form-item>
                </a-col>

                <a-col :xs="24">
                    <a-form-item label="Remarks">
                        <a-textarea v-model:value="form.remarks" :rows="2" placeholder="Optional remarks" />
                    </a-form-item>
                </a-col>
            </a-row>

            <!-- Sale info banner -->
            <a-alert
                v-if="saleInfo"
                type="info"
                style="margin-bottom: 16px"
                show-icon
            >
                <template #message>
                    <strong>Customer:</strong> {{ saleInfo.customer_name }}
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <strong>Salesman:</strong> {{ saleInfo.salesman_name }}
                </template>
            </a-alert>

            <!-- Loading state -->
            <div v-if="itemsLoading" style="text-align: center; padding: 40px">
                <a-spin size="large" />
                <div style="margin-top: 8px; color: #888">Loading sale items...</div>
            </div>

            <!-- Items table - only shown after a sale is selected -->
            <template v-else-if="form.items.length > 0">
                <a-divider style="font-size: 14px; font-weight: 600">
                    Assign Warehouse Per Product
                </a-divider>

                <a-alert
                    type="warning"
                    show-icon
                    style="margin-bottom: 12px"
                    message="Items with the same warehouse will be grouped into one Dispatch. Different warehouses create separate Dispatches automatically."
                />

                <a-table
                    :dataSource="form.items"
                    :columns="itemColumns"
                    :pagination="false"
                    rowKey="xid"
                    size="small"
                    :scroll="{ x: 650 }"
                >
                    <template #bodyCell="{ column, record, index }">
                        <template v-if="column.dataIndex === 'seq'">
                            {{ index + 1 }}
                        </template>
                        <template v-else-if="column.dataIndex === 'product_name'">
                            <div><strong>{{ record.product_name }}</strong></div>
                            <div style="font-size: 11px; color: #888">{{ record.item_code }}</div>
                        </template>
                        <template v-else-if="column.dataIndex === 'quantity'">
                            <a-input-number
                                v-model:value="record.quantity"
                                :min="0.01"
                                :max="record.original_quantity"
                                :step="1"
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
                                style="width: 200px"
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

                <!-- Dispatch preview grouped by warehouse -->
                <a-divider style="font-size: 13px; font-weight: 600; margin-top: 20px">
                    Dispatch Preview ({{ Object.keys(groupedItems).length }} dispatch(es) will be created)
                </a-divider>

                <a-row :gutter="[16, 12]" style="margin-bottom: 20px">
                    <a-col
                        v-for="(groupItems, warehouseXid) in groupedItems"
                        :key="warehouseXid"
                        :xs="24" :sm="12" :md="8"
                    >
                        <a-card size="small" :title="warehouseName(warehouseXid)" style="border: 1px solid #1890ff; border-radius: 6px">
                            <div
                                v-for="item in groupItems"
                                :key="item.xid"
                                style="font-size: 12px; margin-bottom: 4px"
                            >
                                <strong>{{ item.product_name }}</strong>
                                &times; {{ item.quantity }}
                            </div>
                        </a-card>
                    </a-col>
                    <a-col v-if="Object.keys(groupedItems).length === 0" :xs="24">
                        <a-alert type="warning" message="Please select a warehouse for all items to proceed." />
                    </a-col>
                </a-row>

                <a-row justify="end">
                    <a-space>
                        <a-button @click="$router.push({ name: 'admin.stock.dispatches.index' })">
                            Cancel
                        </a-button>
                        <a-button
                            type="primary"
                            size="large"
                            :loading="submitting"
                            :disabled="!canSubmit"
                            @click="submitDispatch"
                        >
                            <SendOutlined />
                            Create {{ Object.keys(groupedItems).length }} Dispatch(es)
                        </a-button>
                    </a-space>
                </a-row>
            </template>

            <!-- Empty state - no sale selected yet -->
            <a-empty
                v-else-if="form.sale_id && !itemsLoading"
                description="No items found for this sale"
                style="margin: 30px 0"
            />

            <div v-else style="text-align: center; padding: 40px; color: #aaa">
                <CarOutlined style="font-size: 48px; margin-bottom: 12px" />
                <div>Search and select a sale invoice above to load items for dispatch</div>
            </div>

        </a-form>
    </a-card>
</template>

<script>
import { defineComponent, ref, reactive, computed } from "vue";
import { useRouter } from "vue-router";
import { useStore } from "vuex";
import { message } from "ant-design-vue";
import { SendOutlined, CarOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default defineComponent({
    name: "DispatchCreate",
    components: { AdminPageHeader, SendOutlined, CarOutlined },
    setup() {
        const router     = useRouter();
        const store      = useStore();
        const appSetting = computed(() => store.state.auth.appSetting);
        const dateFormat = computed(() => appSetting.value?.date_format || "DD-MM-YYYY");

        const salesList    = ref([]);
        const salesLoading = ref(false);
        const itemsLoading = ref(false);
        const submitting   = ref(false);
        const warehouses   = ref([]);
        const saleInfo     = ref(null);

        const form = reactive({
            sale_id:       undefined,
            dispatch_date: null,
            driver_name:   "",
            vehicle_no:    "",
            remarks:       "",
            items:         [],
        });

        const itemColumns = [
            { title: "#",         dataIndex: "seq",            width: 50  },
            { title: "Product",   dataIndex: "product_name",   width: 200 },
            { title: "Quantity",  dataIndex: "quantity",       width: 160 },
            { title: "Warehouse", dataIndex: "x_warehouse_id", width: 220 },
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

        const warehouseName = (xid) =>
            warehouses.value.find((w) => w.xid === xid)?.name || xid;

        // Search sales by invoice number
        const searchSales = async (val) => {
            if (!val || val.trim().length < 1) return;
            salesLoading.value = true;
            try {
                const res = await window.axiosAdmin.get("sales", {
                    params: {
                        searchString: val.trim(),
                        searchColumn: "invoice_number",
                        limit:  20,
                        offset: 0,
                    },
                });
                salesList.value = res.data?.data || [];
            } catch {
                salesList.value = [];
            } finally {
                salesLoading.value = false;
            }
        };

        // When a sale is chosen, load its items
        const onSaleSelected = async (xid) => {
            if (!xid) {
                form.items = [];
                saleInfo.value = null;
                warehouses.value = [];
                return;
            }
            itemsLoading.value = true;
            try {
                const res = await window.axiosAdmin.get("dispatches/sale-items", {
                    params: { sale_id: xid },
                });
                saleInfo.value   = res.data.sale;
                warehouses.value = res.data.warehouses;
                form.items       = res.data.items.map((item) => ({
                    ...item,
                    original_quantity: item.quantity,
                }));
            } catch (e) {
                message.error(
                    e?.response?.data?.message || "Failed to load sale items. Please try again."
                );
                form.items = [];
            } finally {
                itemsLoading.value = false;
            }
        };

        const submitDispatch = async () => {
            if (!canSubmit.value) {
                message.warning("Please select a warehouse for every item and fill all required fields.");
                return;
            }
            submitting.value = true;
            try {
                const payload = {
                    sale_id:       form.sale_id,
                    dispatch_date: form.dispatch_date,
                    driver_name:   form.driver_name  || null,
                    vehicle_no:    form.vehicle_no   || null,
                    remarks:       form.remarks      || null,
                    items: form.items.map((i) => ({
                        xid:            i.xid,
                        x_warehouse_id: i.x_warehouse_id,
                        quantity:       i.quantity,
                    })),
                };
                const res = await window.axiosAdmin.post("dispatches", payload);
                message.success(res.data.message || "Dispatch(es) created successfully!");
                router.push({ name: "admin.stock.dispatches.index" });
            } catch (e) {
                message.error(e?.response?.data?.message || "Failed to create dispatch. Please check stock availability.");
            } finally {
                submitting.value = false;
            }
        };

        return {
            form, salesList, salesLoading, itemsLoading, submitting,
            warehouses, saleInfo, itemColumns, groupedItems, canSubmit,
            appSetting, dateFormat,
            searchSales, onSaleSelected, submitDispatch, warehouseName,
        };
    },
});
</script>
