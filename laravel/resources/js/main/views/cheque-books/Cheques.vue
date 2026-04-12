<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header
                :title="book ? `Cheques — ${book.bank_name} [${book.book_no}]` : 'Cheques'"
                class="p-0"
                @back="$router.push({ name: 'admin.cheque-books.index' })"
            />
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
                <a-breadcrumb-item>Cheques</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters v-if="book">
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :xs="24" :md="16">
                <a-space wrap>
                    <a-statistic title="Total" :value="book.total_cheques" style="margin-right: 24px" />
                    <a-statistic title="Used" :value="book.used_count || 0" style="margin-right: 24px" />
                    <a-statistic title="Remaining" :value="book.remaining_cheques" />
                </a-space>
            </a-col>
            <a-col :xs="24" :md="8" style="text-align: right">
                <a-select
                    v-model:value="statusFilter"
                    placeholder="Filter by Status"
                    :allowClear="true"
                    style="width: 160px"
                    @change="fetchCheques"
                >
                    <a-select-option value="unused">Unused</a-select-option>
                    <a-select-option value="issued">Issued</a-select-option>
                    <a-select-option value="cancelled">Cancelled</a-select-option>
                </a-select>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <a-table
            :dataSource="cheques"
            :columns="columns"
            :loading="loading"
            rowKey="xid"
            :scroll="{ x: 900 }"
            :pagination="{ pageSize: 25 }"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'status'">
                    <a-tag :color="statusColor(record.status)">
                        {{ record.status.toUpperCase() }}
                    </a-tag>
                </template>
                <template v-else-if="column.dataIndex === 'amount'">
                    <span v-if="record.amount">{{ formatCurrency(record.amount) }}</span>
                    <span v-else class="text-gray-400">—</span>
                </template>
                <template v-else-if="column.dataIndex === 'issue_date'">
                    <span v-if="record.issue_date">{{ record.issue_date }}</span>
                    <span v-else class="text-gray-400">—</span>
                </template>
                <template v-else-if="column.dataIndex === 'action'">
                    <a-space>
                        <a-button
                            v-if="record.status === 'unused'"
                            size="small"
                            type="primary"
                            @click="openIssueModal(record)"
                        >
                            Issue
                        </a-button>
                        <a-popconfirm
                            v-if="record.status !== 'cancelled'"
                            title="Cancel this cheque?"
                            ok-text="Cancel Cheque"
                            ok-type="danger"
                            cancel-text="No"
                            @confirm="cancelCheque(record)"
                        >
                            <a-button size="small" danger>Cancel</a-button>
                        </a-popconfirm>
                    </a-space>
                </template>
            </template>
        </a-table>
    </admin-page-table-content>

    <!-- Issue Cheque Modal -->
    <a-modal
        v-model:open="showIssueModal"
        :title="`Issue Cheque #${selectedCheque?.cheque_no}`"
        :footer="null"
        :width="480"
    >
        <a-form layout="vertical" @finish="issueCheque">
            <a-form-item label="Payee (Pay To)" required>
                <a-input v-model:value="issueForm.payee" placeholder="Payee name" />
            </a-form-item>
            <a-form-item label="Amount (PKR)" required>
                <a-input-number
                    v-model:value="issueForm.amount"
                    :min="0.01"
                    :step="100"
                    style="width: 100%"
                    :formatter="v => v ? v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') : ''"
                    :parser="v => v.replace(/,/g, '')"
                />
            </a-form-item>
            <a-form-item label="Issue Date" required>
                <a-date-picker v-model:value="issueForm.issue_date" style="width: 100%" valueFormat="YYYY-MM-DD" />
            </a-form-item>
            <a-form-item label="Remarks">
                <a-textarea v-model:value="issueForm.remarks" :rows="2" placeholder="Purpose / details" />
            </a-form-item>
            <div style="text-align: right">
                <a-space>
                    <a-button @click="showIssueModal = false">Cancel</a-button>
                    <a-button type="primary" html-type="submit" :loading="saving">Issue Cheque</a-button>
                </a-space>
            </div>
        </a-form>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    components: { AdminPageHeader },

    setup() {
        const route = useRoute();
        const bookId = route.params.id;

        const book = ref(null);
        const cheques = ref([]);
        const loading = ref(false);
        const saving = ref(false);
        const statusFilter = ref(null);
        const showIssueModal = ref(false);
        const selectedCheque = ref(null);

        const issueForm = ref({
            payee: '', amount: null, issue_date: null, remarks: '',
        });

        const columns = [
            { title: 'Cheque #', dataIndex: 'cheque_no', width: 110, sorter: (a, b) => a.cheque_no - b.cheque_no },
            { title: 'Status', dataIndex: 'status', width: 110 },
            { title: 'Payee', dataIndex: 'payee', width: 180, ellipsis: true },
            { title: 'Amount', dataIndex: 'amount', width: 130 },
            { title: 'Issue Date', dataIndex: 'issue_date', width: 120 },
            { title: 'Remarks', dataIndex: 'remarks', ellipsis: true },
            { title: 'Action', dataIndex: 'action', width: 160, fixed: 'right' },
        ];

        const statusColor = (s) => ({ unused: 'blue', issued: 'green', cancelled: 'red' }[s] || 'default');

        const formatCurrency = (val) => {
            return 'PKR ' + Number(val).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        };

        const fetchBook = async () => {
            try {
                const res = await window.axiosAdmin.get(`cheque-books/${bookId}`);
                book.value = res.data.data;
            } catch {
                message.error('Failed to load cheque book.');
            }
        };

        const fetchCheques = async () => {
            loading.value = true;
            try {
                const params = {};
                if (statusFilter.value) params.status = statusFilter.value;
                const res = await window.axiosAdmin.get(`cheque-books/${bookId}/cheques`, { params });
                cheques.value = res.data.data;
            } catch {
                message.error('Failed to load cheques.');
            } finally {
                loading.value = false;
            }
        };

        const openIssueModal = (cheque) => {
            selectedCheque.value = cheque;
            issueForm.value = { payee: '', amount: null, issue_date: null, remarks: '' };
            showIssueModal.value = true;
        };

        const issueCheque = async () => {
            if (!issueForm.value.payee || !issueForm.value.amount || !issueForm.value.issue_date) {
                message.warning('Please fill payee, amount, and issue date.');
                return;
            }
            saving.value = true;
            try {
                await window.axiosAdmin.post(`cheques/${selectedCheque.value.xid}/issue`, issueForm.value);
                message.success(`Cheque #${selectedCheque.value.cheque_no} issued to ${issueForm.value.payee}`);
                showIssueModal.value = false;
                fetchCheques();
                fetchBook();
            } catch (err) {
                message.error(err.response?.data?.message || 'Failed to issue cheque.');
            } finally {
                saving.value = false;
            }
        };

        const cancelCheque = async (cheque) => {
            try {
                await window.axiosAdmin.post(`cheques/${cheque.xid}/cancel`);
                message.success(`Cheque #${cheque.cheque_no} cancelled.`);
                fetchCheques();
                fetchBook();
            } catch (err) {
                message.error(err.response?.data?.message || 'Failed to cancel.');
            }
        };

        onMounted(() => {
            fetchBook();
            fetchCheques();
        });

        return {
            book, cheques, loading, saving, statusFilter, columns,
            showIssueModal, selectedCheque, issueForm,
            statusColor, formatCurrency,
            fetchCheques, openIssueModal, issueCheque, cancelCheque,
        };
    },
});
</script>
