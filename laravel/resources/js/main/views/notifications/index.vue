<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Notification Center" class="p-0">
                <template #extra>
                    <a-button :loading="loading" @click="load">
                        <ReloadOutlined /> Refresh
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Notifications</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="nc-wrap">
        <a-spin :spinning="loading">

            <!-- KPI Badges -->
            <div class="nc-kpi-row">
                <div class="nc-kpi nc-kpi--red" @click="activeTab = 'low_stock'">
                    <div class="nc-kpi-icon">⚠️</div>
                    <div>
                        <div class="nc-kpi-count">{{ data?.counts?.low_stock ?? 0 }}</div>
                        <div class="nc-kpi-label">Low Stock Items</div>
                    </div>
                </div>
                <div class="nc-kpi nc-kpi--amber" @click="activeTab = 'high_due'">
                    <div class="nc-kpi-icon">💳</div>
                    <div>
                        <div class="nc-kpi-count">{{ data?.counts?.high_due ?? 0 }}</div>
                        <div class="nc-kpi-label">High-Due Customers</div>
                    </div>
                </div>
                <div class="nc-kpi nc-kpi--blue" @click="activeTab = 'cash_transfers'">
                    <div class="nc-kpi-icon">💰</div>
                    <div>
                        <div class="nc-kpi-count">{{ data?.counts?.cash_transfers ?? 0 }}</div>
                        <div class="nc-kpi-label">Recent Transfers (7d)</div>
                    </div>
                </div>
                <div class="nc-kpi" :class="(data?.counts?.total ?? 0) === 0 ? 'nc-kpi--green' : 'nc-kpi--purple'">
                    <div class="nc-kpi-icon">{{ (data?.counts?.total ?? 0) === 0 ? '✅' : '🔔' }}</div>
                    <div>
                        <div class="nc-kpi-count">{{ data?.counts?.total ?? 0 }}</div>
                        <div class="nc-kpi-label">Total Alerts</div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <a-tabs v-model:activeKey="activeTab" class="nc-tabs">

                <!-- Low Stock Tab -->
                <a-tab-pane key="low_stock">
                    <template #tab>
                        <span class="nc-tab-label">
                            ⚠️ Low Stock
                            <a-badge :count="data?.counts?.low_stock ?? 0" class="nc-tab-badge" />
                        </span>
                    </template>

                    <div v-if="data?.low_stock?.length" class="nc-section-card">
                        <div class="nc-section-header nc-header-red">
                            <span>Products below minimum stock level</span>
                            <span class="nc-count-pill">{{ data.low_stock.length }} items</span>
                        </div>
                        <a-table
                            :columns="stockColumns"
                            :data-source="data.low_stock"
                            :pagination="{ pageSize: 20 }"
                            row-key="product_name"
                            size="middle"
                            bordered
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex === 'severity'">
                                    <a-tag :color="stockSeverity(record).color">
                                        {{ stockSeverity(record).label }}
                                    </a-tag>
                                </template>
                                <template v-if="column.dataIndex === 'current_stock'">
                                    <span :class="record.current_stock <= 0 ? 'nc-zero' : 'nc-low'">
                                        {{ record.current_stock }}
                                    </span>
                                </template>
                                <template v-if="column.dataIndex === 'shortage'">
                                    <span class="nc-shortage">+{{ record.shortage }} needed</span>
                                </template>
                                <template v-if="column.dataIndex === 'bar'">
                                    <div class="nc-stock-bar-wrap">
                                        <div
                                            class="nc-stock-bar"
                                            :style="{
                                                width: Math.min(100, Math.round((record.current_stock / record.alert_qty) * 100)) + '%',
                                                background: record.current_stock <= 0 ? '#dc2626' : '#f59e0b'
                                            }"
                                        ></div>
                                    </div>
                                </template>
                            </template>
                        </a-table>
                    </div>
                    <div v-else-if="!loading" class="nc-all-clear">
                        ✅ All products are above their minimum stock levels. No action needed.
                    </div>
                </a-tab-pane>

                <!-- High Due Customers Tab -->
                <a-tab-pane key="high_due">
                    <template #tab>
                        <span class="nc-tab-label">
                            💳 High-Due Customers
                            <a-badge :count="data?.counts?.high_due ?? 0" class="nc-tab-badge" />
                        </span>
                    </template>

                    <div v-if="data?.high_due?.length" class="nc-section-card">
                        <div class="nc-section-header nc-header-amber">
                            <span>Customers with outstanding balances</span>
                            <span class="nc-count-pill">
                                Total Due: {{ fmt(totalDue) }}
                            </span>
                        </div>
                        <a-table
                            :columns="dueColumns"
                            :data-source="data.high_due"
                            :pagination="{ pageSize: 20 }"
                            row-key="customer_name"
                            size="middle"
                            bordered
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex === 'total_due'">
                                    <span class="nc-due-amount">{{ fmt(record.total_due) }}</span>
                                </template>
                                <template v-if="column.dataIndex === 'rank'">
                                    <span class="nc-rank-badge" :class="rankClass(data.high_due.indexOf(record))">
                                        #{{ data.high_due.indexOf(record) + 1 }}
                                    </span>
                                </template>
                                <template v-if="column.dataIndex === 'last_order'">
                                    {{ formatDate(record.last_order) }}
                                </template>
                            </template>
                            <template #summary>
                                <a-table-summary-row>
                                    <a-table-summary-cell :col-span="2"><strong>Total</strong></a-table-summary-cell>
                                    <a-table-summary-cell><strong class="nc-due-amount">{{ fmt(totalDue) }}</strong></a-table-summary-cell>
                                    <a-table-summary-cell :col-span="3" />
                                </a-table-summary-row>
                            </template>
                        </a-table>
                    </div>
                    <div v-else-if="!loading" class="nc-all-clear">
                        ✅ No customers with outstanding dues. All accounts are settled.
                    </div>
                </a-tab-pane>

                <!-- Cash Transfers Tab -->
                <a-tab-pane key="cash_transfers">
                    <template #tab>
                        <span class="nc-tab-label">
                            💰 Cash Transfers
                            <a-badge :count="data?.counts?.cash_transfers ?? 0" class="nc-tab-badge" />
                        </span>
                    </template>

                    <div v-if="data?.cash_transfers?.length" class="nc-section-card">
                        <div class="nc-section-header nc-header-blue">
                            <span>Cash transfers in the last 7 days</span>
                            <span class="nc-count-pill">{{ data.cash_transfers.length }} transfers</span>
                        </div>
                        <a-table
                            :columns="transferColumns"
                            :data-source="data.cash_transfers"
                            :pagination="{ pageSize: 20 }"
                            row-key="reference_number"
                            size="middle"
                            bordered
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex === 'transfer_type'">
                                    <a-tag :color="typeColor(record.transfer_type)">
                                        {{ typeLabel(record.transfer_type) }}
                                    </a-tag>
                                </template>
                                <template v-if="column.dataIndex === 'amount'">
                                    <span class="nc-transfer-amount">{{ fmt(record.amount) }}</span>
                                </template>
                                <template v-if="column.dataIndex === 'transfer_date'">
                                    {{ formatDate(record.transfer_date) }}
                                </template>
                            </template>
                        </a-table>
                    </div>
                    <div v-else-if="!loading" class="nc-all-clear">
                        ✅ No cash transfers in the last 7 days.
                    </div>
                </a-tab-pane>

            </a-tabs>
        </a-spin>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
