<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Supplier Ledger" class="p-0">
                <template #extra>
                    <a-button @click="print"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Supplier Ledger</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <a-row :gutter="16" class="mb-20" align="middle">
            <a-col :span="7">
                <label class="block text-xs text-gray-500 mb-1">Supplier (optional — leave blank for all)</label>
                <a-select v-model:value="filters.user_id" style="width:100%" show-search :filter-option="filterOption" placeholder="All Suppliers" allow-clear>
                    <a-select-option v-for="s in suppliers" :key="s.xid" :value="s.xid">{{ s.name }}</a-select-option>
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
                <a-button v-if="generated" @click="backfillJEs" :loading="backfilling" title="Generate missing Journal Entries for all past purchases">
                    <SyncOutlined /> Backfill JEs
                </a-button>
            </a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area" v-if="generated">
                <div class="text-center mb-20">
                    <h2 style="margin:0">Supplier Ledger</h2>
                    <h3 v-if="reportData.supplier" style="margin:4px 0;color:#333">{{ reportData.supplier.name }}</h3>
                    <p style="margin:0;color:#666">{{ formatDate(reportData.date_from) }} to {{ formatDate(reportData.date_to) }}</p>
                </div>

                <!-- Warning: GRNs without unit prices detected -->
                <a-alert v-if="hasZeroTotalGrns" type="warning" show-icon style="margin-bottom:14px"
                    message="Some GRN/Purchase orders had ₨0 recorded — amounts computed from product purchase prices. Update item unit prices or product purchase prices for accurate records." />

                <a-table
                    :dataSource="tableRows"
                    :columns="columns"
                    :pagination="false"
                    size="middle"
                    :rowKey="(r, i) => i"
                    :scroll="{ x: 860 }"
                    :rowClassName="(r) => r._is_opening ? 'ob-row' : ((r.items && r.items.length) ? 'sl-expandable-row' : '')"
                    :expandable="expandable"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'reference'">
                            <span v-if="record._is_opening" style="color:#94a3b8;font-style:italic">—</span>
                            <span v-else>
                                {{ record.reference }}
                                <span v-if="record.items && record.items.length" style="font-size:10px;color:#94a3b8;margin-left:4px;">({{ record.items.length }} item{{ record.items.length > 1 ? 's' : '' }})</span>
                            </span>
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
                            <div v-if="+record.credit !== 0">
                                <span class="text-green-600">{{ fmt(record.credit) }}</span>
                                <a-tooltip v-if="record.recorded_amt !== null && record.recorded_amt !== undefined && record.recorded_amt == 0 && record.credit > 0"
                                    title="Amount computed from product purchase prices (GRN had no unit prices)">
                                    <span class="sl-computed-badge">~est.</span>
                                </a-tooltip>
                            </div>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                        <template v-if="column.key === 'running_balance'">
                            <span :class="record.running_balance >= 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'">
                                {{ fmt(Math.abs(record.running_balance)) }} {{ record.running_balance >= 0 ? 'Payable' : 'Advance' }}
                            </span>
                        </template>
                    </template>

                    <!-- Expandable row: GRN/Purchase item details -->
                    <template #expandedRowRender="{ record }">
                        <div v-if="record.items && record.items.length" class="sl-items-panel">
                            <div class="sl-items-title"><ShoppingCartOutlined /> Items received — {{ record.reference }}</div>
                            <div v-if="record.recorded_amt == 0 && record.credit > 0" class="sl-est-notice">
                                ⚠ GRN had no unit prices. Amount estimated from product purchase prices (PKR {{ fmt(record.credit) }}). Enter unit prices on the GRN to correct this.
                            </div>
                            <table class="sl-items-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Code</th>
                                        <th class="sl-right">Qty</th>
                                        <th class="sl-right">Unit Price</th>
                                        <th class="sl-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in record.items" :key="idx">
                                        <td class="sl-idx">{{ idx + 1 }}</td>
                                        <td class="sl-name">{{ item.name }}</td>
                                        <td><span v-if="item.item_code" class="sl-code">{{ item.item_code }}</span></td>
                                        <td class="sl-right sl-qty">{{ item.qty }}</td>
                                        <td class="sl-right" :style="item.unit_price == 0 ? 'color:#f59e0b' : ''">
                                            {{ item.unit_price > 0 ? fmt(item.unit_price) : '—' }}
                                            <span v-if="item.unit_price == 0" style="font-size:10px;color:#f59e0b">(see product)</span>
                                        </td>
                                        <td class="sl-right sl-subtotal">{{ item.subtotal > 0 ? fmt(item.subtotal) : '—' }}</td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="record.credit > 0">
                                    <tr class="sl-total-row">
                                        <td colspan="5" class="sl-right" style="font-weight:700;color:#334155;padding:8px 10px;">
                                            {{ record.recorded_amt == 0 ? 'Estimated Total' : 'Total' }}
                                        </td>
                                        <td class="sl-right sl-subtotal" style="padding:8px 10px;">{{ fmt(record.credit) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-else style="color:#94a3b8;font-size:12px;padding:8px 16px;">No item details available.</div>
                    </template>

                    <template #summary>
                        <a-table-summary-row>
                            <a-table-summary-cell :index="0" :col-span="3"><b>CLOSING BALANCE</b></a-table-summary-cell>
                            <a-table-summary-cell :index="3" align="right"><b class="text-blue-600">{{ fmt(openingBalance < 0 ? Math.abs(openingBalance) + totalDebit : totalDebit) }}</b></a-table-summary-cell>
                            <a-table-summary-cell :index="4" align="right"><b class="text-green-600">{{ fmt(openingBalance > 0 ? openingBalance + totalCredit : totalCredit) }}</b></a-table-summary-cell>
                            <a-table-summary-cell :index="5" align="right">
                                <b :class="closingBalance >= 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ fmt(Math.abs(closingBalance)) }} {{ closingBalance >= 0 ? ' Payable' : ' Advance' }}
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
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { PrinterOutlined, SearchOutlined, ShoppingCartOutlined, SyncOutlined } from '@ant-design/icons-vue';
import { message, notification } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader, PrinterOutlined, SearchOutlined, ShoppingCartOutlined, SyncOutlined },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading    = ref(false);
        const generated  = ref(false);
        const backfilling = ref(false);
        const suppliers  = ref([]);
        const filters    = ref({ user_id: null, date_from: dayjs().startOf('year'), date_to: dayjs() });
        const reportData = ref({ rows: [], opening_balance: 0, supplier: null, date_from: '', date_to: '' });

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
            rowExpandable: (record) => (record.type === 'Purchase' || record.type === 'GRN') && record.items && record.items.length > 0,
        }));

        const columns = [
            { title: 'Date',      key: 'date', dataIndex: 'date', width: 110 },
            { title: 'Reference', key: 'reference', width: 200 },
            { title: 'Type',      key: 'type',  width: 120 },
            { title: 'Debit',     key: 'debit', width: 140, align: 'right' },
            { title: 'Credit',    key: 'credit', width: 160, align: 'right' },
            { title: 'Balance',   key: 'running_balance', width: 160, align: 'right' },
        ];

        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const filterOption = (input, option) => option.children?.()[0]?.children?.toLowerCase().includes(input.toLowerCase());
        const typeColor = (t) => ({ 'Purchase': 'blue', 'GRN': 'purple', 'Purchase Return': 'orange', 'Payment Made': 'green' })[t] || 'default';

        const openingBalance = computed(() => reportData.value.opening_balance ?? 0);
        const totalDebit  = computed(() => reportData.value.rows.reduce((s, r) => s + +r.debit, 0));
        const totalCredit = computed(() => reportData.value.rows.reduce((s, r) => s + +r.credit, 0));
        const closingBalance = computed(() => openingBalance.value + totalCredit.value - totalDebit.value);

        const hasZeroTotalGrns = computed(() =>
            reportData.value.rows.some(r => (r.type === 'GRN' || r.type === 'Purchase') && r.recorded_amt == 0 && r.credit > 0)
        );

        const tableRows = computed(() => {
            const ob = openingBalance.value;
            const obRow = {
                date: reportData.value.date_from,
                reference: '—',
                type: 'Opening Balance',
                debit: ob < 0 ? Math.abs(ob) : 0,
                credit: ob > 0 ? ob : 0,
                running_balance: ob,
                items: [],
                _is_opening: true,
            };
            return [obRow, ...reportData.value.rows];
        });

        const loadSuppliers = async () => {
            const res = await axiosAdmin.get('suppliers?limit=10000');
            suppliers.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
        };

        const load = async () => {
            loading.value = true;
            generated.value = true;
            expandedRowKeys.value = [];
            try {
                const res = await axiosAdmin.get('accounting/reports/supplier-ledger', {
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
                    description: `Generated: ${d.generated} | Skipped (zero-value): ${d.skipped} | Failed: ${d.failed?.length ?? 0}`,
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
        onMounted(loadSuppliers);

        return {
            loading, generated, backfilling, suppliers, filters, reportData, tableRows, columns,
            expandable, expandedRowKeys, hasZeroTotalGrns,
            fmt, formatDate, filterOption, typeColor,
            openingBalance, totalDebit, totalCredit, closingBalance,
            load, print, backfillJEs,
        };
    }
});
</script>

<style scoped>
.sl-expandable-row { cursor: pointer; }
.sl-items-panel { background: #fefce8; border: 1px solid #fde68a; border-radius: 6px; padding: 14px 16px; margin: 4px 0; }
.sl-items-title { font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.sl-est-notice { font-size: 12px; color: #92400e; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; }
.sl-items-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.sl-items-table thead tr { background: #fef9c3; }
.sl-items-table th { padding: 7px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #713f12; text-transform: uppercase; border-bottom: 2px solid #fde68a; }
.sl-items-table td { padding: 8px 10px; border-bottom: 1px solid #fef9c3; }
.sl-items-table tbody tr:last-child td { border-bottom: none; }
.sl-items-table tbody tr:hover { background: #fef3c7; }
.sl-total-row { background: #fef9c3; }
.sl-right { text-align: right; }
.sl-idx { color: #94a3b8; width: 24px; font-size: 11px; }
.sl-name { font-weight: 600; color: #1e293b; }
.sl-code { background: #fef9c3; border-radius: 4px; padding: 1px 6px; font-size: 11px; font-family: monospace; color: #713f12; }
.sl-qty { font-weight: 700; color: #7c3aed; }
.sl-subtotal { font-weight: 700; color: #dc2626; }
.sl-computed-badge { display: inline-block; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 4px; font-size: 10px; color: #92400e; padding: 0 4px; margin-left: 4px; }
:deep(.ob-row > td) { background: #fefce8 !important; }
</style>
