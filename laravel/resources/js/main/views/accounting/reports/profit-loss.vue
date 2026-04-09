<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Profit & Loss Statement" class="p-0">
                <template #extra>
                    <a-button @click="print"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Profit & Loss</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <!-- Filters -->
        <a-row :gutter="16" class="mb-20" align="middle">
            <a-col :span="6"><label class="block text-xs text-gray-500 mb-1">From Date</label><a-date-picker v-model:value="filters.date_from" style="width:100%" /></a-col>
            <a-col :span="6"><label class="block text-xs text-gray-500 mb-1">To Date</label><a-date-picker v-model:value="filters.date_to" style="width:100%" /></a-col>
            <a-col :span="4"><a-button type="primary" @click="load" class="mt-20" :loading="loading"><SearchOutlined /> Generate</a-button></a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area">
                <div class="text-center mb-20">
                    <h2 style="margin:0">Profit &amp; Loss Statement</h2>
                    <p style="margin:0;color:#666">{{ formatDate(data.date_from) }} to {{ formatDate(data.date_to) }}</p>
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
                        <template v-if="column.key === 'net'">
                            <span v-if="record._isSubtotal" :class="record._class" style="font-weight:700">{{ fmt(record.net) }}</span>
                            <span v-else-if="+record.net !== 0" :class="record._class">{{ fmt(Math.abs(record.net)) }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                    </template>
                    <template #summary>
                        <a-table-summary-row>
                            <a-table-summary-cell :index="0" :col-span="2"><b>NET PROFIT / LOSS</b></a-table-summary-cell>
                            <a-table-summary-cell :index="1"></a-table-summary-cell>
                            <a-table-summary-cell :index="2">
                                <b :class="data.net_profit >= 0 ? 'text-green-600' : 'text-red-600'">{{ fmt(data.net_profit) }}</b>
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
        const filters = ref({ date_from: dayjs().startOf('year'), date_to: dayjs() });
        const data = ref({ data: [], total_revenue: 0, total_cogs: 0, gross_profit: 0, total_expenses: 0, net_profit: 0, date_from: '', date_to: '' });

        const columns = [
            { title: 'Code', dataIndex: 'account_code', key: 'account_code', width: 110 },
            { title: 'Account Name', key: 'account_name' },
            { title: 'Type', key: 'account_type', width: 130 },
            { title: 'Amount (PKR)', key: 'net', width: 160, align: 'right' },
        ];

        const typeColor = (t) => ({ Income: 'green', COGS: 'volcano', Expense: 'orange' })[t] || 'default';
        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        const tableRows = computed(() => {
            const rows = [];
            const incomeRows  = (data.value.data || []).filter(r => r.account_type === 'Income');
            const cogsRows    = (data.value.data || []).filter(r => r.account_type === 'COGS');
            const expenseRows = (data.value.data || []).filter(r => r.account_type === 'Expense');

            incomeRows.forEach(r => rows.push({ ...r, _class: 'text-green-600' }));
            rows.push({ account_code: '', account_name: 'Total Revenue', account_type: '', net: data.value.total_revenue, _isSubtotal: true, _class: 'text-green-600' });

            cogsRows.forEach(r => rows.push({ ...r, _class: 'text-orange-500' }));
            rows.push({ account_code: '', account_name: 'Total Cost of Goods Sold', account_type: '', net: data.value.total_cogs, _isSubtotal: true, _class: 'text-orange-500' });

            rows.push({ account_code: '', account_name: 'Gross Profit', account_type: '', net: data.value.gross_profit, _isSubtotal: true, _class: data.value.gross_profit >= 0 ? 'text-blue-600' : 'text-red-600' });

            expenseRows.forEach(r => rows.push({ ...r, _class: 'text-purple-600' }));
            rows.push({ account_code: '', account_name: 'Total Operating Expenses', account_type: '', net: data.value.total_expenses, _isSubtotal: true, _class: 'text-purple-600' });

            return rows;
        });

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/profit-loss', {
                    params: { date_from: filters.value.date_from?.format('YYYY-MM-DD'), date_to: filters.value.date_to?.format('YYYY-MM-DD') },
                });
                data.value = res.data;
            } catch (e) {} finally { loading.value = false; }
        };

        const print = () => window.print();

        onMounted(load);
        return { loading, filters, data, columns, tableRows, fmt, formatDate, typeColor, load, print };
    }
});
</script>
