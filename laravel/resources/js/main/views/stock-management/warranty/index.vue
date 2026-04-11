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
                    <a-button type="primary" @click="openAdd">
                        <template #icon><PlusOutlined /></template>
                        Log Warranty / Damage
                    </a-button>
                    <a-button @click="$router.push({ name: 'admin.stock.warranty.report' })">
                        <template #icon><BarChartOutlined /></template>
                        View Report
                    </a-button>
                    <a-button
                        v-if="selectedRowKeys.length > 0"
                        danger type="primary"
                        @click="bulkDelete"
                    >
                        <template #icon><DeleteOutlined /></template>
                        Delete Selected ({{ selectedRowKeys.length }})
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
                            @change="reFetch"
                        >
                            <a-select-option
                                v-for="wt in warrantyTypes"
                                :key="wt.key"
                                :value="wt.key"
                            >{{ wt.label }}</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="12">
                        <a-select
                            v-model:value="filters.status"
                            placeholder="Filter by status"
                            allow-clear
                            style="width:100%"
                            @change="reFetch"
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
            :visible="addEditVisible"
            :editRecord="editRecord"
            @saved="onSaved"
            @closed="addEditVisible = false"
        />

        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :row-selection="{
                            selectedRowKeys: selectedRowKeys,
                            onChange: (keys) => { selectedRowKeys = keys; },
                            getCheckboxProps: (record) => ({
                                disabled: record.status !== 'pending',
                                name: record.xid,
                            }),
                        }"
                        :columns="columns"
                        :row-key="(record) => record.xid"
                        :data-source="tableData"
                        :pagination="pagination"
                        :loading="tableLoading"
                        @change="handleTableChange"
                        bordered
                        size="middle"
                        :scroll="{ x: 900 }"
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
                                        @click="openEdit(record)"
                                    >
                                        <template #icon><EditOutlined /></template>
                                    </a-button>
                                    <a-button
                                        v-if="record.status === 'pending'"
                                        size="small"
                                        danger
                                        @click="handleDelete(record)"
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
import { defineComponent, ref, reactive, onMounted } from "vue";
import {
    PlusOutlined, DeleteOutlined, EditOutlined,
    CheckOutlined, SwapOutlined, BarChartOutlined,
} from "@ant-design/icons-vue";
import { message, Modal } from "ant-design-vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import AddEdit from "./AddEdit.vue";

const warrantyTypes = [
    { key: "damage",           label: "Damaged",          color: "#FF4D4F" },
    { key: "expired",          label: "Expired Warranty", color: "#FA8B0C" },
    { key: "claimable",        label: "Claimable",        color: "#1677ff" },
    { key: "return_to_vendor", label: "Return to Vendor", color: "#722ed1" },
];

const kpiCards = [
    { key: "damage",           label: "Damaged Stock",    color: "#FF4D4F" },
    { key: "expired",          label: "Expired Warranty", color: "#FA8B0C" },
    { key: "claimable",        label: "Under Claim",      color: "#1677ff" },
    { key: "return_to_vendor", label: "Return to Vendor", color: "#722ed1" },
];

const columns = [
    { title: "Product",  dataIndex: "product_id",    width: 200 },
    { title: "Qty",      dataIndex: "quantity",       align: "right", width: 70 },
    { title: "Type",     dataIndex: "warranty_type",  width: 150 },
    { title: "Status",   dataIndex: "status",         width: 110 },
    { title: "Remarks",  dataIndex: "remarks",        ellipsis: true },
    { title: "Date",     dataIndex: "created_at",     width: 105 },
    { title: "Actions",  dataIndex: "action",         width: 230, fixed: "right" },
];

