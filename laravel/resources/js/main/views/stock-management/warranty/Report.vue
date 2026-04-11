<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Warranty & Damage Report" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.stock.warranty.index' }">Warranty & Damage</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Report</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="wr-wrap" v-if="!loading">
        <!-- Summary Cards -->
        <div class="wr-section-title">Summary by Type</div>
        <div class="wr-summary-grid">
            <div v-for="t in typeDefs" :key="t.key" class="wr-summary-card" :style="`--c:${t.color}`">
                <div class="wr-st-header">
                    <span class="wr-st-dot"></span>
                    <span class="wr-st-name">{{ t.label }}</span>
                </div>
                <div class="wr-st-row">
                    <div class="wr-st-cell">
                        <div class="wr-st-num">{{ summary[t.key]?.total ?? 0 }}</div>
                        <div class="wr-st-sub">Records</div>
                    </div>
                    <div class="wr-st-cell">
                        <div class="wr-st-num pending">{{ summary[t.key]?.pending_qty ?? 0 }}</div>
                        <div class="wr-st-sub">Pending Qty</div>
                    </div>
                    <div class="wr-st-cell">
                        <div class="wr-st-num approved">{{ summary[t.key]?.approved_qty ?? 0 }}</div>
                        <div class="wr-st-sub">Approved Qty</div>
                    </div>
                    <div class="wr-st-cell">
                        <div class="wr-st-num completed">{{ summary[t.key]?.completed_qty ?? 0 }}</div>
                        <div class="wr-st-sub">Completed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="wr-section-title" style="margin-top:24px">All Records</div>
        <div class="wr-filters">
            <a-select
                v-model:value="typeFilter"
                placeholder="All types"
                allow-clear
                style="width:180px"
                @change="applyFilter"
            >
                <a-select-option v-for="t in typeDefs" :key="t.key" :value="t.key">
                    {{ t.label }}
                </a-select-option>
            </a-select>
            <a-select
                v-model:value="statusFilter"
                placeholder="All statuses"
                allow-clear
                style="width:160px"
                @change="applyFilter"
            >
                <a-select-option value="pending">Pending</a-select-option>
                <a-select-option value="approved">Approved</a-select-option>
                <a-select-option value="completed">Completed</a-select-option>
            </a-select>
            <a-button @click="$router.push({ name: 'admin.stock.warranty.index' })">
                ← Back to List
            </a-button>
        </div>

        <a-table
            :columns="columns"
            :data-source="filteredRecords"
            row-key="xid"
            :pagination="{ pageSize: 20, showSizeChanger: true }"
            bordered size="middle"
            style="margin-top:12px"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'warranty_type'">
                    <a-tag :color="typeDefs.find(t => t.key === record.warranty_type)?.color ?? '#999'">
                        {{ typeDefs.find(t => t.key === record.warranty_type)?.label ?? record.warranty_type }}
                    </a-tag>
                </template>
                <template v-if="column.dataIndex === 'status'">
                    <a-badge
                        :status="record.status === 'pending' ? 'warning' : record.status === 'approved' ? 'processing' : 'success'"
                        :text="{ pending:'Pending', approved:'Approved', completed:'Completed' }[record.status]"
                    />
                </template>
            </template>
        </a-table>
    </div>

    <div v-else style="text-align:center;padding:80px">
        <a-spin size="large" />
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';

const axiosAdmin = window.axiosAdmin;

const typeDefs = [
    { key: 'damage',           label: 'Damaged',          color: '#FF4D4F' },
    { key: 'expired',          label: 'Expired Warranty', color: '#FA8B0C' },
    { key: 'claimable',        label: 'Claimable',        color: '#1677ff' },
    { key: 'return_to_vendor', label: 'Return to Vendor', color: '#722ed1' },
];

const columns = [
    { title: 'Product',   dataIndex: 'product_name', sorter: (a, b) => a.product_name.localeCompare(b.product_name) },
    { title: 'Item Code', dataIndex: 'item_code', width: 110 },
    { title: 'Qty',       dataIndex: 'quantity',  align: 'right', width: 70,
      sorter: (a, b) => a.quantity - b.quantity },
    { title: 'Type',      dataIndex: 'warranty_type', width: 150 },
    { title: 'Status',    dataIndex: 'status', width: 120 },
    { title: 'Remarks',   dataIndex: 'remarks', ellipsis: true },
    { title: 'Date',      dataIndex: 'created_at', width: 105, sorter: (a, b) => a.created_at?.localeCompare(b.created_at) },
];

export default defineComponent({
    components: { AdminPageHeader },
    setup() {
        const loading      = ref(true);
        const summary      = ref({});
        const records      = ref([]);
        const typeFilter   = ref(undefined);
        const statusFilter = ref(undefined);

        const filteredRecords = computed(() => {
            let list = records.value;
            if (typeFilter.value)   list = list.filter(r => r.warranty_type === typeFilter.value);
            if (statusFilter.value) list = list.filter(r => r.status        === statusFilter.value);
            return list;
        });

        const applyFilter = () => {};

        onMounted(async () => {
            try {
                const res    = await axiosAdmin.get('stock-adjustments-warranty/report');
                const data   = res.data;
                summary.value = data.summary ?? {};
                records.value = data.records ?? [];
            } catch { message.error('Failed to load report'); }
            finally { loading.value = false; }
        });

        return { loading, summary, records, filteredRecords, typeDefs, columns, typeFilter, statusFilter, applyFilter };
    },
});
</script>

<style scoped>
.wr-wrap { padding: 16px 24px 32px; }
.wr-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #5A5F7D;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 12px;
}
.wr-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}
@media (max-width: 900px) { .wr-summary-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px)  { .wr-summary-grid { grid-template-columns: 1fr; } }

.wr-summary-card {
    background: #fff;
    border-radius: 10px;
    border-top: 3px solid var(--c, #1677ff);
    padding: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.wr-st-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.wr-st-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--c, #1677ff); flex-shrink: 0; }
.wr-st-name { font-size: 12px; font-weight: 700; color: #272B41; }
.wr-st-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
.wr-st-cell { text-align: center; }
.wr-st-num { font-size: 18px; font-weight: 700; color: #272B41; }
.wr-st-num.pending   { color: #FA8B0C; }
.wr-st-num.approved  { color: #1677ff; }
.wr-st-num.completed { color: #20C997; }
.wr-st-sub { font-size: 9px; color: #ADB4D2; text-transform: uppercase; letter-spacing: .3px; }
.wr-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 4px; }
</style>
