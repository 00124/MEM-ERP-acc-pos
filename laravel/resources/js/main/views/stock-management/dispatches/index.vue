<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Dispatches" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t('menu.dashboard') }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Dispatches</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">
                <a-space>
                    <router-link :to="{ name: 'admin.stock.dispatches.create' }">
                        <a-button type="primary">
                            <PlusOutlined />
                            Create Dispatch
                        </a-button>
                    </router-link>
                </a-space>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col :xs="24" :sm="24" :md="8" :lg="6" :xl="6">
                        <a-input-search
                            style="width: 100%"
                            v-model:value="filters.search"
                            placeholder="Search dispatch / driver..."
                            @change="fetchDispatches"
                        />
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="6" :lg="5" :xl="4">
                        <a-select
                            v-model:value="filters.status"
                            placeholder="All Statuses"
                            :allowClear="true"
                            style="width: 100%"
                            @change="fetchDispatches"
                        >
                            <a-select-option value="pending">Pending</a-select-option>
                            <a-select-option value="dispatched">Dispatched</a-select-option>
                            <a-select-option value="delivered">Delivered</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="8" :lg="6" :xl="6">
                        <a-range-picker
                            v-model:value="filters.dateRange"
                            style="width: 100%"
                            @change="fetchDispatches"
                            :format="appSetting.date_format"
                        />
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <a-table
            :dataSource="dispatches"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            @change="handleTableChange"
            rowKey="xid"
            :scroll="{ x: 900 }"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'dispatch_number'">
                    <a @click="showDetails(record)">{{ record.dispatch_number }}</a>
                </template>
                <template v-else-if="column.dataIndex === 'sale'">
                    {{ record.sale?.invoice_number || '-' }}
                </template>
                <template v-else-if="column.dataIndex === 'warehouse'">
                    {{ record.warehouse?.name || '-' }}
                </template>
                <template v-else-if="column.dataIndex === 'customer'">
                    {{ record.customer?.name || '-' }}
                </template>
                <template v-else-if="column.dataIndex === 'dispatch_date'">
                    {{ formatDate(record.dispatch_date) }}
                </template>
                <template v-else-if="column.dataIndex === 'status'">
                    <a-tag :color="statusColor(record.status)">
                        {{ record.status?.toUpperCase() }}
                    </a-tag>
                </template>
                <template v-else-if="column.dataIndex === 'actions'">
                    <a-space>
                        <a-button size="small" @click="showDetails(record)">
                            <EyeOutlined /> View
                        </a-button>
                        <a-button size="small" @click="openGatePass(record)">
                            <PrinterOutlined /> Gate Pass
                        </a-button>
                        <a-dropdown v-if="record.status !== 'delivered'">
                            <a-button size="small">
                                Status <DownOutlined />
                            </a-button>
                            <template #overlay>
                                <a-menu>
                                    <a-menu-item
                                        v-for="s in availableStatuses(record.status)"
                                        :key="s.value"
                                        @click="changeStatus(record, s.value)"
                                    >
                                        {{ s.label }}
                                    </a-menu-item>
                                </a-menu>
                            </template>
                        </a-dropdown>
                    </a-space>
                </template>
            </template>
        </a-table>
    </admin-page-table-content>

    <!-- Detail Drawer -->
    <a-drawer
        v-model:visible="detailVisible"
        title="Dispatch Details"
        width="680"
        placement="right"
    >
        <div v-if="selectedDispatch">
            <a-descriptions :column="2" bordered size="small">
                <a-descriptions-item label="Dispatch #">{{ selectedDispatch.dispatch_number }}</a-descriptions-item>
                <a-descriptions-item label="Sale Invoice">{{ selectedDispatch.sale?.invoice_number }}</a-descriptions-item>
                <a-descriptions-item label="Warehouse">{{ selectedDispatch.warehouse?.name }}</a-descriptions-item>
                <a-descriptions-item label="Customer">{{ selectedDispatch.customer?.name || '-' }}</a-descriptions-item>
                <a-descriptions-item label="Date">{{ formatDate(selectedDispatch.dispatch_date) }}</a-descriptions-item>
                <a-descriptions-item label="Status">
                    <a-tag :color="statusColor(selectedDispatch.status)">{{ selectedDispatch.status?.toUpperCase() }}</a-tag>
                </a-descriptions-item>
                <a-descriptions-item label="Driver">{{ selectedDispatch.driver_name || '-' }}</a-descriptions-item>
                <a-descriptions-item label="Vehicle">{{ selectedDispatch.vehicle_no || '-' }}</a-descriptions-item>
                <a-descriptions-item label="Remarks" :span="2">{{ selectedDispatch.remarks || '-' }}</a-descriptions-item>
            </a-descriptions>

            <a-divider>Items</a-divider>
            <a-table
                :dataSource="selectedDispatch.items || []"
                :columns="itemColumns"
                :pagination="false"
                rowKey="xid"
                size="small"
            />
        </div>
    </a-drawer>
