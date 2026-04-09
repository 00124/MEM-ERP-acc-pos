<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Customer Ledger" class="p-0">
                <template #extra>
                    <a-button @click="print"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Customer Ledger</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <a-row :gutter="16" class="mb-20" align="middle">
            <a-col :span="7">
                <label class="block text-xs text-gray-500 mb-1">Customer (optional — leave blank for all)</label>
                <a-select v-model:value="filters.user_id" style="width:100%" show-search :filter-option="filterOption" placeholder="All Customers" allow-clear>
                    <a-select-option v-for="c in customers" :key="c.xid" :value="c.xid">{{ c.name }}</a-select-option>
                </a-select>
            </a-col>
            <a-col :span="5">
                <label class="block text-xs text-gray-500 mb-1">From Date</label>
                <a-date-picker v-model:value="filters.date_from" style="width:100%" />
            </a-col>
            <a-col :span="5">
                <label class="block text-xs text-gray-500 mb-1">To Date</label>
                <a-date-picker v-model:value="filters.date_to" style="width:100%" />
            </a-col>
            <a-col :span="7" style="display:flex;align-items:flex-end;gap:8px;padding-top:20px;">
                <a-button type="primary" @click="load" :loading="loading"><SearchOutlined /> Generate</a-button>
                <a-button v-if="generated" @click="backfillJEs" :loading="backfilling" title="Generate missing Journal Entries for all past orders">
                    <SyncOutlined /> Backfill JEs
                </a-button>
            </a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area" v-if="generated">
                <div class="text-center mb-20">
                    <h2 style="margin:0">Customer Ledger</h2>
                    <h3 v-if="reportData.customer" style="margin:4px 0;color:#333">{{ reportData.customer.name }}</h3>
                    <p style="margin:0;color:#666">{{ formatDate(reportData.date_from) }} to {{ formatDate(reportData.date_to) }}</p>
                </div>

                <a-table
                    :dataSource="tableRows"
                    :columns="columns"
                    :pagination="false"
                    size="middle"
                    :rowKey="(r, i) => i"
                    :scroll="{ x: 860 }"
                    :rowClassName="(r) => r._is_opening ? 'ob-row' : (r.items && r.items.length ? 'cl-expandable-row' : '')"
                    :expandable="expandable"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'reference'">
                            <span v-if="record._is_opening" style="color:#94a3b8;font-style:italic">—</span>
                            <router-link
                                v-else-if="record.order_xid && record.type === 'Sale'"
                                :to="{ name: 'admin.stock.sales.index' }"
                                @click.prevent="openSaleDetail(record)"
                                style="color:#2563eb;font-weight:600;cursor:pointer;text-decoration:underline;"
                            >
                                {{ record.reference }}
                                <span v-if="record.items && record.items.length" style="font-size:10px;color:#94a3b8;margin-left:4px;">({{ record.items.length }} item{{ record.items.length > 1 ? 's' : '' }})</span>
                            </router-link>
                            <span v-else>{{ record.reference }}</span>
                        </template>
                        <template v-if="column.key === 'type'">
                            <a-tag v-if="!record._is_opening" :color="typeColor(record.type)">{{ record.type }}</a-tag>
                            <b v-else style="color:#6b7280;font-style:italic">Opening Balance</b>
                        </template>
                        <template v-if="column.key === 'debit'">
                            <span v-if="+record.debit !== 0" class="text-blue-600">{{ fmt(record.debit) }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                        <template v-if="column.key === 'credit'">
                            <span v-if="+record.credit !== 0" class="text-green-600">{{ fmt(record.credit) }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                        <template v-if="column.key === 'running_balance'">
                            <span :class="record.running_balance >= 0 ? 'text-blue-700 font-semibold' : 'text-green-600 font-semibold'">
                                {{ fmt(Math.abs(record.running_balance)) }} {{ record.running_balance >= 0 ? 'Dr' : 'Cr' }}
                            </span>
                        </template>
                    </template>

                    <!-- Expandable row showing item details -->
                    <template #expandedRowRender="{ record }">
                        <div v-if="record.items && record.items.length" class="cl-items-panel">
                            <div class="cl-items-title"><ShoppingOutlined /> Items sold — {{ record.reference }}</div>
                            <table class="cl-items-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Code</th>
                                        <th class="cl-right">Qty</th>
                                        <th class="cl-right">Unit Price</th>
                                        <th class="cl-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in record.items" :key="idx">
                                        <td class="cl-idx">{{ idx + 1 }}</td>
                                        <td class="cl-name">{{ item.name }}</td>
                                        <td><span v-if="item.item_code" class="cl-code">{{ item.item_code }}</span></td>
                                        <td class="cl-right cl-qty">{{ item.qty }}</td>
                                        <td class="cl-right">{{ fmt(item.unit_price) }}</td>
                                        <td class="cl-right cl-subtotal">{{ fmt(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="cl-total-row">
                                        <td colspan="5" class="cl-right" style="font-weight:700;color:#334155;padding:8px 10px;">Total</td>
                                        <td class="cl-right cl-subtotal" style="padding:8px 10px;">{{ fmt(record.debit) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-else style="color:#94a3b8;font-size:12px;padding:8px 16px;">No item details available.</div>
                    </template>

                    <template #summary>
                        <a-table-summary-row>
                            <a-table-summary-cell :index="0" :col-span="3"><b>CLOSING BALANCE</b></a-table-summary-cell>
                            <a-table-summary-cell :index="3" align="right"><b class="text-blue-600">{{ fmt(openingBalance > 0 ? openingBalance + totalDebit : totalDebit) }}</b></a-table-summary-cell>
                            <a-table-summary-cell :index="4" align="right"><b class="text-green-600">{{ fmt(openingBalance < 0 ? Math.abs(openingBalance) + totalCredit : totalCredit) }}</b></a-table-summary-cell>
                            <a-table-summary-cell :index="5" align="right">
                                <b :class="closingBalance >= 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ fmt(Math.abs(closingBalance)) }} {{ closingBalance >= 0 ? ' Receivable' : ' Advance' }}
                                </b>
                            </a-table-summary-cell>
                        </a-table-summary-row>
                    </template>
                </a-table>

                <a-empty v-if="reportData.rows.length === 0" description="No transactions found for this period" class="mt-30" />
            </div>
            <a-empty v-if="!generated && !loading" description="Select filters and click Generate" class="mt-40" />
        </a-spin>
    </a-card>

    <!-- Sale Detail Modal -->
    <a-modal v-model:open="saleModalVisible" :title="'Invoice — ' + (selectedSale ? selectedSale.reference : '')" width="720px" :footer="null">
        <div v-if="selectedSale">
            <a-descriptions :column="2" size="small" style="margin-bottom:16px">
                <a-descriptions-item label="Invoice">{{ selectedSale.reference }}</a-descriptions-item>
                <a-descriptions-item label="Date">{{ selectedSale.date }}</a-descriptions-item>
                <a-descriptions-item label="Total"><b class="text-blue-600">PKR {{ fmt(selectedSale.debit) }}</b></a-descriptions-item>
            </a-descriptions>

            <table class="cl-items-table" style="margin-top:0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Code</th>
                        <th class="cl-right">Qty</th>
                        <th class="cl-right">Unit Price</th>
                        <th class="cl-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in selectedSale.items" :key="idx">
                        <td class="cl-idx">{{ idx + 1 }}</td>
                        <td class="cl-name">{{ item.name }}</td>
                        <td><span v-if="item.item_code" class="cl-code">{{ item.item_code }}</span></td>
                        <td class="cl-right cl-qty">{{ item.qty }}</td>
                        <td class="cl-right">{{ fmt(item.unit_price) }}</td>
                        <td class="cl-right cl-subtotal">{{ fmt(item.subtotal) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="cl-total-row">
                        <td colspan="5" class="cl-right" style="font-weight:700;color:#334155;padding:10px;">Total</td>
                        <td class="cl-right cl-subtotal" style="padding:10px;">{{ fmt(selectedSale.debit) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </a-modal>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { PrinterOutlined, SearchOutlined, ShoppingOutlined, SyncOutlined } from '@ant-design/icons-vue';
import { message, notification } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader, PrinterOutlined, SearchOutlined, ShoppingOutlined, SyncOutlined },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading   = ref(false);
        const generated = ref(false);
        const backfilling = ref(false);
        const customers = ref([]);
        const filters   = ref({ user_id: null, date_from: dayjs().startOf('year'), date_to: dayjs() });
        const reportData = ref({ rows: [], opening_balance: 0, customer: null, date_from: '', date_to: '' });

        // Sale detail modal
        const saleModalVisible = ref(false);
        const selectedSale     = ref(null);

        const openSaleDetail = (record) => {
            selectedSale.value = record;
            saleModalVisible.value = true;
        };

        // Expandable rows — only for Sale type with items
        const expandedRowKeys = ref([]);
        const expandable = computed(() => ({
            expandedRowKeys: expandedRowKeys.value,
            onExpand: (expanded, record) => {
                const key = tableRows.value.indexOf(record);
                if (expanded) {
                    expandedRowKeys.value = [...expandedRowKeys.value, key];
                } else {
                    expandedRowKeys.value = expandedRowKeys.value.filter(k => k !== key);
                }
            },
            rowExpandable: (record) => record.type === 'Sale' && record.items && record.items.length > 0,
        }));

        const columns = [
            { title: 'Date',      dataIndex: 'date',      key: 'date',      width: 110 },
            { title: 'Reference', key: 'reference', width: 200 },
            { title: 'Type',      key: 'type',  width: 150 },
            { title: 'Debit',     key: 'debit', width: 140, align: 'right' },
            { title: 'Credit',    key: 'credit',width: 140, align: 'right' },
            { title: 'Balance',   key: 'running_balance', width: 160, align: 'right' },
        ];

        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const filterOption = (input, option) => option.children?.()[0]?.children?.toLowerCase().includes(input.toLowerCase());
        const typeColor = (t) => ({ 'Sale': 'blue', 'Sales Return': 'orange', 'Payment Received': 'green' })[t] || 'default';

        const openingBalance = computed(() => reportData.value.opening_balance ?? 0);
        const totalDebit  = computed(() => reportData.value.rows.reduce((s, r) => s + +r.debit, 0));
        const totalCredit = computed(() => reportData.value.rows.reduce((s, r) => s + +r.credit, 0));
        const closingBalance = computed(() => openingBalance.value + totalDebit.value - totalCredit.value);
        const tableRows = computed(() => {
            const ob = openingBalance.value;
            const obRow = {
                date: reportData.value.date_from,
                reference: '—',
                type: 'Opening Balance',
                debit: ob > 0 ? ob : 0,
                credit: ob < 0 ? Math.abs(ob) : 0,
                running_balance: ob,
                items: [],
                _is_opening: true,
            };
            return [obRow, ...reportData.value.rows];
        });

        const loadCustomers = async () => {
            const res = await axiosAdmin.get('customers?limit=10000');
            customers.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
        };

        const load = async () => {
            loading.value = true;
            generated.value = true;
            expandedRowKeys.value = [];
            try {
                const res = await axiosAdmin.get('accounting/reports/customer-ledger', {
                    params: {
                        user_id:   filters.value.user_id || undefined,
                        date_from: filters.value.date_from?.format('YYYY-MM-DD'),
                        date_to:   filters.value.date_to?.format('YYYY-MM-DD'),
                    }
                });
                reportData.value = res.data;
            } catch (e) { message.error('Failed to load ledger'); }
            finally { loading.value = false; }
        };

        const backfillJEs = async () => {
            backfilling.value = true;
            try {
                const res = await axiosAdmin.post('accounting/backfill-jes');
                const d = res.data;
                notification.success({
                    message: 'JE Backfill Complete',
                    description: `Generated: ${d.generated} | Skipped (zero): ${d.skipped} | Failed: ${d.failed?.length ?? 0}`,
                    duration: 8,
                });
                if (d.warnings && d.warnings.length) {
                    notification.warning({
                        message: 'Backfill Warnings',
                        description: d.warnings.slice(0, 5).join('\n'),
                        duration: 0,
                        style: { whiteSpace: 'pre-line' },
                    });
                }
            } catch (e) { message.error('Backfill failed: ' + (e.response?.data?.message || e.message)); }
            finally { backfilling.value = false; }
        };

        const print = () => window.print();
        onMounted(loadCustomers);

        return {
            loading, generated, backfilling, customers, filters, reportData, tableRows, columns,
            expandable, expandedRowKeys,
            fmt, formatDate, filterOption, typeColor,
            openingBalance, totalDebit, totalCredit, closingBalance,
            load, print, backfillJEs,
            saleModalVisible, selectedSale, openSaleDetail,
        };
    }
});
</script>

<style scoped>
.cl-expandable-row { cursor: pointer; }
.cl-items-panel { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 16px; margin: 4px 0; }
.cl-items-title { font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.cl-items-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.cl-items-table thead tr { background: #e0f2fe; }
.cl-items-table th { padding: 7px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #0369a1; text-transform: uppercase; border-bottom: 2px solid #bae6fd; }
.cl-items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
.cl-items-table tbody tr:last-child td { border-bottom: none; }
.cl-items-table tbody tr:hover { background: #f0f9ff; }
.cl-total-row { background: #f1f5f9; }
.cl-right { text-align: right; }
.cl-idx { color: #94a3b8; width: 24px; font-size: 11px; }
.cl-name { font-weight: 600; color: #1e293b; }
.cl-code { background: #f1f5f9; border-radius: 4px; padding: 1px 6px; font-size: 11px; font-family: monospace; color: #475569; }
.cl-qty { font-weight: 700; color: #0ea5e9; }
.cl-subtotal { font-weight: 700; color: #15803d; }
:deep(.ob-row > td) { background: #fefce8 !important; }
</style>
