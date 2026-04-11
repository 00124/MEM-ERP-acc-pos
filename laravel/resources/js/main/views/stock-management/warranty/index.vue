<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Warranty & Damage Management" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Warranty &amp; Damage</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- KPI Summary Cards -->
    <div class="wm-kpi-row">
        <div v-for="card in kpiCards" :key="card.key" class="wm-kpi-card" :style="{ borderLeftColor: card.color }">
            <div class="wm-kpi-body">
                <div class="wm-kpi-label">{{ card.label }}</div>
                <div class="wm-kpi-value" :style="{ color: card.color }">
                    {{ summary[card.key] ? summary[card.key].total : 0 }} records
                </div>
                <div class="wm-kpi-sub">
                    Pending: {{ summary[card.key] ? summary[card.key].pending_qty : 0 }}
                    &nbsp;|&nbsp;
                    Approved: {{ summary[card.key] ? summary[card.key].approved_qty : 0 }}
                </div>
            </div>
        </div>
    </div>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="14" :lg="14">
                <a-space wrap>
                    <a-button type="primary" @click="addItem">
                        <template #icon><PlusOutlined /></template>
                        Log Warranty / Damage
                    </a-button>
                    <a-button @click="$router.push({ name: 'admin.stock.warranty.report' })">
                        <template #icon><BarChartOutlined /></template>
                        View Report
                    </a-button>
                    <a-button
                        v-if="table.selectedRowKeys.length > 0"
                        danger type="primary"
                        @click="showSelectedDeleteConfirm"
                    >
                        <template #icon><DeleteOutlined /></template>
                        Delete Selected
                    </a-button>
                </a-space>
            </a-col>
            <a-col :xs="24" :sm="24" :md="10" :lg="10">
                <a-row :gutter="[8, 8]" justify="end">
                    <a-col :xs="24" :sm="12" :md="12">
                        <a-select
                            v-model:value="filters.warranty_type"
                            placeholder="Filter by type"
                            allow-clear
                            style="width:100%"
                            @change="reFetchDatatable"
                        >
                            <a-select-option
                                v-for="t in warrantyTypes"
                                :key="t.key"
                                :value="t.key"
                            >{{ t.label }}</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="12">
                        <a-select
                            v-model:value="filters.status"
                            placeholder="Filter by status"
                            allow-clear
                            style="width:100%"
                            @change="reFetchDatatable"
                        >
                            <a-select-option value="pending">Pending</a-select-option>
                            <a-select-option value="approved">Approved</a-select-option>
                            <a-select-option value="completed">Completed</a-select-option>
                        </a-select>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <AddEdit
            :addEditType="addEditType"
            :visible="addEditVisible"
            :url="addEditUrl"
            @addEditSuccess="addEditSuccess"
            @closed="onCloseAddEdit"
            :formData="formData"
            :data="viewData"
            :pageTitle="pageTitle"
            :successMessage="successMessage"
        />

        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :row-selection="{
                            selectedRowKeys: table.selectedRowKeys,
                            onChange: onRowSelectChange,
                            getCheckboxProps: (record) => ({
                                disabled: record.status !== 'pending',
                                name: record.xid,
                            }),
                        }"
                        :columns="columns"
                        :row-key="(record) => record.xid"
                        :data-source="table.data"
                        :pagination="table.pagination"
                        :loading="table.loading"
                        @change="handleTableChange"
                        bordered
                        size="middle"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'product_id'">
                                <div style="font-weight:600">
                                    {{ record.product ? record.product.name : '' }}
                                </div>
                                <div style="font-size:11px;color:#ADB4D2">
                                    {{ record.product ? record.product.item_code : '' }}
                                </div>
                            </template>

                            <template v-if="column.dataIndex === 'warranty_type'">
                                <a-tag :color="typeColor(record.warranty_type)">
                                    {{ typeLabel(record.warranty_type) }}
                                </a-tag>
                            </template>

                            <template v-if="column.dataIndex === 'status'">
                                <a-badge
                                    :status="statusBadge(record.status)"
                                    :text="statusLabel(record.status)"
                                />
                            </template>

                            <template v-if="column.dataIndex === 'action'">
                                <a-space>
                                    <a-button
                                        v-if="record.status === 'pending'"
                                        type="primary"
                                        size="small"
                                        :loading="actionLoading[record.xid]"
                                        @click="handleApprove(record)"
                                    >
                                        <template #icon><CheckOutlined /></template>
                                        Approve
                                    </a-button>
                                    <a-button
                                        v-if="record.status === 'approved' && (record.warranty_type === 'claimable' || record.warranty_type === 'return_to_vendor')"
                                        size="small"
                                        style="background:#20C997;color:#fff;border-color:#0CAB7C"
                                        :loading="actionLoading[record.xid]"
                                        @click="handleReplace(record)"
                                    >
                                        <template #icon><SwapOutlined /></template>
                                        Replaced
                                    </a-button>
                                    <a-button
                                        v-if="record.status === 'pending'"
                                        size="small"
                                        @click="editItem(record)"
                                    >
                                        <template #icon><EditOutlined /></template>
                                    </a-button>
                                    <a-button
                                        v-if="record.status === 'pending'"
                                        size="small"
                                        danger
                                        @click="showDeleteConfirm(record.xid)"
                                    >
                                        <template #icon><DeleteOutlined /></template>
                                    </a-button>
                                </a-space>
                            </template>
                        </template>
                    </a-table>
                </div>
            </a-col>
        </a-row>
    </admin-page-table-content>
</template>

<script>
import { onMounted, ref, reactive } from "vue";
import {
    PlusOutlined, DeleteOutlined, EditOutlined,
    CheckOutlined, SwapOutlined, BarChartOutlined,
} from "@ant-design/icons-vue";
import { message } from "ant-design-vue";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import AddEdit from "./AddEdit.vue";