import { ReloadOutlined } from "@ant-design/icons-vue";
import dayjs from "dayjs";
import AdminPageHeader from "../../../common/layouts/AdminPageHeader.vue";
import common from "../../../common/composable/common";

export default {
    components: { ReloadOutlined, AdminPageHeader },
    setup() {
        const { formatAmountCurrency, formatDate } = common();

        const loading   = ref(false);
        const data      = ref(null);
        const activeTab = ref("low_stock");

        const fmt = (v) => formatAmountCurrency(v ?? 0);

        const stockColumns = [
            { title: "Product",   dataIndex: "product_name",  sorter: (a, b) => a.product_name.localeCompare(b.product_name) },
            { title: "Branch",    dataIndex: "warehouse_name" },
            { title: "Current Stock", dataIndex: "current_stock", sorter: (a, b) => a.current_stock - b.current_stock },
            { title: "Min Level", dataIndex: "alert_qty" },
            { title: "Shortage",  dataIndex: "shortage",      sorter: (a, b) => a.shortage - b.shortage },
            { title: "Level",     dataIndex: "bar",           width: 120 },
            { title: "Severity",  dataIndex: "severity",      width: 100 },
        ];

        const dueColumns = [
            { title: "Rank",     dataIndex: "rank", width: 70 },
            { title: "Customer", dataIndex: "customer_name", sorter: (a, b) => a.customer_name.localeCompare(b.customer_name) },
            { title: "Total Due", dataIndex: "total_due", sorter: (a, b) => a.total_due - b.total_due, defaultSortOrder: "descend" },
            { title: "Invoices", dataIndex: "invoice_count" },
            { title: "Phone",    dataIndex: "phone" },
            { title: "Last Order", dataIndex: "last_order" },
        ];

        const transferColumns = [
            { title: "Ref #",     dataIndex: "reference_number" },
            { title: "From",      dataIndex: "from_branch" },
            { title: "To",        dataIndex: "to_branch" },
            { title: "Amount",    dataIndex: "amount", sorter: (a, b) => a.amount - b.amount },
            { title: "Type",      dataIndex: "transfer_type" },
            { title: "Date",      dataIndex: "transfer_date" },
            { title: "By",        dataIndex: "transferred_by" },
        ];

        const totalDue = computed(() => {
            return (data.value?.high_due ?? []).reduce((s, r) => s + (r.total_due || 0), 0);
        });

        const stockSeverity = (item) => {
            if (item.current_stock <= 0)                       return { color: "red",    label: "Out of Stock" };
            if (item.current_stock <= item.alert_qty * 0.5)   return { color: "orange", label: "Critical" };
            return { color: "gold", label: "Low" };
        };

        const rankClass = (idx) => {
            if (idx === 0) return "rank-gold";
            if (idx === 1) return "rank-silver";
            if (idx === 2) return "rank-bronze";
            return "";
        };

        const typeLabel = (type) => {
            const m = { ho_to_branch: "HO → Branch", branch_to_ho: "Branch → HO", branch_to_branch: "Branch → Branch" };
            return m[type] ?? type;
        };

        const typeColor = (type) => {
            const m = { ho_to_branch: "blue", branch_to_ho: "green", branch_to_branch: "orange" };
            return m[type] ?? "default";
        };

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get("erp-notifications");
                data.value = res;
                if (res.counts?.low_stock > 0)      activeTab.value = "low_stock";
                else if (res.counts?.high_due > 0)  activeTab.value = "high_due";
                else                                 activeTab.value = "cash_transfers";
            } catch (_) {
                data.value = null;
            } finally {
                loading.value = false;
            }
        };

        onMounted(load);

        return {
            loading, data, activeTab,
            stockColumns, dueColumns, transferColumns,
            totalDue,
            fmt, formatDate, stockSeverity, rankClass, typeLabel, typeColor,
            load,
        };
    },
};
</script>

