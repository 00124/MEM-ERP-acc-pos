<template>
    <div class="cheque-clearance-page">
        <!-- Page Header -->
        <AdminPageHeader>
            <template #header>
                <a-page-header title="Cheque Clearance" class="p-0" />
            </template>
            <template #breadcrumb>
                <a-breadcrumb separator="-" style="font-size: 12px">
                    <a-breadcrumb-item>
                        <router-link :to="{ name: 'admin.dashboard.index' }">
                            {{ $t('menu.dashboard') }}
                        </router-link>
                    </a-breadcrumb-item>
                    <a-breadcrumb-item>
                        <router-link :to="{ name: 'admin.cheque-books.index' }">Cheque Books</router-link>
                    </a-breadcrumb-item>
                    <a-breadcrumb-item>Clearance</a-breadcrumb-item>
                </a-breadcrumb>
            </template>
        </AdminPageHeader>

        <!-- Filters Bar -->
        <div class="clearance-filters">
            <a-row :gutter="[12, 12]" align="middle">
                <a-col :xs="24" :sm="12" :md="6" :lg="5">
                    <a-select
                        v-model:value="filters.cheque_book_id"
                        placeholder="All Banks / Books"
                        :allowClear="true"
                        style="width: 100%"
                        @change="fetchData"
                    >
                        <a-select-option v-for="b in books" :key="b.xid" :value="b.xid">
                            {{ b.bank_name }} – {{ b.book_no }}
                        </a-select-option>
                    </a-select>
                </a-col>
                <a-col :xs="24" :sm="12" :md="5" :lg="4">
                    <a-select
                        v-model:value="filters.status"
                        placeholder="All Statuses"
                        :allowClear="true"
                        style="width: 100%"
                        @change="fetchData"
                    >
                        <a-select-option value="issued">
                            <span style="color:#f59e0b">● Pending</span>
                        </a-select-option>
                        <a-select-option value="cleared">
                            <span style="color:#22c55e">● Cleared</span>
                        </a-select-option>
                        <a-select-option value="bounced">
                            <span style="color:#ef4444">● Bounced</span>
                        </a-select-option>
                    </a-select>
                </a-col>
                <a-col :xs="24" :sm="24" :md="7" :lg="6">
                    <a-range-picker
                        v-model:value="filters.dateRange"
                        style="width: 100%"
                        valueFormat="YYYY-MM-DD"
                        placeholder="['Issue From', 'Issue To']"
                        @change="fetchData"
                    />
                </a-col>
                <a-col :xs="24" :sm="12" :md="6" :lg="5">
                    <a-input-search
                        v-model:value="filters.search"
                        placeholder="Search cheque no / payee..."
                        allow-clear
                        style="width: 100%"
                        @change="onSearchChange"
                    />
                </a-col>
                <a-col :xs="24" :sm="12" :md="24" :lg="4" style="text-align: right">
                    <a-button @click="clearFilters" style="margin-right: 8px">
                        <ReloadOutlined /> Reset
                    </a-button>
                </a-col>
            </a-row>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <a-row :gutter="[16, 16]">
                <a-col :xs="24" :sm="12" :md="6">
                    <div class="stat-card stat-card--pending">
                        <div class="stat-card__icon">
                            <ClockCircleOutlined />
                        </div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Pending Cheques</div>
                            <div class="stat-card__value">{{ summary.total_pending }}</div>
                            <div class="stat-card__sub">awaiting clearance</div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <div class="stat-card stat-card--cleared">
                        <div class="stat-card__icon">
                            <CheckCircleOutlined />
                        </div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Cleared Amount</div>
                            <div class="stat-card__value">{{ formatPKR(summary.total_cleared_amount) }}</div>
                            <div class="stat-card__sub">successfully cleared</div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <div class="stat-card stat-card--amount">
                        <div class="stat-card__icon">
                            <DollarOutlined />
                        </div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Pending Amount</div>
                            <div class="stat-card__value">{{ formatPKR(summary.total_pending_amount) }}</div>
                            <div class="stat-card__sub">yet to be cleared</div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <div class="stat-card stat-card--bounced">
                        <div class="stat-card__icon">
                            <WarningOutlined />
                        </div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Bounced Amount</div>
                            <div class="stat-card__value">{{ formatPKR(summary.total_bounced_amount) }}</div>
                            <div class="stat-card__sub">returned / dishonoured</div>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </div>

        <!-- Data Table -->
        <div class="clearance-table-wrap">
            <a-table
                :dataSource="filteredCheques"
                :columns="columns"
                :loading="loading"
                rowKey="xid"
                :scroll="{ x: 950 }"
                :pagination="{ pageSize: 15, showSizeChanger: false, showTotal: (t) => `${t} cheques` }"
                class="clearance-table"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.dataIndex === 'cheque_no'">
                        <span class="cheque-no-badge">#{{ record.cheque_no }}</span>
                    </template>
                    <template v-else-if="column.dataIndex === 'bank'">
                        <div v-if="record.cheque_book">
                            <div class="bank-name">{{ record.cheque_book.bank_name }}</div>
                            <div class="book-no">{{ record.cheque_book.book_no }}</div>
                        </div>
                    </template>
                    <template v-else-if="column.dataIndex === 'payee'">
                        <span class="payee-name">{{ record.payee || '—' }}</span>
                    </template>
                    <template v-else-if="column.dataIndex === 'amount'">
                        <span class="amount-cell">{{ formatPKR(record.amount) }}</span>
                    </template>
                    <template v-else-if="column.dataIndex === 'issue_date'">
                        {{ record.issue_date || '—' }}
                    </template>
                    <template v-else-if="column.dataIndex === 'status'">
                        <span :class="['status-badge', `status-badge--${record.status}`]">
                            {{ statusLabel(record.status) }}
                        </span>
                    </template>
                    <template v-else-if="column.dataIndex === 'action'">
                        <a-space>
                            <a-button
                                v-if="record.status === 'issued'"
                                size="small"
                                class="btn-clear"
                                @click="openClearModal(record)"
                            >
                                <CheckOutlined /> Clear
                            </a-button>
                            <a-button
                                v-if="record.status === 'issued' || record.status === 'cleared'"
                                size="small"
                                class="btn-bounce"
                                @click="openBounceModal(record)"
                            >
                                <StopOutlined /> Bounce
                            </a-button>
                            <a-tag v-if="record.status === 'bounced'" color="red">Bounced</a-tag>
                            <a-tag v-if="record.status === 'cleared'" color="green">Cleared ✓</a-tag>
                        </a-space>
                    </template>
                </template>
            </a-table>
        </div>

        <!-- Clear Cheque Modal -->
        <a-modal
            v-model:open="showClearModal"
            title="Clear Cheque"
            :footer="null"
            :width="440"
            centered
        >
            <div class="modal-cheque-info" v-if="selectedCheque">
                <div class="modal-cheque-row">
                    <span class="modal-label">Cheque No</span>
                    <span class="modal-value cheque-no-badge">#{{ selectedCheque.cheque_no }}</span>
                </div>
                <div class="modal-cheque-row">
                    <span class="modal-label">Payee</span>
                    <span class="modal-value">{{ selectedCheque.payee }}</span>
                </div>
                <div class="modal-cheque-row">
                    <span class="modal-label">Amount</span>
                    <span class="modal-value amount-cell">{{ formatPKR(selectedCheque.amount) }}</span>
                </div>
            </div>

            <a-divider style="margin: 12px 0" />

            <a-form layout="vertical" @finish="confirmClear">
                <a-form-item label="Clearance Date" required>
                    <a-date-picker
                        v-model:value="clearForm.clear_date"
                        style="width: 100%"
                        valueFormat="YYYY-MM-DD"
                        :disabledDate="d => d && d > Date.now()"
                    />
                </a-form-item>
                <div style="text-align: right; margin-top: 8px">
                    <a-space>
                        <a-button @click="showClearModal = false">Cancel</a-button>
                        <a-button type="primary" html-type="submit" :loading="saving" class="btn-confirm-clear">
                            <CheckCircleOutlined /> Confirm Clearance
                        </a-button>
                    </a-space>
                </div>
            </a-form>
        </a-modal>

        <!-- Bounce Cheque Modal -->
        <a-modal
            v-model:open="showBounceModal"
            title="Mark as Bounced"
            :footer="null"
            :width="440"
            centered
        >
            <div class="modal-cheque-info bounce-info" v-if="selectedCheque">
                <div class="modal-cheque-row">
                    <span class="modal-label">Cheque No</span>
                    <span class="modal-value cheque-no-badge">#{{ selectedCheque.cheque_no }}</span>
                </div>
                <div class="modal-cheque-row">
                    <span class="modal-label">Payee</span>
                    <span class="modal-value">{{ selectedCheque.payee }}</span>
                </div>
                <div class="modal-cheque-row">
                    <span class="modal-label">Amount</span>
                    <span class="modal-value amount-cell">{{ formatPKR(selectedCheque.amount) }}</span>
                </div>
            </div>

            <a-divider style="margin: 12px 0" />

            <a-form layout="vertical" @finish="confirmBounce">
                <a-form-item label="Bounce Reason">
                    <a-textarea
                        v-model:value="bounceForm.bounce_reason"
                        :rows="3"
                        placeholder="e.g. Insufficient funds, Account closed..."
                    />
                </a-form-item>
                <div style="text-align: right; margin-top: 8px">
                    <a-space>
                        <a-button @click="showBounceModal = false">Cancel</a-button>
                        <a-button danger html-type="submit" :loading="saving">
                            <WarningOutlined /> Mark as Bounced
                        </a-button>
                    </a-space>
                </div>
            </a-form>
        </a-modal>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { message } from 'ant-design-vue';
