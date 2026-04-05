<template>
    <a-dropdown
        v-if="permsArray.includes('admin')"
        trigger="click"
        placement="bottomRight"
        :overlay-style="{ width: '360px' }"
        @visible-change="onDropdownOpen"
    >
        <a-badge :count="counts.total" :overflow-count="99" class="nb-badge">
            <a-button type="text" class="nb-bell-btn">
                <BellOutlined class="nb-bell-icon" />
            </a-button>
        </a-badge>

        <template #overlay>
            <div class="nb-panel">
                <!-- Header -->
                <div class="nb-header">
                    <span class="nb-title">🔔 Notifications</span>
                    <router-link :to="{ name: 'admin.notifications.index' }" class="nb-view-all" @click="closeDropdown">
                        View All
                    </router-link>
                </div>

                <a-spin :spinning="loading" size="small">
                    <!-- Low Stock Section -->
                    <div v-if="alerts.low_stock && alerts.low_stock.length" class="nb-section">
                        <div class="nb-section-title nb-section-red">
                            <span>⚠️ Low Stock</span>
                            <span class="nb-badge-sm">{{ alerts.low_stock.length }}</span>
                        </div>
                        <div
                            v-for="(item, i) in alerts.low_stock.slice(0, 4)"
                            :key="i"
                            class="nb-item"
                        >
                            <div class="nb-item-icon nb-icon-red">📦</div>
                            <div class="nb-item-body">
                                <div class="nb-item-title">{{ item.product_name }}</div>
                                <div class="nb-item-sub">{{ item.warehouse_name }} &mdash; Stock: <strong>{{ item.current_stock }}</strong> / Alert: {{ item.alert_qty }}</div>
                            </div>
                        </div>
                        <div v-if="alerts.low_stock.length > 4" class="nb-more">
                            +{{ alerts.low_stock.length - 4 }} more low stock items
                        </div>
                    </div>

                    <!-- High Due Section -->
                    <div v-if="alerts.high_due && alerts.high_due.length" class="nb-section">
                        <div class="nb-section-title nb-section-amber">
                            <span>💳 High Due Customers</span>
                            <span class="nb-badge-sm">{{ alerts.high_due.length }}</span>
                        </div>
                        <div
                            v-for="(item, i) in alerts.high_due.slice(0, 4)"
                            :key="i"
                            class="nb-item"
                        >
                            <div class="nb-item-icon nb-icon-amber">👤</div>
                            <div class="nb-item-body">
                                <div class="nb-item-title">{{ item.customer_name }}</div>
                                <div class="nb-item-sub">Due: <strong class="nb-due">{{ fmtAmt(item.total_due) }}</strong> &mdash; {{ item.invoice_count }} invoices</div>
                            </div>
                        </div>
                        <div v-if="alerts.high_due.length > 4" class="nb-more">
                            +{{ alerts.high_due.length - 4 }} more customers
                        </div>
                    </div>

                    <!-- Cash Transfers Section -->
                    <div v-if="alerts.cash_transfers && alerts.cash_transfers.length" class="nb-section">
                        <div class="nb-section-title nb-section-blue">
                            <span>💰 Recent Transfers (7 days)</span>
                            <span class="nb-badge-sm">{{ alerts.cash_transfers.length }}</span>
                        </div>
                        <div
                            v-for="(item, i) in alerts.cash_transfers.slice(0, 3)"
                            :key="i"
                            class="nb-item"
                        >
                            <div class="nb-item-icon nb-icon-blue">🔄</div>
                            <div class="nb-item-body">
                                <div class="nb-item-title">{{ item.from_branch }} → {{ item.to_branch }}</div>
                                <div class="nb-item-sub">{{ fmtAmt(item.amount) }} &mdash; {{ item.reference_number }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- All clear -->
                    <div v-if="!loading && counts.total === 0" class="nb-empty">
                        ✅ All clear — no alerts
                    </div>
                </a-spin>

                <!-- Footer -->
                <div class="nb-footer">
                    <router-link :to="{ name: 'admin.notifications.index' }" @click="closeDropdown">
                        <a-button type="primary" block size="small">View Full Notification Center</a-button>
                    </router-link>
                </div>
            </div>
        </template>
    </a-dropdown>
</template>

<script>
import { ref, reactive, onMounted } from "vue";
import { BellOutlined } from "@ant-design/icons-vue";
import common from "../../common/composable/common";

export default {
    name: "NotificationBell",
    components: { BellOutlined },
    setup() {
        const { permsArray, formatAmountCurrency } = common();
        const loading = ref(false);
        const counts  = reactive({ total: 0, low_stock: 0, high_due: 0, cash_transfers: 0 });
        const alerts  = reactive({ low_stock: [], high_due: [], cash_transfers: [] });
        const loaded  = ref(false);

        const fmtAmt = (v) => formatAmountCurrency(v ?? 0);

        const fetchCounts = async () => {
            try {
                const res = await axiosAdmin.get("erp-notifications/counts");
                Object.assign(counts, res);
            } catch (_) {}
        };

        const fetchAlerts = async () => {
            if (loaded.value) return;
            loading.value = true;
            try {
                const res = await axiosAdmin.get("erp-notifications");
                Object.assign(alerts, {
                    low_stock:      res.low_stock      ?? [],
                    high_due:       res.high_due       ?? [],
                    cash_transfers: res.cash_transfers ?? [],
                });
                Object.assign(counts, res.counts ?? {});
                loaded.value = true;
            } catch (_) {} finally {
                loading.value = false;
            }
        };

        const onDropdownOpen = (open) => { if (open) fetchAlerts(); };
        const closeDropdown  = () => {};

        onMounted(() => {
            if (permsArray.value.includes("admin")) {
                fetchCounts();
                setInterval(fetchCounts, 60000);
            }
        });

        return { permsArray, loading, counts, alerts, fmtAmt, onDropdownOpen, closeDropdown };
    },
};
</script>

<style scoped>
.nb-bell-btn { color: #475569; font-size: 18px; padding: 0 6px; }
.nb-bell-icon { font-size: 18px; }
.nb-badge :deep(.ant-badge-count) { box-shadow: 0 0 0 2px #fff; }

.nb-panel {
    background: #fff; border-radius: 12px; overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,.18); border: 1px solid #e2e8f0;
    max-height: 520px; overflow-y: auto;
}
.nb-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px 10px;
    background: linear-gradient(135deg, #1e3a5f, #1677ff);
    color: #fff;
}
.nb-title { font-size: 14px; font-weight: 700; }
.nb-view-all { color: rgba(255,255,255,.85); font-size: 12px; text-decoration: underline; }

.nb-section { border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; }
.nb-section-title {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 16px 4px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
}
.nb-section-red   { color: #dc2626; }
.nb-section-amber { color: #d97706; }
.nb-section-blue  { color: #1677ff; }

.nb-badge-sm {
    background: currentColor; color: #fff; border-radius: 10px;
    padding: 1px 7px; font-size: 10px;
}

.nb-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 8px 16px; transition: background .12s;
}
.nb-item:hover { background: #f8fafc; }
.nb-item-icon {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.nb-icon-red   { background: #fee2e2; }
.nb-icon-amber { background: #fef3c7; }
.nb-icon-blue  { background: #dbeafe; }

.nb-item-title { font-size: 13px; font-weight: 600; color: #1e293b; }
.nb-item-sub   { font-size: 11px; color: #64748b; margin-top: 2px; }
.nb-due        { color: #dc2626; }

.nb-more { padding: 4px 16px 8px; font-size: 11px; color: #94a3b8; font-style: italic; }
.nb-empty { padding: 20px; text-align: center; color: #64748b; font-size: 13px; }
.nb-footer { padding: 10px 16px; border-top: 1px solid #e2e8f0; background: #f8fafc; }
</style>
