<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Cheque Usage Report" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.cheque-books.index' }">Cheque Books</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Report</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :xs="24" :sm="12" :md="6">
                <a-select
                    v-model:value="filters.cheque_book_id"
                    placeholder="All Cheque Books"
                    :allowClear="true"
                    style="width: 100%"
                    @change="fetchReport"
                >
                    <a-select-option v-for="b in books" :key="b.xid" :value="b.xid">
                        {{ b.bank_name }} – {{ b.book_no }}
                    </a-select-option>
                </a-select>
            </a-col>
            <a-col :xs="24" :sm="12" :md="5">
                <a-select
                    v-model:value="filters.status"
                    placeholder="All Statuses"
                    :allowClear="true"
                    style="width: 100%"
                    @change="fetchReport"
                >
                    <a-select-option value="unused">Unused</a-select-option>
                    <a-select-option value="issued">Issued</a-select-option>
                    <a-select-option value="cancelled">Cancelled</a-select-option>
                </a-select>
            </a-col>
            <a-col :xs="24" :sm="24" :md="8">
                <a-range-picker
                    v-model:value="filters.dateRange"
                    style="width: 100%"
                    valueFormat="YYYY-MM-DD"
                    @change="onDateChange"
                />
            </a-col>
            <a-col :xs="24" :sm="12" :md="5" style="text-align: right">
                <a-button @click="clearFilters">Clear Filters</a-button>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <!-- Summary Cards -->
        <a-row :gutter="[16, 16]" style="margin-bottom: 16px" v-if="!loading">
            <a-col :xs="12" :sm="6" :md="6">
                <a-card size="small">
                    <a-statistic title="Total Cheques" :value="cheques.length" />
                </a-card>
            </a-col>
            <a-col :xs="12" :sm="6" :md="6">
                <a-card size="small">
                    <a-statistic title="Issued" :value="cheques.filter(c => c.status === 'issued').length" value-style="color:#52c41a" />
                </a-card>
            </a-col>
            <a-col :xs="12" :sm="6" :md="6">
                <a-card size="small">
                    <a-statistic title="Unused" :value="cheques.filter(c => c.status === 'unused').length" value-style="color:#1890ff" />
                </a-card>
            </a-col>
            <a-col :xs="12" :sm="6" :md="6">
                <a-card size="small">
                    <a-statistic title="Total Issued (PKR)" :value="formatAmount(totalAmount)" />
                </a-card>
            </a-col>
        </a-row>

        <a-table
            :dataSource="cheques"
            :columns="columns"
            :loading="loading"
            rowKey="xid"
            :scroll="{ x: 1000 }"
            :pagination="{ pageSize: 25 }"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'status'">
                    <a-tag :color="statusColor(record.status)">{{ record.status.toUpperCase() }}</a-tag>
                </template>
                <template v-else-if="column.dataIndex === 'book_info'">
                    <span v-if="record.cheque_book">
                        {{ record.cheque_book.bank_name }} – {{ record.cheque_book.book_no }}
                    </span>
                </template>
                <template v-else-if="column.dataIndex === 'amount'">
                    <span v-if="record.amount">{{ formatAmount(record.amount) }}</span>
                    <span v-else>—</span>
                </template>
                <template v-else-if="column.dataIndex === 'issue_date'">
                    {{ record.issue_date || '—' }}
                </template>
            </template>
        </a-table>
    </admin-page-table-content>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    components: { AdminPageHeader },

    setup() {
        const books = ref([]);
        const cheques = ref([]);
        const loading = ref(false);
        const totalAmount = ref(0);

        const filters = ref({
            cheque_book_id: null,
            status: null,
            dateRange: null,
        });

        const columns = [
            { title: 'Cheque Book', dataIndex: 'book_info', width: 200 },
            { title: 'Cheque #', dataIndex: 'cheque_no', width: 110, sorter: (a, b) => a.cheque_no - b.cheque_no },
            { title: 'Status', dataIndex: 'status', width: 100 },
            { title: 'Payee', dataIndex: 'payee', width: 180, ellipsis: true },
            { title: 'Amount (PKR)', dataIndex: 'amount', width: 140 },
            { title: 'Issue Date', dataIndex: 'issue_date', width: 120 },
            { title: 'Remarks', dataIndex: 'remarks', ellipsis: true },
        ];

        const statusColor = (s) => ({ unused: 'blue', issued: 'green', cancelled: 'red' }[s] || 'default');

        const formatAmount = (val) => {
            return Number(val).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        };

        const onDateChange = () => fetchReport();

        const fetchBooks = async () => {
            try {
                const res = await window.axiosAdmin.get('cheque-books');
                books.value = res.data.data;
            } catch {}
        };

        const fetchReport = async () => {
            loading.value = true;
            try {
                const params = {};
                if (filters.value.cheque_book_id) params.cheque_book_id = filters.value.cheque_book_id;
                if (filters.value.status) params.status = filters.value.status;
                if (filters.value.dateRange?.[0]) params.date_from = filters.value.dateRange[0];
                if (filters.value.dateRange?.[1]) params.date_to = filters.value.dateRange[1];

                const res = await window.axiosAdmin.get('cheque-books/report', { params });
                cheques.value = res.data.data;
                totalAmount.value = res.data.total_amount || 0;
            } catch {
                message.error('Failed to load report.');
            } finally {
                loading.value = false;
            }
        };

        const clearFilters = () => {
            filters.value = { cheque_book_id: null, status: null, dateRange: null };
            fetchReport();
        };

        onMounted(() => {
            fetchBooks();
            fetchReport();
        });

        return {
            books, cheques, loading, totalAmount, filters, columns,
            statusColor, formatAmount, fetchReport, onDateChange, clearFilters,
        };
    },
});
</script>