<style scoped>
.nc-wrap { padding: 20px 24px; }

/* KPI Row */
.nc-kpi-row { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.nc-kpi {
    flex: 1; min-width: 180px; display: flex; align-items: center; gap: 14px;
    padding: 16px 18px; border-radius: 12px; color: #fff; cursor: pointer;
    box-shadow: 0 2px 12px rgba(0,0,0,.12); transition: transform .15s, box-shadow .15s;
}
.nc-kpi:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.16); }
.nc-kpi--red    { background: linear-gradient(135deg, #ef4444, #b91c1c); }
.nc-kpi--amber  { background: linear-gradient(135deg, #f59e0b, #b45309); }
.nc-kpi--blue   { background: linear-gradient(135deg, #1677ff, #0958d9); }
.nc-kpi--green  { background: linear-gradient(135deg, #22c55e, #15803d); }
.nc-kpi--purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.nc-kpi-icon  { font-size: 28px; }
.nc-kpi-count { font-size: 26px; font-weight: 900; line-height: 1; }
.nc-kpi-label { font-size: 11px; opacity: .88; text-transform: uppercase; letter-spacing: .4px; margin-top: 3px; }

/* Tabs */
.nc-tabs :deep(.ant-tabs-nav) { margin-bottom: 0; padding: 0 4px; background: #fff; border-radius: 10px 10px 0 0; }
.nc-tab-label { display: flex; align-items: center; gap: 8px; font-weight: 600; }
.nc-tab-badge :deep(.ant-badge-count) { background: #ef4444; }

/* Section card */
.nc-section-card { background: #fff; border-radius: 0 0 12px 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.nc-section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; font-size: 13px; font-weight: 600; color: #1e293b;
    border-bottom: 1px solid #e2e8f0;
}
.nc-header-red   { background: linear-gradient(90deg, #fff5f5, #fee2e2); border-left: 4px solid #dc2626; }
.nc-header-amber { background: linear-gradient(90deg, #fffbeb, #fef3c7); border-left: 4px solid #f59e0b; }
.nc-header-blue  { background: linear-gradient(90deg, #eff6ff, #dbeafe); border-left: 4px solid #1677ff; }

.nc-count-pill {
    font-size: 12px; font-weight: 700; background: #e2e8f0;
    padding: 2px 10px; border-radius: 20px; color: #475569;
}

/* Stock bar */
.nc-stock-bar-wrap {
    height: 10px; background: #f1f5f9; border-radius: 5px; overflow: hidden; width: 100%;
}
.nc-stock-bar { height: 100%; border-radius: 5px; transition: width .4s; }

/* Value colors */
.nc-zero     { color: #dc2626; font-weight: 800; }
.nc-low      { color: #d97706; font-weight: 700; }
.nc-shortage { color: #dc2626; font-weight: 600; font-size: 12px; }
.nc-due-amount      { color: #dc2626; font-weight: 800; font-family: 'Courier New', monospace; }
.nc-transfer-amount { color: #1677ff; font-weight: 700; font-family: 'Courier New', monospace; }

/* Rank */
.nc-rank-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 32px; padding: 2px 8px; border-radius: 6px;
    font-size: 12px; font-weight: 800;
    background: #e2e8f0; color: #64748b;
}
.rank-gold   { background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }
.rank-silver { background: #f1f5f9; color: #475569; border: 1px solid #94a3b8; }
.rank-bronze { background: #fef9ee; color: #78350f; border: 1px solid #d97706; }

/* All clear */
.nc-all-clear {
    padding: 48px 24px; text-align: center;
    font-size: 15px; color: #64748b;
    background: #fff; border-radius: 0 0 12px 12px;
}
</style>
