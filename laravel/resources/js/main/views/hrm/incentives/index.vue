<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Incentive Report" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t("menu.dashboard") }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t("menu.hrm") }}</a-breadcrumb-item>
                <a-breadcrumb-item>Incentives</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-row>
        <a-col :xs="24" :sm="24" :md="24" :lg="4" :xl="4" class="bg-setting-sidebar">
            <PayrollSidebar />
        </a-col>
        <a-col :xs="24" :sm="24" :md="24" :lg="20" :xl="20">
            <admin-page-filters>
                <a-row :gutter="[16, 16]">
                    <a-col :xs="24" :sm="24" :md="6" :lg="6">
                        <a-select
                            v-model:value="filters.user_id"
                            :placeholder="$t('common.select_default_text', ['Employee'])"
                            allowClear
                            style="width: 100%"
                            @change="fetchData"
                        >
                            <a-select-option
                                v-for="emp in employees"
                                :key="emp.xid"
                                :value="emp.xid"
                            >
                                {{ emp.name }}
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="24" :md="6" :lg="6">
                        <a-date-picker
                            v-model:value="filters.date_from"
                            placeholder="From Date"
                            style="width: 100%"
                            @change="fetchData"
                        />
                    </a-col>
                    <a-col :xs="24" :sm="24" :md="6" :lg="6">
                        <a-date-picker
                            v-model:value="filters.date_to"
                            placeholder="To Date"
                            style="width: 100%"
                            @change="fetchData"
                        />
                    </a-col>
                    <a-col :xs="24" :sm="24" :md="6" :lg="6">
                        <a-button @click="resetFilters">Reset</a-button>
                        <router-link
                            :to="{ name: 'admin.hrm.incentive_summary.index' }"
                            style="margin-left: 8px"
                        >
                            <a-button type="default">Monthly Summary</a-button>
                        </router-link>
                    </a-col>
                </a-row>
            </admin-page-filters>

            <admin-page-table-content>
                <a-table
                    :columns="columns"
                    :data-source="incentives"
                    :loading="loading"
                    :pagination="pagination"
                    @change="handleTableChange"
                    row-key="xid"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.dataIndex === 'employee'">
                            <span>{{ record.user?.name ?? '-' }}</span>
                        </template>
                        <template v-if="column.dataIndex === 'invoice'">
                            <span>{{ record.order?.invoice_number ?? '-' }}</span>
                        </template>
                        <template v-if="column.dataIndex === 'sale_total'">
                            <span>{{ formatAmountCurrency(record.order?.total ?? 0) }}</span>
                        </template>
                        <template v-if="column.dataIndex === 'amount'">
                            <a-tag color="green">{{ formatAmountCurrency(record.amount) }}</a-tag>
                        </template>
                        <template v-if="column.dataIndex === 'type'">
                            <a-tag :color="typeColor(record.type)">{{ record.type }}</a-tag>
                        </template>
                        <template v-if="column.dataIndex === 'date'">
                            {{ record.date ? formatDate(record.date) : '-' }}
                        </template>
                    </template>
                </a-table>
            </admin-page-table-content>
        </a-col>
    </a-row>
</template>

<script>
import { defineComponent, ref, onMounted, reactive } from "vue";
import PayrollSidebar from "../payrolls/PayrollSidebar.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import common from "../../../../common/composable/common";

export default defineComponent({
    components: { PayrollSidebar, AdminPageHeader },
    setup() {
        const { formatAmountCurrency, appSetting } = common();
        const incentives = ref([]);
        const employees = ref([]);
        const loading = ref(false);
        const pagination = reactive({ current: 1, pageSize: 20, total: 0 });
        const filters = reactive({ user_id: undefined, date_from: undefined, date_to: undefined });

        const columns = [
            { title: "Employee", dataIndex: "employee" },
            { title: "Invoice #", dataIndex: "invoice" },
            { title: "Sale Total", dataIndex: "sale_total" },
            { title: "Incentive Amount", dataIndex: "amount" },
            { title: "Type", dataIndex: "type" },
            { title: "Date", dataIndex: "date" },
        ];

        const typeColor = (type) => {
            if (type === 'percentage') return 'blue';
            if (type === 'fixed') return 'purple';
            return 'orange';
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString();
        };

        const fetchData = async () => {
            loading.value = true;
            try {
                const params = new URLSearchParams();
                params.append('offset', (pagination.current - 1) * pagination.pageSize);
                params.append('limit', pagination.pageSize);
                if (filters.user_id) params.append('user_id', filters.user_id);
                if (filters.date_from) params.append('date_from', filters.date_from.format ? filters.date_from.format('YYYY-MM-DD') : filters.date_from);
                if (filters.date_to) params.append('date_to', filters.date_to.format ? filters.date_to.format('YYYY-MM-DD') : filters.date_to);

                const res = await window.axiosAdmin.get(`hrm/incentives?${params.toString()}`);
                incentives.value = res.data;
                pagination.total = res.meta?.paging?.total ?? res.data.length;
            } catch (e) {
                console.error(e);
            } finally {
                loading.value = false;
            }
        };

        const fetchEmployees = async () => {
            const res = await window.axiosAdmin.get('users?fields=xid,name&user_type=staff_members&limit=10000');
            employees.value = res.data;
        };

        const handleTableChange = (pag) => {
            pagination.current = pag.current;
            fetchData();
        };

        const resetFilters = () => {
            filters.user_id = undefined;
            filters.date_from = undefined;
            filters.date_to = undefined;
            pagination.current = 1;
            fetchData();
        };

        onMounted(() => {
            fetchEmployees();
            fetchData();
        });

        return {
            incentives, employees, loading, pagination, filters, columns,
            typeColor, formatDate, formatAmountCurrency, fetchData,
            handleTableChange, resetFilters,
        };
    },
});
</script>
