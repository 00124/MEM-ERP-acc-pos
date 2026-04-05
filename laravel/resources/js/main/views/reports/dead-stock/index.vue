<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Dead Stock Report" class="p-0">
                <template #extra>
                    <a-button @click="print" class="no-print">
                        <PrinterOutlined /> Print
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Reports</a-breadcrumb-item>
                <a-breadcrumb-item>Dead Stock</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <!-- Filters -->
        <a-row :gutter="16" class="mb-20 no-print" align="middle">
            <a-col :span="6">
                <label class="block text-xs text-gray-500 mb-1">Inactivity Threshold (days)</label>
                <a-select v-model:value="filters.days" style="width:100%">
                    <a-select-option :value="30">30 days</a-select-option>
                    <a-select-option :value="60">60 days</a-select-option>
                    <a-select-option :value="90">90 days</a-select-option>
                    <a-select-option :value="180">180 days (6 months)</a-select-option>
                    <a-select-option :value="365">365 days (1 year)</a-select-option>
                </a-select>
            </a-col>
            <a-col :span="6">
                <label class="block text-xs text-gray-500 mb-1">Branch / Warehouse (optional)</label>
                <a-select v-model:value="filters.warehouse_id" style="width:100%" allow-clear placeholder="All Branches">
                    <a-select-option v-for="w in warehouses" :key="w.xid" :value="w.xid">{{ w.name }}</a-select-option>
                </a-select>
            </a-col>
            <a-col :span="4">
                <a-button type="primary" @click="load" class="mt-20" :loading="loading">
                    <SearchOutlined /> Generate
                </a-button>
            </a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area" v-if="generated">

                <!-- KPI Cards -->
                <a-row :gutter="16" class="mb-24">
                    <a-col :span="6">
                        <div class="ds-kpi ds-kpi--red">
                            <div class="ds-kpi-icon"><WarningOutlined /></div>
                            <div>
                                <div class="ds-kpi-label">Dead SKUs</div>
                                <div class="ds-kpi-value">{{ data?.count ?? 0 }}</div>
                            </div>
                        </div>
                    </a-col>
                    <a-col :span="6">
                        <div class="ds-kpi ds-kpi--orange">
                            <div class="ds-kpi-icon"><ShoppingOutlined /></div>
                            <div>
                                <div class="ds-kpi-label">Total Units Stuck</div>
                                <div class="ds-kpi-value">{{ fmtNum(data?.total_qty ?? 0) }}</div>
                            </div>
                        </div>
                    </a-col>
                    <a-col :span="6">
                        <div class="ds-kpi ds-kpi--amber">
                            <div class="ds-kpi-icon"><DollarOutlined /></div>
                            <div>
                                <div class="ds-kpi-label">Capital Locked</div>
                                <div class="ds-kpi-value">{{ fmtAmt(data?.total_value ?? 0) }}</div>
                            </div>
                        </div>
                    </a-col>
                    <a-col :span="6">
                        <div class="ds-kpi ds-kpi--gray">
                            <div class="ds-kpi-icon"><ClockCircleOutlined /></div>
                            <div>
                                <div class="ds-kpi-label">Threshold</div>
                                <div class="ds-kpi-value">{{ filters.days }} days</div>
                            </div>
                        </div>
                    </a-col>
                </a-row>

                <!-- Print header -->
                <div class="print-header text-center mb-20">
                    <h2 style="margin:0">Dead Stock Report</h2>
                    <p style="margin:4px 0;color:#666">
                        Items with no sales in the last {{ filters.days }} days &nbsp;|&nbsp;
                        Generated: {{ today }}
                    </p>
                </div>

                <!-- Table -->
                <a-table
                    :dataSource="data?.rows ?? []"
                    :columns="columns"
                    :pagination="{ pageSize: 50, showSizeChanger: true, pageSizeOptions: ['20','50','100','500'] }"
                    size="middle"
                    :rowKey="(r) => r.item_code + r.warehouse_name"
                    :scroll="{ x: 900 }"
                    :rowClassName="rowClass"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'product_name'">
                            <div class="font-medium">{{ record.product_name }}</div>
                            <div class="text-xs text-gray-400">{{ record.item_code }}</div>
                        </template>
                        <template v-if="column.key === 'last_sale_date'">
                            <span v-if="record.last_sale_date" class="text-orange-500">
                                {{ record.last_sale_date }}
                            </span>
                            <a-tag v-else color="red">Never Sold</a-tag>
                        </template>
                        <template v-if="column.key === 'days_inactive'">
                            <span v-if="record.days_inactive !== null" :class="ageClass(record.days_inactive)">
                                {{ record.days_inactive }} days
                            </span>
                            <a-tag v-else color="red">∞ Never</a-tag>
                        </template>
                        <template v-if="column.key === 'stock_value'">
                            <span class="font-semibold text-red-600">{{ fmtAmt(record.stock_value) }}</span>
                        </template>
                    </template>

                    <template #summary>
                        <a-table-summary-row>
                            <a-table-summary-cell :index="0" :col-span="4"><b>TOTAL</b></a-table-summary-cell>
                            <a-table-summary-cell :index="4" align="right">
                                <b>{{ fmtNum(data?.total_qty ?? 0) }}</b>
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="5"></a-table-summary-cell>
                            <a-table-summary-cell :index="6" align="right">
                                <b class="text-red-600">{{ fmtAmt(data?.total_value ?? 0) }}</b>
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="7"></a-table-summary-cell>
                        </a-table-summary-row>
                    </template>
                </a-table>

                <a-empty v-if="(data?.rows?.length ?? 0) === 0" description="No dead stock found — all items are moving!" class="mt-40" />
            </div>

            <a-empty v-if="!generated && !loading" description="Select threshold and click Generate" class="mt-40" />
        </a-spin>
    </a-card>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import {
    PrinterOutlined, SearchOutlined, WarningOutlined,
    ShoppingOutlined, DollarOutlined, ClockCircleOutlined,
} from '@ant-design/icons-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: {
        AdminPageHeader, PrinterOutlined, SearchOutlined,
        WarningOutlined, ShoppingOutlined, DollarOutlined, ClockCircleOutlined,
    },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading    = ref(false);
        const generated  = ref(false);
        const warehouses = ref([]);
        const data       = ref(null);
        const today      = dayjs().format('DD MMM YYYY');

        const filters = ref({
            days:         60,
            warehouse_id: null,
        });

        const columns = [
            { title: 'Product',      key: 'product_name',  width: 220 },
            { title: 'Category',     dataIndex: 'category_name',  key: 'category_name', width: 160 },
            { title: 'Branch',       dataIndex: 'warehouse_name', key: 'warehouse_name', width: 140 },
            { title: 'Purchase Price', dataIndex: 'purchase_price', key: 'purchase_price', width: 130, align: 'right',
              customRender: ({ text }) => fmtAmt(text) },
            { title: 'Stock Qty',    dataIndex: 'current_stock',  key: 'current_stock',  width: 100, align: 'right' },
            { title: 'Last Sale',    key: 'last_sale_date', width: 130 },
            { title: 'Stock Value',  key: 'stock_value',    width: 150, align: 'right' },
            { title: 'Inactive',     key: 'days_inactive',  width: 130, align: 'right' },
        ];

        const fmtAmt = (v) => 'Rs ' + Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        const fmtNum = (v) => Number(v || 0).toLocaleString('en-PK');

        const ageClass = (days) => {
            if (days === null) return 'text-red-700 font-bold';
            if (days >= 180)   return 'text-red-600 font-semibold';
            if (days >= 90)    return 'text-orange-500 font-semibold';
            return 'text-yellow-600';
        };

        const rowClass = (record) => {
            const d = record.days_inactive;
            if (d === null || d >= 180) return 'ds-row-critical';
            if (d >= 90)                return 'ds-row-high';
            return 'ds-row-medium';
        };

        const loadWarehouses = async () => {
            try {
                const res = await axiosAdmin.get('warehouses?limit=500');
                warehouses.value = (res.data?.data || res.data || []);
            } catch (_) {}
        };

        const load = async () => {
            loading.value  = true;
            generated.value = true;
            try {
                const params = { days: filters.value.days };
                if (filters.value.warehouse_id) params.warehouse_id = filters.value.warehouse_id;
                const res = await axiosAdmin.get('reports/dead-stock', { params });
                data.value = res.data ?? res;
            } catch (e) {
                data.value = null;
            } finally {
                loading.value = false;
            }
        };

        const print = () => window.print();

        onMounted(loadWarehouses);

        return {
            loading, generated, warehouses, data, filters, columns,
            fmtAmt, fmtNum, ageClass, rowClass, today, load, print,
        };
    },
});
</script>

<style scoped>
.ds-kpi {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; border-radius: 12px; color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,.1);
}
.ds-kpi-icon { font-size: 28px; opacity: .85; }
.ds-kpi-label { font-size: 12px; opacity: .85; }
.ds-kpi-value { font-size: 22px; font-weight: 700; line-height: 1.1; }
.ds-kpi--red    { background: linear-gradient(135deg, #ef4444, #b91c1c); }
.ds-kpi--orange { background: linear-gradient(135deg, #f97316, #c2410c); }
.ds-kpi--amber  { background: linear-gradient(135deg, #f59e0b, #b45309); }
.ds-kpi--gray   { background: linear-gradient(135deg, #6b7280, #374151); }

.print-header { display: none; }

@media print {
    .no-print { display: none !important; }
    .print-header { display: block !important; }
}
</style>

<style>
.ds-row-critical td { background: #fff1f0 !important; }
.ds-row-high td     { background: #fff7e6 !important; }
.ds-row-medium td   { background: #fffbe6 !important; }
</style>
