<template>
    <div class="bank-txn-page">
        <AdminPageHeader>
            <template #header>
                <a-page-header
                    :title="account ? `${account.account_name} — Transactions` : 'Bank Transactions'"
                    class="p-0"
                    @back="$router.push({ name: 'admin.accounting.bank_accounts.index' })"
                />
            </template>
            <template #breadcrumb>
                <a-breadcrumb separator="-" style="font-size: 12px">
                    <a-breadcrumb-item>
                        <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                    </a-breadcrumb-item>
                    <a-breadcrumb-item>
                        <router-link :to="{ name: 'admin.accounting.bank_accounts.index' }">Bank Accounts</router-link>
                    </a-breadcrumb-item>
                    <a-breadcrumb-item>Transactions</a-breadcrumb-item>
                </a-breadcrumb>
            </template>
        </AdminPageHeader>

        <!-- Account Info + Summary Cards -->
        <div class="txn-hero" v-if="account">
            <a-row :gutter="[16, 16]">
                <a-col :xs="24" :md="8">
                    <div class="account-info-card">
                        <div class="aic-icon"><BankOutlined /></div>
                        <div class="aic-body">
                            <div class="aic-name">{{ account.account_name }}</div>
                            <div class="aic-detail" v-if="account.account_number">
                                <strong>A/C:</strong> {{ account.account_number }}
                            </div>
                            <div class="aic-detail" v-if="account.branch_name">
                                <strong>Branch:</strong> {{ account.branch_name }}
                            </div>
                            <div class="aic-detail">
                                <strong>Opening:</strong> {{ formatPKR(account.opening_balance) }}
                            </div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="8" :md="5">
                    <div class="txn-stat-card txn-stat--green">
                        <ArrowDownOutlined />
                        <div>
                            <div class="tsc-label">Total Debits</div>
                            <div class="tsc-value">{{ formatPKR(totalDebit) }}</div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="8" :md="5">
                    <div class="txn-stat-card txn-stat--orange">
                        <ArrowUpOutlined />
                        <div>
                            <div class="tsc-label">Total Credits</div>
                            <div class="tsc-value">{{ formatPKR(totalCredit) }}</div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="8" :md="6">
                    <div class="txn-stat-card txn-stat--blue" :class="{ 'txn-stat--neg': netBalance < 0 }">
                        <WalletOutlined />
                        <div>
                            <div class="tsc-label">Net Balance</div>
                            <div class="tsc-value">{{ formatPKR(netBalance) }}</div>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </div>

        <!-- Filters -->
        <div class="txn-filters">
            <a-row :gutter="[12, 12]" align="middle">
                <a-col :xs="24" :sm="24" :md="10">
                    <a-range-picker
                        v-model:value="filters.dateRange"
                        style="width: 100%"
                        valueFormat="YYYY-MM-DD"
                        @change="fetchTransactions"
                    />
                </a-col>
                <a-col :xs="24" :sm="12" :md="5">
                    <a-select
                        v-model:value="filters.source"
                        placeholder="All Sources"
                        :allowClear="true"
                        style="width: 100%"
                        @change="applyFilter"
                    >
                        <a-select-option value="journal">Journal Entries</a-select-option>
                        <a-select-option value="payment">Payments</a-select-option>
                    </a-select>
                </a-col>
                <a-col :xs="24" :sm="12" :md="5">
                    <a-input-search
                        v-model:value="filters.search"
                        placeholder="Search reference / description..."
                        allow-clear
                        style="width: 100%"
                        @change="applyFilter"
                    />
                </a-col>
                <a-col :xs="24" :md="4" style="text-align: right">
                    <a-button @click="clearFilters"><ReloadOutlined /> Reset</a-button>
                </a-col>
            </a-row>
        </div>

        <!-- Transactions Table -->
        <div class="txn-table-wrap">
            <a-table
                :dataSource="filteredRows"
                :columns="columns"
                :loading="loading"
                rowKey="(r, i) => i"
                :scroll="{ x: 800 }"
                :pagination="{ pageSize: 20, showTotal: t => `${t} transactions` }"
                class="txn-table"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.dataIndex === 'source'">
                        <a-tag :color="record.source === 'journal' ? 'blue' : 'purple'" style="font-size: 11px">
                            {{ record.source === 'journal' ? 'Journal' : 'Payment' }}
                        </a-tag>
                    </template>
                    <template v-else-if="column.dataIndex === 'debit'">
                        <span v-if="record.debit > 0" class="txn-debit">+ {{ formatPKR(record.debit) }}</span>
                        <span v-else>—</span>
                    </template>
                    <template v-else-if="column.dataIndex === 'credit'">
                        <span v-if="record.credit > 0" class="txn-credit">- {{ formatPKR(record.credit) }}</span>
                        <span v-else>—</span>
                    </template>
                </template>

                <template #summary>
                    <a-table-summary fixed>
                        <a-table-summary-row>
                            <a-table-summary-cell :index="0" :col-span="3" style="font-weight: 700">
                                Totals
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="3" style="color: #16a34a; font-weight: 700">
                                + {{ formatPKR(totalDebit) }}
                            </a-table-summary-cell>
                            <a-table-summary-cell :index="4" style="color: #ea580c; font-weight: 700">
                                - {{ formatPKR(totalCredit) }}
                            </a-table-summary-cell>
                        </a-table-summary-row>
                    </a-table-summary>
                </template>
            </a-table>
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { message } from 'ant-design-vue';
import {
    BankOutlined, WalletOutlined, ArrowDownOutlined, ArrowUpOutlined, ReloadOutlined,
} from '@ant-design/icons-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        BankOutlined, WalletOutlined, ArrowDownOutlined, ArrowUpOutlined, ReloadOutlined,
    },

    setup() {
        const route = useRoute();
        const accountId = route.params.id;

        const account = ref(null);
        const rows = ref([]);
        const loading = ref(false);
        const totalDebit = ref(0);
        const totalCredit = ref(0);
        const netBalance = ref(0);

        const filters = ref({ dateRange: null, source: null, search: '' });

        const columns = [
            { title: 'Date', dataIndex: 'date', width: 110, sorter: (a, b) => (a.date || '').localeCompare(b.date || '') },
            { title: 'Source', dataIndex: 'source', width: 100 },
            { title: 'Reference', dataIndex: 'reference', width: 140, ellipsis: true },
            { title: 'Description', dataIndex: 'description', ellipsis: true },
            { title: 'Debit (+)', dataIndex: 'debit', width: 140 },
            { title: 'Credit (−)', dataIndex: 'credit', width: 140 },
        ];

        const formatPKR = (val) => {
            if (val === null || val === undefined) return 'PKR 0';
            const n = Number(val);
            return (n < 0 ? '-PKR ' : 'PKR ') + Math.abs(n).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        };

        const filteredRows = computed(() => {
            let list = rows.value;
            if (filters.value.source) {
                list = list.filter(r => r.source === filters.value.source);
            }
            if (filters.value.search) {
                const q = filters.value.search.toLowerCase();
                list = list.filter(r =>
                    (r.reference || '').toLowerCase().includes(q) ||
                    (r.description || '').toLowerCase().includes(q)
                );
            }
            return list;
        });

        const fetchTransactions = async () => {
            loading.value = true;
            try {
                const params = {};
                if (filters.value.dateRange?.[0]) params.date_from = filters.value.dateRange[0];
                if (filters.value.dateRange?.[1]) params.date_to = filters.value.dateRange[1];

                const res = await window.axiosAdmin.get(`bank-accounts/${accountId}/transactions`, { params });
                account.value = res.data.account;
                rows.value = res.data.data;
                totalDebit.value = res.data.total_debit;
                totalCredit.value = res.data.total_credit;
                netBalance.value = res.data.net_balance;
            } catch {
                message.error('Failed to load transactions.');
            } finally {
                loading.value = false;
            }
        };

        const applyFilter = () => {};

        const clearFilters = () => {
            filters.value = { dateRange: null, source: null, search: '' };
            fetchTransactions();
        };

        onMounted(fetchTransactions);

        return {
            account, rows, loading, columns, filters,
            totalDebit, totalCredit, netBalance, filteredRows,
            formatPKR, fetchTransactions, applyFilter, clearFilters,
        };
    },
});
</script>

