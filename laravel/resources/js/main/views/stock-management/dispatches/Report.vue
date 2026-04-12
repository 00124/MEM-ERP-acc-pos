<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Delivery Status Report" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.stock.dispatches.index' }">Dispatches</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Delivery Report</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="16" :lg="18">
                <a-row :gutter="[12, 12]">
                    <a-col :xs="24" :sm="8" :md="6">
                        <a-select
                            v-model:value="filters.status"
                            placeholder="All Statuses"
                            :allowClear="true"
                            style="width: 100%"
                        >
                            <a-select-option value="pending">Pending</a-select-option>
                            <a-select-option value="dispatched">Dispatched</a-select-option>
                            <a-select-option value="delivered">Delivered</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="10" :md="8">
                        <a-range-picker
                            v-model:value="filters.dateRange"
                            style="width: 100%"
                            :format="appSetting.date_format || 'DD-MM-YYYY'"
                        />
                    </a-col>
                    <a-col :xs="24" :sm="6" :md="4">
                        <a-button type="primary" @click="fetchReport" :loading="loading">
                            Generate Report
                        </a-button>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <!-- Summary Stats -->
        <a-row :gutter="[16, 16]" style="margin-bottom: 16px" v-if="summary">
            <a-col :xs="12" :sm="6" :md="6">
                <a-statistic title="Total Dispatches" :value="summary.total" />
            </a-col>
            <a-col :xs="12" :sm="6" :md="6">
                <a-statistic title="Pending" :value="summary.pending" :value-style="{ color: '#fa8c16' }" />
            </a-col>
            <a-col :xs="12" :sm="6" :md="6">
                <a-statistic title="Dispatched" :value="summary.dispatched" :value-style="{ color: '#1890ff' }" />
            </a-col>
            <a-col :xs="12" :sm="6" :md="6">
                <a-statistic title="Delivered" :value="summary.delivered" :value-style="{ color: '#52c41a' }" />
            </a-col>
        </a-row>

        <a-table
            :dataSource="reportData"
            :columns="columns"
            :loading="loading"
            :pagination="{ pageSize: 20 }"
            rowKey="xid"
            :scroll="{ x: 900 }"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'dispatch_number'">
                    <router-link :to="{ name: 'admin.stock.dispatches.gate-pass', params: { id: record.xid } }">
                        {{ record.dispatch_number }}
                    </router-link>
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
            </template>
        </a-table>
    </admin-page-table-content>
</template>

<script>
import { defineComponent, ref, reactive, computed, onMounted } from "vue";
import { useStore } from "vuex";
import { message } from "ant-design-vue";
import dayjs from "dayjs";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default defineComponent({
    name: "DispatchReport",
    components: { AdminPageHeader },
    setup() {
        const store      = useStore();
        const appSetting = computed(() => store.state.auth.appSetting);

        const reportData = ref([]);
        const summary    = ref(null);
        const loading    = ref(false);

        const filters = reactive({
            status:    undefined,
            dateRange: [],
        });

        const columns = [
            { title: "Dispatch #",   dataIndex: "dispatch_number", width: 120 },
            { title: "Sale Invoice", dataIndex: "sale",            width: 150 },
            { title: "Warehouse",    dataIndex: "warehouse",       width: 160 },
            { title: "Customer",     dataIndex: "customer",        width: 160 },
            { title: "Driver",       dataIndex: "driver_name",     width: 130 },
            { title: "Vehicle",      dataIndex: "vehicle_no",      width: 110 },
            { title: "Dispatch Date",dataIndex: "dispatch_date",   width: 120 },
            { title: "Status",       dataIndex: "status",          width: 110 },
        ];

        const formatDate  = (d) => d ? dayjs(d).format(appSetting.value?.date_format || "DD-MM-YYYY") : "-";
        const statusColor = (s) => ({ pending: "orange", dispatched: "blue", delivered: "green" }[s] || "default");

        const fetchReport = async () => {
            loading.value = true;
            try {
                const params = {};
                if (filters.status) params.status = filters.status;
                if (filters.dateRange?.length === 2) {
                    params.date_from = dayjs(filters.dateRange[0]).format("YYYY-MM-DD");
                    params.date_to   = dayjs(filters.dateRange[1]).format("YYYY-MM-DD");
                }
                const res  = await window.axiosAdmin.get("dispatches/report", { params });
                reportData.value = res.data.data;
                summary.value    = res.data.summary;
            } catch {
                message.error("Failed to generate report");
            } finally {
                loading.value = false;
            }
        };

        onMounted(fetchReport);

        return { reportData, summary, loading, filters, columns, appSetting, fetchReport, formatDate, statusColor };
    },
});
</script>
