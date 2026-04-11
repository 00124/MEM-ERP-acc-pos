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
                <a-breadcrumb-item>Warranty & Damage</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- KPI Cards -->
    <div class="wm-kpi-row">
        <div v-for="card in kpiCards" :key="card.key" class="wm-kpi-card" :style="`--accent:${card.color}`">
            <div class="wm-kpi-icon"><component :is="card.icon" /></div>
            <div class="wm-kpi-body">
                <div class="wm-kpi-label">{{ card.label }}</div>
                <div class="wm-kpi-value">{{ summary[card.key]?.total ?? 0 }} records</div>
                <div class="wm-kpi-sub">
                    Pending: {{ summary[card.key]?.pending_qty ?? 0 }} &nbsp;|&nbsp;
                    Approved: {{ summary[card.key]?.approved_qty ?? 0 }}
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Actions -->
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
                        v-if="table.selectedRowKeys.length > 0"
                        danger type="primary"
                        @click="bulkDelete"
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
                            allow-clear style="width:100%"
                            @change="reFetch"
                        >
                            <a-select-option v-for="t in warrantyTypes" :key="t.key" :value="t.key">
                                {{ t.label }}
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="12">
                        <a-select
                            v-model:value="filters.status"
                            placeholder="Filter by status"
                            allow-clear style="width:100%"
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
        <!-- Add / Edit Drawer -->
        <AddEditWarranty
            :visible="addEditVisible"
            :editRecord="editRecord"
            @saved="onSaved"
            @closed="addEditVisible = false"
        />

        <a-table
            :row-selection="{
                selectedRowKeys: table.selectedRowKeys,
                onChange: onRowSelectChange,
                getCheckboxProps: (r) => ({ disabled: r.status !== 'pending', name: r.xid }),
            }"
            :columns="columns"
            row-key="xid"
            :data-source="table.data"
            :pagination="table.pagination"
            :loading="table.loading"
            @change="handleTableChange"
            bordered size="middle"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'product_id'">
                    <div style="font-weight:600">{{ record.product?.name }}</div>
                    <div style="font-size:11px;color:#ADB4D2">{{ record.product?.item_code }}</div>
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
                            type="primary" size="small"
                            @click="handleApprove(record)"
                            :loading="actionLoading[record.xid]"
                        >
                            <template #icon><CheckOutlined /></template>
                            Approve
                        </a-button>
                        <a-button
                            v-if="record.status === 'approved' && (record.warranty_type === 'claimable' || record.warranty_type === 'return_to_vendor')"
                            size="small" style="background:#20C997;color:#fff;border-color:#0CAB7C"
                            @click="handleReplace(record)"
                            :loading="actionLoading[record.xid]"
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
                        <a-popconfirm
                            v-if="record.status === 'pending'"
                            title="Delete this record?"
                            ok-text="Yes" cancel-text="No"
                            @confirm="handleDelete(record)"
                        >
                            <a-button size="small" danger>
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-popconfirm>
                    </a-space>
                </template>
            </template>
        </a-table>
    </admin-page-table-content>
</template>

<script>
import { defineComponent, ref, reactive, onMounted, computed } from 'vue';
import {
    PlusOutlined, DeleteOutlined, EditOutlined, CheckOutlined,
    SwapOutlined, BarChartOutlined,
    WarningOutlined, ExperimentOutlined, SafetyOutlined, SendOutlined,
} from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import AddEditWarranty from './AddEdit.vue';

const axiosAdmin = window.axiosAdmin;

const warrantyTypes = [
    { key: 'damage',           label: 'Damaged',            color: '#FF4D4F' },
    { key: 'expired',          label: 'Expired Warranty',   color: '#FA8B0C' },
    { key: 'claimable',        label: 'Claimable',          color: '#1677ff' },
    { key: 'return_to_vendor', label: 'Return to Vendor',   color: '#722ed1' },
];

