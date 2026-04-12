<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Cheque Book Register" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t('menu.dashboard') }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Cheque Books</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                <a-button type="primary" @click="showCreateModal = true">
                    <PlusOutlined /> New Cheque Book
                </a-button>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <a-table
            :dataSource="books"
            :columns="columns"
            :loading="loading"
            rowKey="xid"
            :scroll="{ x: 900 }"
            :pagination="{ pageSize: 15 }"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'book_no'">
                    <a @click="openBook(record)">{{ record.book_no }}</a>
                </template>
                <template v-else-if="column.dataIndex === 'range'">
                    {{ record.start_cheque_no }} – {{ record.end_cheque_no }}
                </template>
                <template v-else-if="column.dataIndex === 'remaining_cheques'">
                    <a-tag :color="record.remaining_cheques === 0 ? 'red' : record.remaining_cheques < 5 ? 'orange' : 'green'">
                        {{ record.remaining_cheques }} remaining
                    </a-tag>
                </template>
                <template v-else-if="column.dataIndex === 'action'">
                    <a-space>
                        <a-button size="small" @click="openBook(record)">
                            <UnorderedListOutlined /> Cheques
                        </a-button>
                        <a-popconfirm
                            title="Delete this cheque book? (Only if no cheques are issued)"
                            ok-text="Delete"
                            ok-type="danger"
                            cancel-text="Cancel"
                            @confirm="deleteBook(record)"
                        >
                            <a-button size="small" danger>
                                <DeleteOutlined />
                            </a-button>
                        </a-popconfirm>
                    </a-space>
                </template>
            </template>
        </a-table>
    </admin-page-table-content>

    <!-- Create Cheque Book Modal -->
    <a-modal
        v-model:open="showCreateModal"
        title="Add New Cheque Book"
        :footer="null"
        :width="560"
        @cancel="resetForm"
    >
        <a-form layout="vertical" @finish="createBook">
            <a-row :gutter="[16, 0]">
                <a-col :span="24">
                    <a-form-item label="Bank Name" required>
                        <a-input v-model:value="form.bank_name" placeholder="e.g. HBL, MCB, UBL" />
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Account Title">
                        <a-input v-model:value="form.account_title" placeholder="Account holder name" />
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Account Number">
                        <a-input v-model:value="form.account_number" placeholder="IBAN / Account No" />
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Book / Series No" required>
                        <a-input v-model:value="form.book_no" placeholder="e.g. HBL-001" />
                    </a-form-item>
                </a-col>
                <a-col :span="6">
                    <a-form-item label="Start Cheque #" required>
                        <a-input-number v-model:value="form.start_cheque_no" :min="1" style="width:100%" />
                    </a-form-item>
                </a-col>
                <a-col :span="6">
                    <a-form-item label="End Cheque #" required>
                        <a-input-number v-model:value="form.end_cheque_no" :min="form.start_cheque_no || 1" style="width:100%" />
                    </a-form-item>
                </a-col>
                <a-col :span="24" v-if="form.start_cheque_no && form.end_cheque_no && form.end_cheque_no >= form.start_cheque_no">
                    <a-alert
                        :message="`This will generate ${form.end_cheque_no - form.start_cheque_no + 1} cheques automatically.`"
                        type="info"
                        show-icon
                        style="margin-bottom: 12px"
                    />
                </a-col>
                <a-col :span="24">
                    <a-form-item label="Notes">
                        <a-textarea v-model:value="form.notes" :rows="2" placeholder="Optional notes" />
                    </a-form-item>
                </a-col>
            </a-row>
            <div style="text-align: right">
                <a-space>
                    <a-button @click="resetForm">Cancel</a-button>
                    <a-button type="primary" html-type="submit" :loading="saving">
                        Create Book
                    </a-button>
                </a-space>
            </div>
        </a-form>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';
import { PlusOutlined, DeleteOutlined, UnorderedListOutlined } from '@ant-design/icons-vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    components: { AdminPageHeader, PlusOutlined, DeleteOutlined, UnorderedListOutlined },

    setup() {
        const router = useRouter();
        const books = ref([]);
        const loading = ref(false);
        const saving = ref(false);
        const showCreateModal = ref(false);

        const form = ref({
            bank_name: '',
            account_title: '',
            account_number: '',
            book_no: '',
            start_cheque_no: null,
            end_cheque_no: null,
            notes: '',
        });

        const columns = [
            { title: 'Bank', dataIndex: 'bank_name', width: 130 },
            { title: 'Account Title', dataIndex: 'account_title', width: 150 },
            { title: 'Book No', dataIndex: 'book_no', width: 110 },
            { title: 'Cheque Range', dataIndex: 'range', width: 140 },
            { title: 'Total', dataIndex: 'total_cheques', width: 80 },
            { title: 'Used', dataIndex: 'used_count', width: 70 },
            { title: 'Remaining', dataIndex: 'remaining_cheques', width: 130 },
            { title: 'Action', dataIndex: 'action', width: 160, fixed: 'right' },
        ];

        const fetchBooks = async () => {
            loading.value = true;
            try {
                const res = await window.axiosAdmin.get('cheque-books');
                books.value = res.data.data;
            } catch {
                message.error('Failed to load cheque books');
            } finally {
                loading.value = false;
            }
        };

        const createBook = async () => {
            if (!form.value.bank_name || !form.value.book_no || !form.value.start_cheque_no || !form.value.end_cheque_no) {
                message.warning('Please fill all required fields.');
                return;
            }
            if (form.value.end_cheque_no < form.value.start_cheque_no) {
                message.warning('End cheque number must be >= start.');
                return;
            }
            saving.value = true;
            try {
                await window.axiosAdmin.post('cheque-books', form.value);
                message.success('Cheque book created and cheques generated!');
                showCreateModal.value = false;
                resetForm();
                fetchBooks();
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to create cheque book.';
                message.error(msg);
            } finally {
                saving.value = false;
            }
        };

        const deleteBook = async (record) => {
            try {
                await window.axiosAdmin.delete(`cheque-books/${record.xid}`);
                message.success('Cheque book deleted.');
                fetchBooks();
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to delete.';
                message.error(msg);
            }
        };

        const openBook = (record) => {
            router.push({ name: 'admin.cheque-books.cheques', params: { id: record.xid } });
        };

        const resetForm = () => {
            showCreateModal.value = false;
            form.value = {
                bank_name: '', account_title: '', account_number: '',
                book_no: '', start_cheque_no: null, end_cheque_no: null, notes: '',
            };
        };

        onMounted(fetchBooks);

        return { books, loading, saving, showCreateModal, form, columns, createBook, deleteBook, openBook, resetForm };
    },
});
</script>