<style scoped>
.bank-txn-page { background: #f4f6f9; min-height: 100vh; }

.txn-hero { margin-bottom: 20px; }
.account-info-card {
    background: linear-gradient(135deg, #1e40af, #2563eb);
    border-radius: 12px;
    padding: 20px;
    display: flex; align-items: flex-start; gap: 14px;
    color: #fff;
    height: 100%;
}
.aic-icon { font-size: 30px; flex-shrink: 0; margin-top: 2px; }
.aic-name   { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
.aic-detail { font-size: 13px; opacity: .85; margin-bottom: 4px; }

.txn-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    font-size: 20px;
    height: 100%;
}
.txn-stat--green  { color: #16a34a; }
.txn-stat--orange { color: #ea580c; }
.txn-stat--blue   { color: #2563eb; }
.txn-stat--neg    { color: #dc2626; }
.tsc-label { font-size: 11px; color: #6b7280; text-transform: uppercase; }
.tsc-value { font-size: 18px; font-weight: 700; color: #111827; }

.txn-filters {
    background: #fff; border-radius: 10px;
    padding: 14px 20px; margin-bottom: 16px;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
}

.txn-table-wrap {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
}

.txn-debit  { color: #16a34a; font-weight: 600; }
.txn-credit { color: #dc2626; font-weight: 600; }
</style>
