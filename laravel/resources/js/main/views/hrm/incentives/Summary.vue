<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Monthly Incentive Summary" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t("menu.dashboard") }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t("menu.hrm") }}</a-breadcrumb-item>
                <a-breadcrumb-item>Incentive Summary</a-breadcrumb-item>
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
                    <a-col :xs="12" :sm="12" :md="6" :lg="6">
                        <a-select
                            v-model:value="selectedMonth"
                            style="width: 100%"
                            placeholder="Month"
                            @change="fetchSummary"
                        >
                            <a-select-option v-for="m in months" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="12" :sm="12" :md="6" :lg="6">
                        <a-select
                            v-model:value="selectedYear"
                            style="width: 100%"
                            placeholder="Year"
                            @change="fetchSummary"
                        >
                            <a-select-option v-for="y in years" :key="y" :value="y">{{ y }}</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="24" :md="6" :lg="6">
                        <router-link :to="{ name: 'admin.hrm.incentives.index' }">
                            <a-button>All Incentives</a-button>
                        </router-link>
                    </a-col>
                </a-row>
            </admin-page-filters>

            <admin-page-table-content>
                <!-- Summary Stats -->
                <a-row :gutter="16" style="margin-bottom: 16px">
                    <a-col :xs="12" :sm="8" :md="6">
                        <a-statistic
                            title="Total Incentives"
                            :value="formatAmountCurrency(grandTotal)"
                            style="background:#fff; padding:16px; border-radius:8px"
                        />
                    </a-col>
                    <a-col :xs="12" :sm="8" :md="6">
                        <a-statistic
                            title="Employees Earning"
                            :value="summaryData.length"
                            style="background:#fff; padding:16px; border-radius:8px"
                        />
                    </a-col>
                </a-row>

                <a-table
                    :columns="columns"
                    :data-source="summaryData"
                    :loading="loading"
                    :pagination="false"
                    row-key="user_id"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.dataIndex === 'name'">
                            <strong>{{ record.name }}</strong>
                        </template>
                        <template v-if="column.dataIndex === 'total_incentive'">
                            <a-tag color="green">{{ formatAmountCurrency(record.total_incentive) }}</a-tag>
                        </template>
                        <template v-if="column.dataIndex === 'total_sales'">
                            {{ record.total_sales }}
                        </template>
                        <template v-if="column.dataIndex === 'incentive_type'">
                            <a-tag :color="typeColor(record.incentive_type)">{{ record.incentive_type }}</a-tag>
                        </template>
                    </template>
                </a-table>
            </admin-page-table-content>
        </a-col>
    </a-row>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from "vue";
import PayrollSidebar from "../payrolls/PayrollSidebar.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import common from "../../../../common/composable/common";
import dayjs from "dayjs";

export default defineComponent({
    components: { PayrollSidebar, AdminPageHeader },
    setup() {
        const { formatAmountCurrency } = common();
        const summaryData = ref([]);
        const loading = ref(false);
        const now = dayjs();
        const selectedMonth = ref(now.month() + 1);
        const selectedYear = ref(now.year());

        const months = [
            { value: 1, label: 'January' }, { value: 2, label: 'February' },
            { value: 3, label: 'March' }, { value: 4, label: 'April' },
            { value: 5, label: 'May' }, { value: 6, label: 'June' },
            { value: 7, label: 'July' }, { value: 8, label: 'August' },
            { value: 9, label: 'September' }, { value: 10, label: 'October' },
            { value: 11, label: 'November' }, { value: 12, label: 'December' },
        ];

        const years = Array.from({ length: 5 }, (_, i) => now.year() - i);

        const columns = [
            { title: "Employee", dataIndex: "name" },
            { title: "Incentive Type", dataIndex: "incentive_type" },
            { title: "Total Sales", dataIndex: "total_sales" },
            { title: "Total Incentive", dataIndex: "total_incentive" },
        ];

        const grandTotal = computed(() =>
            summaryData.value.reduce((sum, r) => sum + parseFloat(r.total_incentive ?? 0), 0)
        );

        const typeColor = (type) => {
            if (type === 'percentage') return 'blue';
            if (type === 'fixed') return 'purple';
            return 'orange';
        };

        const fetchSummary = async () => {
            loading.value = true;
            try {
                const res = await window.axiosAdmin.get(
                    `hrm/incentive-summary?month=${selectedMonth.value}&year=${selectedYear.value}`
                );
                summaryData.value = res.data ?? [];
            } catch (e) {
                console.error(e);
            } finally {
                loading.value = false;
            }
        };

        onMounted(fetchSummary);

        return {
            summaryData, loading, selectedMonth, selectedYear,
            months, years, columns, grandTotal, typeColor,
            formatAmountCurrency, fetchSummary,
        };
    },
});
</script>
