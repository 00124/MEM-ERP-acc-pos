<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t('menu.cash_transfers')" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t('menu.dashboard') }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t('menu.cash_transfers') }}</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- Summary KPI Cards -->
    <div class="ct-summary-row">
        <div class="ct-card ct-card--blue">
            <div class="ct-card__icon">⬇️</div>
            <div class="ct-card__body">
                <div class="ct-card__label">HO → Branch (Sent)</div>
                <div class="ct-card__value">{{ formatAmountCurrency(summary.ho_to_branch) }}</div>
            </div>
        </div>
        <div class="ct-card ct-card--green">
            <div class="ct-card__icon">⬆️</div>
            <div class="ct-card__body">
                <div class="ct-card__label">Branch → HO (Received)</div>
                <div class="ct-card__value">{{ formatAmountCurrency(summary.branch_to_ho) }}</div>
            </div>
        </div>
        <div class="ct-card ct-card--orange">
            <div class="ct-card__icon">🔄</div>
            <div class="ct-card__body">
                <div class="ct-card__label">Branch → Branch</div>
                <div class="ct-card__value">{{ formatAmountCurrency(summary.branch_to_branch) }}</div>
            </div>
        </div>
        <div class="ct-card ct-card--purple">
            <div class="ct-card__icon">💰</div>
            <div class="ct-card__body">
                <div class="ct-card__label">Total Transferred</div>
                <div class="ct-card__value">{{ formatAmountCurrency(summary.grand_total) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <admin-page-filters>
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :xs="24" :sm="24" :md="12" :lg="8" :xl="8">
                <a-space>
                    <a-button
                        v-if="permsArray.includes('admin')"
                        type="primary"
                        @click="openCreateModal"
                    >
                        <PlusOutlined />
                        New Transfer
                    </a-button>
                </a-space>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="16" :xl="16">
                <a-row :gutter="[12, 12]" justify="end">
                    <a-col :xs="24" :sm="12" :md="8" :lg="8">
                        <a-select
                            v-model:value="filters.transfer_type"
                            style="width: 100%"
                            placeholder="All Types"
                            allowClear
                            @change="fetchData"
                        >
                            <a-select-option value="ho_to_branch">HO → Branch</a-select-option>
                            <a-select-option value="branch_to_ho">Branch → HO</a-select-option>
                            <a-select-option value="branch_to_branch">Branch → Branch</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="10" :lg="10">
                        <DateRangePicker
                            @dateTimeChanged="(d) => { extraFilters.dates = d; fetchData(); }"
                        />
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <!-- Table -->
    <admin-page-table-content>
        <a-row class="mt-20">
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :columns="columns"
                        :data-source="tableData"
                        :loading="tableLoading"
                        :pagination="pagination"
                        row-key="xid"
                        bordered
                        size="middle"
                        @change="handleTableChange"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'transfer_type'">
                                <a-tag :color="typeColor(record.transfer_type)">
                                    {{ typeLabel(record.transfer_type) }}
                                </a-tag>
                            </template>
                            <template v-if="column.dataIndex === 'from_warehouse_id'">
                                {{ record.from_warehouse?.name ?? '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'to_warehouse_id'">
                                {{ record.to_warehouse?.name ?? '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'amount'">
                                <span class="ct-amount">{{ formatAmountCurrency(record.amount) }}</span>
                            </template>
                            <template v-if="column.dataIndex === 'transfer_date'">
                                {{ formatDate(record.transfer_date) }}
                            </template>
                            <template v-if="column.dataIndex === 'transferred_by'">
                                {{ record.transferred_by_user?.name ?? '-' }}
                            </template>
                        </template>

                        <template #summary>
                            <a-table-summary-row>
                                <a-table-summary-cell :col-span="3">
                                    <strong>Total</strong>
                                </a-table-summary-cell>
                                <a-table-summary-cell>
                                    <strong>{{ formatAmountCurrency(pageTotal) }}</strong>
                                </a-table-summary-cell>
                                <a-table-summary-cell :col-span="3" />
                            </a-table-summary-row>
                        </template>
                    </a-table>
                </div>
            </a-col>
        </a-row>
    </admin-page-table-content>

    <!-- Create Transfer Modal -->
    <a-modal
        v-model:open="modalVisible"
        title="New Cash Transfer"
        :confirm-loading="saving"
        ok-text="Transfer"
        cancel-text="Cancel"
        @ok="submitTransfer"
        @cancel="resetForm"
        width="520px"
    >
        <a-form layout="vertical" class="ct-form">
            <a-form-item label="Transfer Type" required>
                <a-select
                    v-model:value="form.transfer_type"
                    placeholder="Select transfer type"
                    style="width: 100%"
                    @change="onTypeChange"
                >
                    <a-select-option value="ho_to_branch">HO → Branch (Head Office sends cash to Branch)</a-select-option>
                    <a-select-option value="branch_to_ho">Branch → HO (Branch sends cash to Head Office)</a-select-option>
                    <a-select-option value="branch_to_branch">Branch → Branch</a-select-option>
                </a-select>
            </a-form-item>

            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="From Branch" required>
                        <a-select
                            v-model:value="form.from_warehouse_id"
                            placeholder="Select source"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                        >
                            <a-select-option
                                v-for="w in warehouses"
                                :key="w.xid"
                                :value="w.xid"
                                :label="w.name"
                            >{{ w.name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="To Branch" required>
                        <a-select
                            v-model:value="form.to_warehouse_id"
                            placeholder="Select destination"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                        >
                            <a-select-option
                                v-for="w in warehouses"
                                :key="w.xid"
                                :value="w.xid"
                                :label="w.name"
                            >{{ w.name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Amount (PKR)" required>
                        <a-input-number
                            v-model:value="form.amount"
                            :min="1"
                            :precision="2"
                            style="width: 100%"
                            placeholder="0.00"
                        />
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Transfer Date" required>
                        <a-date-picker
                            v-model:value="form.transfer_date"
                            style="width: 100%"
                            format="DD/MM/YYYY"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-form-item label="Notes (Optional)">
                <a-textarea
                    v-model:value="form.notes"
                    :rows="3"
                    placeholder="Reason for transfer, reference, etc."
                />
            </a-form-item>
        </a-form>
    </a-modal>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
import { PlusOutlined } from "@ant-design/icons-vue";
import { message } from "ant-design-vue";
import dayjs from "dayjs";
import AdminPageHeader from "../../../common/layouts/AdminPageHeader.vue";
import DateRangePicker from "../../../common/components/common/calendar/DateRangePicker.vue";
import common from "../../../common/composable/common";

export default {
    components: { PlusOutlined, AdminPageHeader, DateRangePicker },
    setup() {
        const { permsArray, formatAmountCurrency, formatDate } = common();

        const tableData    = ref([]);
        const tableLoading = ref(false);
        const modalVisible = ref(false);
        const saving       = ref(false);
        const warehouses   = ref([]);
        const summary      = reactive({ grand_total: 0, ho_to_branch: 0, branch_to_ho: 0, branch_to_branch: 0 });
        const filters      = reactive({ transfer_type: undefined });
        const extraFilters = reactive({ dates: [] });
        const pagination   = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true });

        const form = reactive({
            transfer_type:    undefined,
            from_warehouse_id: undefined,
            to_warehouse_id:   undefined,
            amount:            null,
            transfer_date:     dayjs(),
            notes:             "",
        });

        const columns = [
            { title: "Ref #",       dataIndex: "reference_number", width: 110 },
            { title: "From Branch", dataIndex: "from_warehouse_id" },
            { title: "To Branch",   dataIndex: "to_warehouse_id" },
            { title: "Amount",      dataIndex: "amount" },
            { title: "Type",        dataIndex: "transfer_type" },
            { title: "Date",        dataIndex: "transfer_date" },
            { title: "Transferred By", dataIndex: "transferred_by" },
        ];

        const pageTotal = computed(() =>
            tableData.value.reduce((s, r) => s + (r.amount || 0), 0)
        );

        const buildListUrl = () => {
            const fields = "id,xid,reference_number,from_warehouse_id,x_from_warehouse_id,fromWarehouse{id,xid,name},to_warehouse_id,x_to_warehouse_id,toWarehouse{id,xid,name},amount,transfer_type,transfer_date,notes,transferred_by,x_transferred_by,transferredBy{id,xid,name}";
            let url = `cash-transfers?fields=${fields}&page=${pagination.current}&limit=${pagination.pageSize}`;
            if (filters.transfer_type) url += `&transfer_type=${filters.transfer_type}`;
            if (extraFilters.dates && extraFilters.dates.length === 2) {
                url += `&dates=${extraFilters.dates[0]},${extraFilters.dates[1]}`;
            }
            return url;
        };

        const fetchData = async () => {
            tableLoading.value = true;
            try {
                const res = await axiosAdmin.get(buildListUrl());
                tableData.value    = res.data ?? [];
                pagination.total   = res.meta?.total ?? tableData.value.length;
            } catch (e) {
                message.error("Failed to load cash transfers.");
            } finally {
                tableLoading.value = false;
            }
            fetchSummary();
        };

        const fetchSummary = async () => {
            try {
                let url = "cash-transfers-summary?";
                if (extraFilters.dates && extraFilters.dates.length === 2) {
                    url += `dates=${extraFilters.dates[0]},${extraFilters.dates[1]}`;
                }
                const res = await axiosAdmin.get(url);
                Object.assign(summary, res);
            } catch (_) {}
        };

        const fetchWarehouses = async () => {
            try {
                const res = await axiosAdmin.get("warehouses?limit=1000&fields=id,xid,name");
                warehouses.value = res.data ?? [];
            } catch (_) {}
        };

        const handleTableChange = (pag) => {
            pagination.current  = pag.current;
            pagination.pageSize = pag.pageSize;
            fetchData();
        };

        const openCreateModal = () => { modalVisible.value = true; };

        const resetForm = () => {
            form.transfer_type     = undefined;
            form.from_warehouse_id = undefined;
            form.to_warehouse_id   = undefined;
            form.amount            = null;
            form.transfer_date     = dayjs();
            form.notes             = "";
        };

        const onTypeChange = (val) => {
            form.from_warehouse_id = undefined;
            form.to_warehouse_id   = undefined;
        };

        const submitTransfer = async () => {
            if (!form.transfer_type || !form.from_warehouse_id || !form.to_warehouse_id || !form.amount || !form.transfer_date) {
                message.warning("Please fill all required fields.");
                return;
            }
            if (form.from_warehouse_id === form.to_warehouse_id) {
                message.error("Source and destination branch cannot be the same.");
                return;
            }
            saving.value = true;
            try {
                await axiosAdmin.post("cash-transfers", {
                    transfer_type:    form.transfer_type,
                    from_warehouse_id: form.from_warehouse_id,
                    to_warehouse_id:   form.to_warehouse_id,
                    amount:            form.amount,
                    transfer_date:     form.transfer_date ? form.transfer_date.format("YYYY-MM-DD") : null,
                    notes:             form.notes,
                });
                message.success("Cash transfer recorded successfully.");
                modalVisible.value = false;
                resetForm();
                fetchData();
            } catch (err) {
                const msg = err?.response?.data?.message || "Failed to create transfer.";
                message.error(msg);
            } finally {
                saving.value = false;
            }
        };

        const typeLabel = (type) => {
            const map = { ho_to_branch: "HO → Branch", branch_to_ho: "Branch → HO", branch_to_branch: "Branch → Branch" };
            return map[type] ?? type;
        };

        const typeColor = (type) => {
            const map = { ho_to_branch: "blue", branch_to_ho: "green", branch_to_branch: "orange" };
            return map[type] ?? "default";
        };

        onMounted(() => {
            fetchData();
            fetchWarehouses();
        });

        return {
            permsArray, formatAmountCurrency, formatDate,
            tableData, tableLoading, columns, pagination, pageTotal,
            filters, extraFilters,
            summary,
            warehouses,
            modalVisible, saving, form,
            fetchData, handleTableChange,
            openCreateModal, resetForm, onTypeChange, submitTransfer,
            typeLabel, typeColor,
        };
    },
};
</script>

<style scoped>
.ct-summary-row {
    display: flex;
    gap: 16px;
    padding: 16px 24px 0;
    flex-wrap: wrap;
}
.ct-card {
    flex: 1;
    min-width: 200px;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 20px;
    border-radius: 10px;
    color: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,0.12);
}
.ct-card--blue   { background: linear-gradient(135deg, #1677ff, #0958d9); }
.ct-card--green  { background: linear-gradient(135deg, #52c41a, #389e0d); }
.ct-card--orange { background: linear-gradient(135deg, #fa8c16, #d46b08); }
.ct-card--purple { background: linear-gradient(135deg, #722ed1, #531dab); }
.ct-card__icon { font-size: 28px; }
.ct-card__label { font-size: 12px; opacity: 0.88; margin-bottom: 4px; }
.ct-card__value { font-size: 20px; font-weight: 700; letter-spacing: 0.3px; }
.ct-amount { font-weight: 600; color: #1677ff; }
.ct-form .ant-form-item { margin-bottom: 14px; }
</style>