import {
    ClockCircleOutlined, CheckCircleOutlined, DollarOutlined, WarningOutlined,
    CheckOutlined, StopOutlined, ReloadOutlined,
} from '@ant-design/icons-vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        ClockCircleOutlined, CheckCircleOutlined, DollarOutlined, WarningOutlined,
        CheckOutlined, StopOutlined, ReloadOutlined,
    },

    setup() {
        const books = ref([]);
        const cheques = ref([]);
        const loading = ref(false);
        const saving = ref(false);

        const summary = ref({
            total_pending: 0,
            total_cleared_amount: 0,
            total_pending_amount: 0,
            total_bounced_amount: 0,
        });

        const filters = ref({
            cheque_book_id: null,
            status: null,
            dateRange: null,
            search: '',
        });

        const showClearModal = ref(false);
        const showBounceModal = ref(false);
        const selectedCheque = ref(null);
        const clearForm = ref({ clear_date: null });
        const bounceForm = ref({ bounce_reason: '' });

        let searchTimeout = null;

        const columns = [
            { title: 'Cheque No', dataIndex: 'cheque_no', width: 110, sorter: (a, b) => a.cheque_no - b.cheque_no },
            { title: 'Bank / Book', dataIndex: 'bank', width: 160 },
            { title: 'Payee', dataIndex: 'payee', width: 180, ellipsis: true },
            { title: 'Amount', dataIndex: 'amount', width: 130, sorter: (a, b) => (a.amount || 0) - (b.amount || 0) },
            { title: 'Issue Date', dataIndex: 'issue_date', width: 115 },
            { title: 'Status', dataIndex: 'status', width: 110 },
            { title: 'Actions', dataIndex: 'action', width: 180, fixed: 'right' },
        ];

        const statusLabel = (s) => ({ issued: 'Pending', cleared: 'Cleared', bounced: 'Bounced' }[s] || s);

        const formatPKR = (val) => {
            if (!val && val !== 0) return '—';
            return 'PKR ' + Number(val).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        };

        const filteredCheques = computed(() => {
            if (!filters.value.search) return cheques.value;
            const q = filters.value.search.toLowerCase();
            return cheques.value.filter(c =>
                String(c.cheque_no).includes(q) ||
                (c.payee && c.payee.toLowerCase().includes(q))
            );
        });

        const fetchBooks = async () => {
            try {
                const res = await window.axiosAdmin.get('cheque-books');
                books.value = res.data.data;
            } catch {}
        };

        const fetchData = async () => {
            loading.value = true;
            try {
                const params = {};
                if (filters.value.cheque_book_id) params.cheque_book_id = filters.value.cheque_book_id;
                if (filters.value.status) params.status = filters.value.status;
                if (filters.value.dateRange?.[0]) params.date_from = filters.value.dateRange[0];
                if (filters.value.dateRange?.[1]) params.date_to = filters.value.dateRange[1];
                if (filters.value.search) params.search = filters.value.search;

                const res = await window.axiosAdmin.get('cheque-books/clearance', { params });
                cheques.value = res.data.data;
                summary.value = res.data.summary;
            } catch {
                message.error('Failed to load clearance data.');
            } finally {
                loading.value = false;
            }
        };

        const onSearchChange = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(fetchData, 400);
        };

        const clearFilters = () => {
            filters.value = { cheque_book_id: null, status: null, dateRange: null, search: '' };
            fetchData();
        };

        const openClearModal = (cheque) => {
            selectedCheque.value = cheque;
            clearForm.value = { clear_date: null };
            showClearModal.value = true;
        };

        const openBounceModal = (cheque) => {
            selectedCheque.value = cheque;
            bounceForm.value = { bounce_reason: '' };
            showBounceModal.value = true;
        };

        const confirmClear = async () => {
            if (!clearForm.value.clear_date) {
                message.warning('Please select clearance date.');
                return;
            }
            saving.value = true;
            try {
                await window.axiosAdmin.post(`cheques/${selectedCheque.value.xid}/clear`, clearForm.value);
                message.success(`Cheque #${selectedCheque.value.cheque_no} cleared successfully!`);
                showClearModal.value = false;
                fetchData();
            } catch (err) {
                message.error(err.response?.data?.message || 'Failed to clear cheque.');
            } finally {
                saving.value = false;
            }
        };

        const confirmBounce = async () => {
            saving.value = true;
            try {
                await window.axiosAdmin.post(`cheques/${selectedCheque.value.xid}/bounce`, bounceForm.value);
                message.error(`Cheque #${selectedCheque.value.cheque_no} marked as Bounced.`);
                showBounceModal.value = false;
                fetchData();
            } catch (err) {
                message.error(err.response?.data?.message || 'Failed to mark bounced.');
            } finally {
                saving.value = false;
            }
        };

        onMounted(() => {
            fetchBooks();
            fetchData();
        });

        return {
            books, cheques, loading, saving, summary, filters, columns,
            showClearModal, showBounceModal, selectedCheque, clearForm, bounceForm,
            filteredCheques,
            statusLabel, formatPKR,
            fetchData, onSearchChange, clearFilters,
            openClearModal, openBounceModal, confirmClear, confirmBounce,
        };
    },
});
</script>