export default defineComponent({
    components: {
        PlusOutlined, DeleteOutlined, EditOutlined,
        CheckOutlined, SwapOutlined, BarChartOutlined,
        AddEdit,
        AdminPageHeader,
    },
    setup() {
        const addEditVisible  = ref(false);
        const editRecord      = ref(null);
        const actionLoading   = reactive({});
        const summary         = ref({});
        const tableData       = ref([]);
        const tableLoading    = ref(false);
        const selectedRowKeys = ref([]);
        const filters         = reactive({ warranty_type: undefined, status: undefined });
        const pagination      = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true });

        const fetchList = async (page = 1) => {
            tableLoading.value = true;
            try {
                const params = {
                    mode: "warranty",
                    fields: "xid,product_id,x_product_id,product{id,xid,name,item_code},quantity,warranty_type,status,remarks,created_at",
                    offset: (page - 1) * pagination.pageSize,
                    limit: pagination.pageSize,
                    order: "id desc",
                };
                if (filters.warranty_type) params.warranty_type = filters.warranty_type;
                if (filters.status)        params.status        = filters.status;

                const res = await axiosAdmin.get("stock-adjustments", { params });
                tableData.value     = res.data ?? [];
                pagination.total    = res.meta?.paging?.total ?? tableData.value.length;
                pagination.current  = page;
            } catch {
                message.error("Failed to load warranty records");
            } finally {
                tableLoading.value = false;
            }
        };

        const fetchSummary = async () => {
            try {
                const res = await axiosAdmin.get("stock-adjustments-warranty/report");
                summary.value = res.data?.summary ?? {};
            } catch {}
        };

        const reFetch = () => {
            selectedRowKeys.value = [];
            fetchList(1);
        };

        const handleTableChange = (pag) => {
            pagination.pageSize = pag.pageSize;
            fetchList(pag.current);
        };

        const openAdd  = () => { editRecord.value = null; addEditVisible.value = true; };
        const openEdit = (r)  => { editRecord.value = r;  addEditVisible.value = true; };

        const onSaved = () => {
            addEditVisible.value = false;
            fetchList(pagination.current);
            fetchSummary();
        };

        const handleApprove = async (record) => {
            actionLoading[record.xid] = true;
            try {
                await axiosAdmin.post(`stock-adjustments/${record.xid}/approve`);
                message.success("Approved — stock reduced.");
                fetchList(pagination.current);
                fetchSummary();
            } catch (e) {
                message.error(e?.response?.data?.message ?? "Approval failed");
            } finally { actionLoading[record.xid] = false; }
        };

        const handleReplace = async (record) => {
            actionLoading[record.xid] = true;
            try {
                await axiosAdmin.post(`stock-adjustments/${record.xid}/replace`);
                message.success("Replacement received — stock restored.");
                fetchList(pagination.current);
                fetchSummary();
            } catch (e) {
                message.error(e?.response?.data?.message ?? "Action failed");
            } finally { actionLoading[record.xid] = false; }
        };

        const handleDelete = async (record) => {
            Modal.confirm({
                title: "Delete this record?",
                okType: "danger",
                okText: "Yes, Delete",
                cancelText: "Cancel",
                centered: true,
                onOk: async () => {
                    try {
                        await axiosAdmin.delete(`stock-adjustments/${record.xid}`);
                        message.success("Record deleted.");
                        fetchList(pagination.current);
                        fetchSummary();
                    } catch (e) {
                        message.error(e?.response?.data?.message ?? "Delete failed");
                    }
                },
            });
        };

        const bulkDelete = () => {
            Modal.confirm({
                title: `Delete ${selectedRowKeys.value.length} records?`,
                content: "Only pending records will be deleted.",
                okType: "danger",
                okText: "Yes, Delete",
                cancelText: "Cancel",
                centered: true,
                onOk: async () => {
                    for (const xid of selectedRowKeys.value) {
                        try { await axiosAdmin.delete(`stock-adjustments/${xid}`); } catch {}
                    }
                    selectedRowKeys.value = [];
                    fetchList(1);
                    fetchSummary();
                },
            });
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

        onMounted(() => {
            fetchList(1);
            fetchSummary();
        });

        return {
            columns, kpiCards, warrantyTypes, filters, summary,
            tableData, tableLoading, pagination, selectedRowKeys, actionLoading,
            addEditVisible, editRecord,
            reFetch, handleTableChange,
            openAdd, openEdit, onSaved,
            handleApprove, handleReplace, handleDelete, bulkDelete,
            typeLabel, typeColor, statusBadge, statusLabel,
        };
    },
});
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
