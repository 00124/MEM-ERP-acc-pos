<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="GRN" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t('menu.dashboard') }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t('menu.purchases') }}</a-breadcrumb-item>
                <a-breadcrumb-item>GRN</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">
                <a-space>
                    <template
                        v-if="
                            permsArray.includes('grn_create') ||
                            permsArray.includes('admin')
                        "
                    >
                        <router-link :to="{ name: 'admin.stock.grn.create' }">
                            <a-button type="primary">
                                <PlusOutlined />
                                {{ $t('grn.add') || 'Add GRN' }}
                            </a-button>
                        </router-link>
                    </template>
                </a-space>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col :xs="24" :sm="24" :md="12" :lg="8" :xl="6">
                        <a-input-search
                            style="width: 100%"
                            v-model:value="filters.searchString"
                            show-search
                            :placeholder="$t('common.placeholder_search_text', [$t('stock.invoice_number')])"
                        />
                    </a-col>
                    <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="6">
                        <a-select
                            v-model:value="filters.user_id"
                            :placeholder="$t('common.select_default_text', [$t('grn.supplier')])"
                            :allowClear="true"
                            style="width: 100%"
                            optionFilterProp="title"
                            show-search
                        >
                            <a-select-option
                                v-for="user in users"
                                :key="user.xid"
                                :title="user.name"
                                :value="user.xid"
                            >
                                {{ user.name }}
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="6">
                        <DateRangePicker
                            ref="searchDateRangePicker"
                            @dateTimeChanged="(changedDateTime) => (filters.dates = changedDateTime)"
                        />
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <a-row>
            <a-col :span="24">
                <a-tabs v-model:activeKey="filters.payment_status">
                    <a-tab-pane key="all" :tab="`${$t('common.all')} GRN`" />
                </a-tabs>
            </a-col>
        </a-row>
        <OrderTable
            ref="orderTableRef"
            orderType="grn"
            :filters="filters"
            tableSize="middle"
            :bordered="true"
            :selectable="false"
        />
    </admin-page-table-content>
</template>
<script>
import { onMounted, ref } from "vue";
import { PlusOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";
import OrderTable from "../../../components/order/OrderTable.vue";
import DateRangePicker from "../../../../common/components/common/calendar/DateRangePicker.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default {
    components: {
        PlusOutlined,
        OrderTable,
        DateRangePicker,
        AdminPageHeader,
    },
    setup() {
        const { permsArray } = common();
        const users = ref([]);
        const orderTableRef = ref(null);
        const filters = ref({
            payment_status: "all",
            user_id: undefined,
            dates: [],
            searchColumn: "invoice_number",
            searchString: "",
        });

        onMounted(() => {
            axiosAdmin.get("suppliers?limit=10000").then((res) => {
                users.value = res.data;
            });
        });

        return {
            permsArray,
            users,
            filters,
            orderTableRef,
        };
    },
};
</script>