<style scoped>
.cheque-clearance-page {
    background: #f4f6f9;
    min-height: 100vh;
}

/* Filters Bar */
.clearance-filters {
    background: #fff;
    padding: 16px 24px;
    margin-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    margin: 0 0 20px 0;
}

/* Summary Cards */
.summary-cards {
    margin-bottom: 20px;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    transition: transform .2s;
    height: 100%;
}
.stat-card:hover { transform: translateY(-2px); }

.stat-card__icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.stat-card--pending .stat-card__icon  { background: #fef3c7; color: #d97706; }
.stat-card--cleared .stat-card__icon  { background: #dcfce7; color: #16a34a; }
.stat-card--amount  .stat-card__icon  { background: #dbeafe; color: #2563eb; }
.stat-card--bounced .stat-card__icon  { background: #fee2e2; color: #dc2626; }

.stat-card__body { flex: 1; min-width: 0; }
.stat-card__label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 4px;
}
.stat-card__value {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    line-height: 1.2;
}
.stat-card__sub {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 2px;
}

/* Table */
.clearance-table-wrap {
    background: #fff;
    border-radius: 12px;
    padding: 0;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    overflow: hidden;
}

.cheque-no-badge {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 2px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 13px;
    letter-spacing: .3px;
}

.bank-name { font-weight: 600; color: #111; font-size: 13px; }
.book-no   { font-size: 11px; color: #6b7280; }
.payee-name { color: #374151; }
.amount-cell { font-weight: 600; color: #1e40af; }

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .3px;
}
.status-badge--issued  { background: #fef3c7; color: #92400e; }
.status-badge--cleared { background: #dcfce7; color: #166534; }
.status-badge--bounced { background: #fee2e2; color: #991b1b; }

/* Action Buttons */
.btn-clear {
    background: #22c55e !important;
    border-color: #22c55e !important;
    color: #fff !important;
    border-radius: 6px !important;
}
.btn-clear:hover { background: #16a34a !important; border-color: #16a34a !important; }

.btn-bounce {
    background: #ef4444 !important;
    border-color: #ef4444 !important;
    color: #fff !important;
    border-radius: 6px !important;
}
.btn-bounce:hover { background: #dc2626 !important; border-color: #dc2626 !important; }

.btn-confirm-clear {
    background: #2563eb !important;
    border-color: #2563eb !important;
}

/* Modal Info Block */
.modal-cheque-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 4px;
}
.bounce-info { background: #fff5f5; }

.modal-cheque-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
}
.modal-cheque-row + .modal-cheque-row { border-top: 1px solid #f0f0f0; }
.modal-label { font-size: 12px; color: #6b7280; font-weight: 500; }
.modal-value { font-size: 14px; color: #111827; font-weight: 600; }
</style>