const warrantyTypes = [
    { key: "damage",           label: "Damaged",          color: "#FF4D4F" },
    { key: "expired",          label: "Expired Warranty", color: "#FA8B0C" },
    { key: "claimable",        label: "Claimable",        color: "#1677ff" },
    { key: "return_to_vendor", label: "Return to Vendor", color: "#722ed1" },
];

const addEditUrl = "stock-adjustments";

const initData = {
    product_id:    "",
    quantity:      1,
    warranty_type: undefined,
    remarks:       "",
};

const columns = [
    { title: "Product",  dataIndex: "product_id",    width: 200 },
    { title: "Qty",      dataIndex: "quantity",       align: "right", width: 70 },
    { title: "Type",     dataIndex: "warranty_type",  width: 150 },
    { title: "Status",   dataIndex: "status",         width: 110 },
    { title: "Remarks",  dataIndex: "remarks",        ellipsis: true },
    { title: "Date",     dataIndex: "created_at",     width: 105 },
    { title: "Actions",  dataIndex: "action",         width: 220, fixed: "right" },
];

const kpiCards = [
    { key: "damage",           label: "Damaged Stock",    color: "#FF4D4F" },
    { key: "expired",          label: "Expired Warranty", color: "#FA8B0C" },
    { key: "claimable",        label: "Under Claim",      color: "#1677ff" },
    { key: "return_to_vendor", label: "Return to Vendor", color: "#722ed1" },
];

export default {
    components: {
        PlusOutlined, DeleteOutlined, EditOutlined,
        CheckOutlined, SwapOutlined, BarChartOutlined,
        AddEdit,
        AdminPageHeader,
    },
    setup() {
        const crudVariables = crud();
        const { selectedWarehouse } = common();
        const summary = ref({});
        const actionLoading = reactive({});
        const filters = reactive({ warranty_type: undefined, status: undefined });

        const hashableColumns = ["product_id"];

        onMounted(() => {
            crudVariables.crudUrl.value      = addEditUrl;
            crudVariables.langKey.value      = "stock_adjustment";
            crudVariables.initData.value     = { ...initData };
            crudVariables.formData.value     = { ...initData };
            crudVariables.hashableColumns.value = [...hashableColumns];

            reFetchDatatable();
            fetchSummary();
        });

        const reFetchDatatable = () => {
            const urlFilters = { ...filters };
            // Append mode=warranty to the URL to filter only warranty records
            crudVariables.tableUrl.value = {
                url: `stock-adjustments?mode=warranty&fields=xid,product_id,x_product_id,product{id,xid,name,item_code},quantity,warranty_type,status,remarks,created_at`,
                filters: urlFilters,
            };
            crudVariables.fetch({ page: 1 });
        };

        const fetchSummary = () => {
            axiosAdmin
                .get("stock-adjustments-warranty/report")
                .then((res) => {
                    summary.value = res.data ? res.data.summary || {} : {};
                })
                .catch(() => {});
        };

        const handleApprove = (record) => {
            actionLoading[record.xid] = true;
            axiosAdmin
                .post(`stock-adjustments/${record.xid}/approve`)
                .then(() => {
                    message.success("Approved — stock reduced.");
                    reFetchDatatable();
                    fetchSummary();
                })
                .catch((e) => {
                    message.error(
                        e && e.response && e.response.data && e.response.data.message
                            ? e.response.data.message
                            : "Approval failed"
                    );
                })
                .finally(() => { actionLoading[record.xid] = false; });
        };

        const handleReplace = (record) => {
            actionLoading[record.xid] = true;
            axiosAdmin
                .post(`stock-adjustments/${record.xid}/replace`)
                .then(() => {
                    message.success("Replacement received — stock restored.");
                    reFetchDatatable();
                    fetchSummary();
                })
                .catch((e) => {
                    message.error(
                        e && e.response && e.response.data && e.response.data.message
                            ? e.response.data.message
                            : "Action failed"
                    );
                })
                .finally(() => { actionLoading[record.xid] = false; });
        };

        const typeLabel = (t) => {
            const found = warrantyTypes.find((w) => w.key === t);
            return found ? found.label : t;
        };
        const typeColor = (t) => {
            const found = warrantyTypes.find((w) => w.key === t);
            return found ? found.color : "#999";
        };
        const statusBadge = (s) =>
            s === "pending" ? "warning" : s === "approved" ? "processing" : "success";
        const statusLabel = (s) =>
            s === "pending" ? "Pending" : s === "approved" ? "Approved" : "Completed";

        return {
            columns,
            kpiCards,
            warrantyTypes,
            filters,
            summary,
            actionLoading,
            selectedWarehouse,
            ...crudVariables,
            reFetchDatatable,
            fetchSummary,
            handleApprove,
            handleReplace,
            typeLabel,
            typeColor,
            statusBadge,
            statusLabel,
            addEditUrl,
        };
    },
};
</script>

<style scoped>
.wm-kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    padding: 16px 24px 0;
}
@media (max-width: 900px) { .wm-kpi-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px)  { .wm-kpi-row { grid-template-columns: 1fr; } }

.wm-kpi-card {
    background: #fff;
    border-radius: 10px;
    border-left: 4px solid #1677ff;
    padding: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.wm-kpi-label {
    font-size: 11px;
    font-weight: 700;
    color: #ADB4D2;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.wm-kpi-value {
    font-size: 20px;
    font-weight: 700;
    color: #272B41;
    margin: 4px 0 2px;
}
.wm-kpi-sub {
    font-size: 11px;
    color: #5A5F7D;
}
</style>