export default defineComponent({
    components: {
        AdminPageHeader, AddEditWarranty,
        PlusOutlined, DeleteOutlined, EditOutlined, CheckOutlined,
        SwapOutlined, BarChartOutlined,
        WarningOutlined, ExperimentOutlined, SafetyOutlined, SendOutlined,
    },
    setup() {
        const addEditVisible = ref(false);
        const editRecord     = ref(null);
        const actionLoading  = reactive({});
        const summary        = ref({});

        const filters = reactive({ warranty_type: undefined, status: undefined });

        const table = reactive({
            data: [],
            loading: false,
            pagination: { current: 1, pageSize: 15, total: 0, showSizeChanger: true },
            selectedRowKeys: [],
        });

        const columns = [
            { title: 'Product', dataIndex: 'product_id', width: 200 },
            { title: 'Qty', dataIndex: 'quantity', align: 'right', width: 70 },
            { title: 'Type', dataIndex: 'warranty_type', width: 140 },
            { title: 'Status', dataIndex: 'status', width: 110 },
            { title: 'Remarks', dataIndex: 'remarks', ellipsis: true },
            { title: 'Date', dataIndex: 'created_at', width: 100 },
            { title: 'Actions', dataIndex: 'action', width: 210, fixed: 'right' },
        ];

        const kpiCards = [
            { key: 'damage',           label: 'Damaged Stock',       color: '#FF4D4F', icon: WarningOutlined },
            { key: 'expired',          label: 'Expired Warranty',    color: '#FA8B0C', icon: ExperimentOutlined },
            { key: 'claimable',        label: 'Under Claim',         color: '#1677ff', icon: SafetyOutlined },
            { key: 'return_to_vendor', label: 'Return to Vendor',    color: '#722ed1', icon: SendOutlined },
        ];

        const fetchSummary = async () => {
            try {
                const res = await axiosAdmin.get('stock-adjustments-warranty/report');
                summary.value = res.data?.summary ?? {};
            } catch {}
        };

        const fetch = async (page = 1) => {
            table.loading = true;
            try {
                const params = {
                    mode: 'warranty',
                    page,
                    per_page: table.pagination.pageSize,
                    fields: 'xid,product_id,x_product_id,product{id,xid,name,item_code},quantity,warranty_type,status,remarks,notes,created_at',
                };
                if (filters.warranty_type) params.warranty_type = filters.warranty_type;
                if (filters.status)        params.status        = filters.status;

                const res = await axiosAdmin.get('stock-adjustments', { params });
                const d   = res.data;
                table.data = d.data ?? [];
                table.pagination.total   = d.total ?? 0;
                table.pagination.current = d.current_page ?? page;
            } catch { message.error('Failed to load records'); }
            finally { table.loading = false; }
        };

        const reFetch = () => fetch(1);

        const handleTableChange = (pag) => {
            table.pagination.current  = pag.current;
            table.pagination.pageSize = pag.pageSize;
            fetch(pag.current);
        };

        const onRowSelectChange = (keys) => { table.selectedRowKeys = keys; };

        const openAdd  = () => { editRecord.value = null; addEditVisible.value = true; };
        const openEdit = (r) => { editRecord.value = r;    addEditVisible.value = true; };
        const onSaved  = () => { addEditVisible.value = false; reFetch(); fetchSummary(); };

        const handleApprove = async (record) => {
            actionLoading[record.xid] = true;
            try {
                await axiosAdmin.post(`stock-adjustments/${record.xid}/approve`);
                message.success('Approved — stock reduced.');
                reFetch(); fetchSummary();
            } catch (e) {
                message.error(e?.response?.data?.message ?? 'Approval failed');
            } finally { actionLoading[record.xid] = false; }
        };

        const handleReplace = async (record) => {
            actionLoading[record.xid] = true;
            try {
                await axiosAdmin.post(`stock-adjustments/${record.xid}/replace`);
                message.success('Replacement received — stock restored.');
                reFetch(); fetchSummary();
            } catch (e) {
                message.error(e?.response?.data?.message ?? 'Action failed');
            } finally { actionLoading[record.xid] = false; }
        };

        const handleDelete = async (record) => {
            try {
                await axiosAdmin.delete(`stock-adjustments/${record.xid}`);
                message.success('Record deleted.');
                reFetch(); fetchSummary();
            } catch (e) {
                message.error(e?.response?.data?.message ?? 'Delete failed');
            }
        };

        const bulkDelete = () => {
            Modal.confirm({
                title: `Delete ${table.selectedRowKeys.length} records?`,
                okType: 'danger',
                onOk: async () => {
                    for (const xid of table.selectedRowKeys) {
                        try { await axiosAdmin.delete(`stock-adjustments/${xid}`); } catch {}
                    }
                    table.selectedRowKeys = [];
                    reFetch(); fetchSummary();
                },
            });
        };

        const typeLabel = (t) => warrantyTypes.find(w => w.key === t)?.label ?? t;
        const typeColor = (t) => warrantyTypes.find(w => w.key === t)?.color ?? '#999';

        const statusBadge = (s) => s === 'pending' ? 'warning' : s === 'approved' ? 'processing' : 'success';
        const statusLabel = (s) => ({ pending: 'Pending', approved: 'Approved', completed: 'Completed' }[s] ?? s);

        onMounted(() => { fetch(); fetchSummary(); });

        return {
            table, columns, filters, kpiCards, summary, warrantyTypes,
            addEditVisible, editRecord, actionLoading,
            reFetch, handleTableChange, onRowSelectChange,
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
    gap: 16px;
    padding: 16px 24px 0;
}
@media (max-width: 900px) { .wm-kpi-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px)  { .wm-kpi-row { grid-template-columns: 1fr; } }

.wm-kpi-card {
    background: #fff;
    border-radius: 10px;
    border-left: 4px solid var(--accent, #1677ff);
    padding: 16px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.wm-kpi-icon {
    font-size: 22px;
    color: var(--accent, #1677ff);
    padding-top: 2px;
    flex-shrink: 0;
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
    margin: 2px 0;
}
.wm-kpi-sub {
    font-size: 11px;
    color: #5A5F7D;
}
</style>
