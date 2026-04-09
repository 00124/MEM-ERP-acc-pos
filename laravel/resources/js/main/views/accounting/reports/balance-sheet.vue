<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Balance Sheet" class="p-0">
                <template #extra>
                    <a-button @click="print"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Balance Sheet</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <!-- Filters -->
        <a-row :gutter="16" class="mb-20" align="middle">
            <a-col :span="6"><label class="block text-xs text-gray-500 mb-1">As of Date</label><a-date-picker v-model:value="filters.as_of" style="width:100%" /></a-col>
            <a-col :span="4"><a-button type="primary" @click="load" class="mt-20" :loading="loading"><SearchOutlined /> Generate</a-button></a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area">
                <div class="text-center mb-20">
                    <h2 style="margin:0">Balance Sheet</h2>
                    <p style="margin:0;color:#666">As of {{ formatDate(data.as_of) }}</p>
                </div>

                <a-table :dataSource="tableRows" :columns="columns" :pagination="false" size="middle" rowKey="account_code" :scroll="{ x: 700 }">
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'account_name'">
                            <span :style="{ fontWeight: record._isSubtotal ? '700' : 'normal', fontStyle: record._isSubtotal ? 'italic' : 'normal' }">
                                {{ record.account_name }}
                            </span>
                        </template>
                        <template v-if="column.key === 'account_type'">
                            <a-tag v-if="!record._isSubtotal" :color="typeColor(record.account_type)">{{ record.account_type }}</a-tag>
                        </template>
                        <template v-if="column.key === 'balance'">
                            <span v-if="record._isSubtotal" :class="record._class" style="font-weight:700">{{ fmt(record.balance) }}</span>
                            <span v-else-if="+record.balance !== 0" :class="record._class">{{ fmt(Math.abs(record.balance)) }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                    </template>
                    <template #summary>
                        <a-table-summary-row>
                            <a-table-summary-cell :index="0" :col-span="2"><b>TOTAL LIABILITIES + EQUITY</b></a-table-summary-cell>
                            <a-table-summary-cell :index="1"></a-table-summary-cell>
                            <a-table-summary-cell :index="2">
                                <b :class="isBalanced ? 'text-green-600' : 'text-red-600'">
                                    {{ fmt(+data.total_liabilities + +data.total_equity) }}
                                    <span v-if="isBalanced" style="font-size:11px;margin-left:6px">✓ Balanced</span>
                                    <span v-else style="font-size:11px;margin-left:6px">⚠ Unbalanced</span>
                                </b>
                            </a-table-summary-cell>
                        </a-table-summary-row>
                    </template>
                </a-table>
            </div>
        </a-spin>
    </a-card>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { PrinterOutlined, SearchOutlined } from '@ant-design/icons-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader, PrinterOutlined, SearchOutlined },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading = ref(false);
        const filters = ref({ as_of: dayjs() });
        const data = ref({ data: [], total_assets: 0, total_liabilities: 0, total_equity: 0, as_of: '' });

        const columns = [
            { title: 'Code', dataIndex: 'account_code', key: 'account_code', width: 110 },
            { title: 'Account Name', key: 'account_name' },
            { title: 'Type', key: 'account_type', width: 120 },
            { title: 'Balance (PKR)', key: 'balance', width: 160, align: 'right' },
        ];

        const typeColor = (t) => ({ Asset: 'blue', Liability: 'red', Equity: 'purple' })[t] || 'default';
        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        const isBalanced = computed(() =>
            Math.abs((+data.value.total_assets) - ((+data.value.total_liabilities) + (+data.value.total_equity))) < 1
        );

        const tableRows = computed(() => {
            const rows = [];
            const assetRows     = (data.value.data || []).filter(r => r.account_type === 'Asset');
            const liabilityRows = (data.value.data || []).filter(r => r.account_type === 'Liability');
            const equityRows    = (data.value.data || []).filter(r => r.account_type === 'Equity');

            assetRows.forEach(r => rows.push({ ...r, _class: 'text-blue-600' }));
            rows.push({ account_code: '', account_name: 'Total Assets', account_type: '', balance: data.value.total_assets, _isSubtotal: true, _class: 'text-blue-600' });

            liabilityRows.forEach(r => rows.push({ ...r, _class: 'text-red-600' }));
            rows.push({ account_code: '', account_name: 'Total Liabilities', account_type: '', balance: data.value.total_liabilities, _isSubtotal: true, _class: 'text-red-600' });

            equityRows.forEach(r => rows.push({ ...r, _class: 'text-purple-600' }));
            rows.push({ account_code: '', account_name: 'Total Equity', account_type: '', balance: data.value.total_equity, _isSubtotal: true, _class: 'text-purple-600' });

            return rows;
        });

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/balance-sheet', {
                    params: { as_of: filters.value.as_of?.format('YYYY-MM-DD') },
                });
                data.value = res.data;
            } catch (e) {} finally { loading.value = false; }
        };

        const print = () => window.print();

        onMounted(load);
        return { loading, filters, data, columns, tableRows, isBalanced, fmt, formatDate, typeColor, load, print };
    }
});
</script>