</template>

<script>
import { defineComponent, ref, reactive, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { PlusOutlined, EyeOutlined, PrinterOutlined, DownOutlined } from "@ant-design/icons-vue";
import { useStore } from "vuex";
import { message } from "ant-design-vue";
import dayjs from "dayjs";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default defineComponent({
    name: "DispatchIndex",
    components: { AdminPageHeader, PlusOutlined, EyeOutlined, PrinterOutlined, DownOutlined },
    setup() {
        const router     = useRouter();
        const store      = useStore();
        const appSetting = computed(() => store.state.auth.appSetting);

        const dispatches      = ref([]);
        const loading         = ref(false);
        const detailVisible   = ref(false);
        const selectedDispatch = ref(null);

        const pagination = reactive({ current: 1, pageSize: 15, total: 0 });

        const filters = reactive({
            search:    "",
            status:    undefined,
            dateRange: [],
        });

        const columns = [
            { title: "Dispatch #",   dataIndex: "dispatch_number", width: 120 },
            { title: "Sale Invoice", dataIndex: "sale",            width: 140 },
            { title: "Warehouse",    dataIndex: "warehouse",       width: 150 },
            { title: "Customer",     dataIndex: "customer",        width: 150 },
            { title: "Date",         dataIndex: "dispatch_date",   width: 120 },
            { title: "Driver",       dataIndex: "driver_name",     width: 120 },
            { title: "Vehicle",      dataIndex: "vehicle_no",      width: 100 },
            { title: "Status",       dataIndex: "status",          width: 110 },
            { title: "Actions",      dataIndex: "actions",         width: 200, fixed: "right" },
        ];

        const itemColumns = [
            { title: "Product",   dataIndex: ["product", "name"],     width: 180 },
            { title: "Warehouse", dataIndex: ["warehouse", "name"],   width: 140 },
            { title: "Quantity",  dataIndex: "quantity",              width: 100 },
        ];

        const formatDate = (d) => d ? dayjs(d).format(appSetting.value?.date_format || "DD-MM-YYYY") : "-";

        const statusColor = (s) => ({ pending: "orange", dispatched: "blue", delivered: "green" }[s] || "default");

        const availableStatuses = (current) => {
            const all = [
                { value: "pending",    label: "Mark Pending" },
                { value: "dispatched", label: "Mark Dispatched" },
                { value: "delivered",  label: "Mark Delivered" },
            ];
            return all.filter((s) => s.value !== current);
        };

        const fetchDispatches = async () => {
            loading.value = true;
            try {
                const params = {
                    offset: (pagination.current - 1) * pagination.pageSize,
                    limit:  pagination.pageSize,
                };
                if (filters.search)  params.search    = filters.search;
                if (filters.status)  params.status    = filters.status;
                if (filters.dateRange?.length === 2) {
                    params.date_from = dayjs(filters.dateRange[0]).format("YYYY-MM-DD");
                    params.date_to   = dayjs(filters.dateRange[1]).format("YYYY-MM-DD");
                }
                const res = await window.axiosAdmin.get("dispatches", { params });
                dispatches.value        = res.data.data;
                pagination.total        = res.data.meta?.paging?.total || 0;
            } catch (e) {
                message.error("Failed to load dispatches");
            } finally {
                loading.value = false;
            }
        };

        const handleTableChange = (pag) => {
            pagination.current  = pag.current;
            pagination.pageSize = pag.pageSize;
            fetchDispatches();
        };

        const showDetails = async (record) => {
            try {
                const res          = await window.axiosAdmin.get(`dispatches/${record.xid}`);
                selectedDispatch.value = res.data.data;
                detailVisible.value    = true;
            } catch {
                message.error("Failed to load details");
            }
        };

        const changeStatus = async (record, newStatus) => {
            try {
                await window.axiosAdmin.post(`dispatches/${record.xid}/status`, { status: newStatus });
                message.success("Status updated");
                fetchDispatches();
            } catch (e) {
                message.error(e?.response?.data?.message || "Failed to update status");
            }
        };

        const openGatePass = (record) => {
            router.push({ name: "admin.stock.dispatches.gate-pass", params: { id: record.xid } });
        };

        onMounted(fetchDispatches);

        return {
            dispatches, loading, pagination, filters, columns, itemColumns,
            detailVisible, selectedDispatch, appSetting,
            fetchDispatches, handleTableChange, showDetails, changeStatus, openGatePass,
            formatDate, statusColor, availableStatuses,
        };
    },
});
</script>
