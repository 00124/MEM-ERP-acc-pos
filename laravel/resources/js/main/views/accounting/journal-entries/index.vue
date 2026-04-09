<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Journal Entries" class="p-0">
                <template #extra>
                    <a-button type="primary" @click="openAddModal"><PlusOutlined /> New Journal Entry</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Journal Entries</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <!-- Filters -->
        <a-row :gutter="16" class="mb-20">
            <a-col :span="8">
                <a-range-picker v-model:value="dateRange" style="width:100%" @change="loadEntries" />
            </a-col>
        </a-row>

        <a-spin :spinning="loading">
            <a-table :dataSource="entries" :columns="columns" :pagination="pagination" @change="handleTableChange" rowKey="id" size="middle" :scroll="{ x: 1000 }">
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'entry_date'">{{ formatDate(record.entry_date) }}</template>

                    <template v-if="column.key === 'description'">
                        <div>{{ record.description }}</div>
                        <!-- Product previews when linked to an order -->
                        <div v-if="record.order_items && record.order_items.length" class="je-product-preview">
                            <span v-for="(item, i) in record.order_items.slice(0,2)" :key="i" class="je-prod-chip">
                                {{ item.product_name }} × {{ item.quantity }}
                            </span>
                            <span v-if="record.order_items.length > 2" class="je-prod-more">
                                +{{ record.order_items.length - 2 }} more
                            </span>
                        </div>
                    </template>

                    <template v-if="column.key === 'total_debit'">
                        <span class="text-blue-600 font-semibold">{{ formatAmount(record.lines?.reduce((s,l)=>s+(+l.debit),0)) }}</span>
                    </template>
                    <template v-if="column.key === 'items'">
                        <a-badge v-if="record.order_items && record.order_items.length"
                            :count="record.order_items.length"
                            :numberStyle="{ backgroundColor: '#10b981' }"
                            :title="record.order_items.length + ' product(s)'"
                        >
                            <ShoppingOutlined style="font-size:16px;color:#10b981" />
                        </a-badge>
                        <span v-else class="text-gray-300">—</span>
                    </template>
                    <template v-if="column.key === 'status'">
                        <a-tag :color="record.status==='posted'?'green':'orange'">{{ record.status }}</a-tag>
                    </template>
                    <template v-if="column.key === 'action'">
                        <a-space>
                            <a-button size="small" type="link" @click="viewEntry(record)"><EyeOutlined /></a-button>
                            <a-popconfirm title="Delete this entry?" @confirm="deleteEntry(record.id)">
                                <a-button size="small" type="link" danger><DeleteOutlined /></a-button>
                            </a-popconfirm>
                        </a-space>
                    </template>
                </template>
            </a-table>
        </a-spin>
    </a-card>

    <!-- New Journal Entry Modal -->
    <a-modal v-model:open="addModalVisible" title="New Journal Entry" width="860px" @ok="saveEntry" :confirmLoading="saving" okText="Post Entry">
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :span="8">
                    <a-form-item label="Date" required>
                        <a-date-picker v-model:value="entryForm.entry_date" style="width:100%" />
                    </a-form-item>
                </a-col>
                <a-col :span="8">
                    <a-form-item label="Reference">
                        <a-input v-model:value="entryForm.reference" placeholder="Invoice #, PO #..." />
                    </a-form-item>
                </a-col>
                <a-col :span="8">
                    <a-form-item label="Description">
                        <a-input v-model:value="entryForm.description" placeholder="Entry description" />
                    </a-form-item>
                </a-col>
            </a-row>

            <!-- Lines -->
            <a-table :dataSource="entryForm.lines" :columns="lineColumns" :pagination="false" size="small" rowKey="key">
                <template #bodyCell="{ column, record, index }">
                    <template v-if="column.key === 'account_id'">
                        <a-select v-model:value="record.account_id" style="width:100%" show-search :filter-option="filterAccount" placeholder="Select account">
                            <a-select-option v-for="a in allAccounts" :key="a.id" :value="a.id">
                                {{ a.account_code }} - {{ a.account_name }}
                            </a-select-option>
                        </a-select>
                    </template>
                    <template v-if="column.key === 'description'">
                        <a-input v-model:value="record.description" placeholder="Line note" />
                    </template>
                    <template v-if="column.key === 'debit'">
                        <a-input-number v-model:value="record.debit" :min="0" :precision="2" style="width:100%" @change="()=>record.credit=record.debit>0?0:record.credit" />
                    </template>
                    <template v-if="column.key === 'credit'">
                        <a-input-number v-model:value="record.credit" :min="0" :precision="2" style="width:100%" @change="()=>record.debit=record.credit>0?0:record.debit" />
                    </template>
                    <template v-if="column.key === 'remove'">
                        <a-button type="link" danger size="small" @click="removeLine(index)" :disabled="entryForm.lines.length<=2"><MinusCircleOutlined /></a-button>
                    </template>
                </template>
                <template #footer>
                    <div class="flex justify-between items-center">
                        <a-button type="dashed" size="small" @click="addLine"><PlusOutlined /> Add Line</a-button>
                        <div style="font-weight:600">
                            <span class="mr-4">Total Debit: <span class="text-blue-600">{{ totalDebit.toFixed(2) }}</span></span>
                            <span>Total Credit: <span class="text-green-600">{{ totalCredit.toFixed(2) }}</span></span>
                            <span v-if="!isBalanced" class="ml-4 text-red-500">⚠ Not balanced</span>
                            <span v-else class="ml-4 text-green-600">✓ Balanced</span>
                        </div>
                    </div>
                </template>
            </a-table>
        </a-form>
    </a-modal>

    <!-- View Entry Modal -->
    <a-modal v-model:open="viewModalVisible" title="Journal Entry Details" width="780px" :footer="null">
        <template v-if="viewingEntry">

            <!-- Entry Header Info -->
            <a-descriptions :column="2" bordered size="small" class="mb-16">
                <a-descriptions-item label="Entry #">{{ viewingEntry.entry_number }}</a-descriptions-item>
                <a-descriptions-item label="Date">{{ formatDate(viewingEntry.entry_date) }}</a-descriptions-item>
                <a-descriptions-item label="Reference">
                    <span v-if="viewingEntry.reference">
                        <a-tag color="blue">{{ viewingEntry.reference }}</a-tag>
                        <span v-if="viewingEntry.order_type" class="ml-1" style="font-size:11px;color:#64748b">{{ viewingEntry.order_type }}</span>
                    </span>
                    <span v-else>—</span>
                </a-descriptions-item>
                <a-descriptions-item label="Status">
                    <a-tag color="green">{{ viewingEntry.status }}</a-tag>
                </a-descriptions-item>
                <a-descriptions-item label="Description" :span="2">{{ viewingEntry.description || '—' }}</a-descriptions-item>
            </a-descriptions>

            <!-- ── Product Details Section ──────────────────────────── -->
            <template v-if="viewingEntry.order_items && viewingEntry.order_items.length">
                <div class="je-section-title">
                    <ShoppingOutlined /> Products / Items
                    <span class="je-section-badge">{{ viewingEntry.order_items.length }} item(s)</span>
                </div>
                <table class="je-product-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Item Code</th>
                            <th class="je-right">Qty</th>
                            <th class="je-right">Unit Price (PKR)</th>
                            <th class="je-right">Discount</th>
                            <th class="je-right">Tax</th>
                            <th class="je-right">Subtotal (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, i) in viewingEntry.order_items" :key="i">
                            <td class="je-td-idx">{{ i + 1 }}</td>
                            <td class="je-td-name">{{ item.product_name }}</td>
                            <td><span class="je-code">{{ item.item_code || '—' }}</span></td>
                            <td class="je-right je-td-qty">{{ item.quantity }}</td>
                            <td class="je-right">{{ formatAmount(item.unit_price) }}</td>
                            <td class="je-right">
                                <span v-if="+item.total_discount > 0" class="je-discount">{{ formatAmount(item.total_discount) }}</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="je-right">
                                <span v-if="+item.total_tax > 0" class="je-tax">{{ formatAmount(item.total_tax) }}</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="je-right je-td-subtotal">{{ formatAmount(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="je-foot-row">
                            <td colspan="7" class="je-foot-label">Total</td>
                            <td class="je-right je-foot-total">
                                {{ formatAmount(viewingEntry.order_items.reduce((s,i)=>s+(+i.subtotal),0)) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </template>
            <a-empty v-else-if="viewingEntry.reference" description="No product items linked to this entry" class="mb-16" />

            <!-- ── Accounting Lines Section ─────────────────────────── -->
            <div class="je-section-title" style="margin-top:20px">
                <AccountBookOutlined /> Accounting Lines
            </div>
            <a-table :dataSource="viewingEntry.lines" :columns="viewLineColumns" :pagination="false" size="small" rowKey="id">
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'debit'">
                        <span v-if="+record.debit > 0" class="text-blue-600 font-semibold">{{ formatAmount(record.debit) }}</span>
                        <span v-else class="text-gray-300">—</span>
                    </template>
                    <template v-if="column.key === 'credit'">
                        <span v-if="+record.credit > 0" class="text-green-600 font-semibold">{{ formatAmount(record.credit) }}</span>
                        <span v-else class="text-gray-300">—</span>
                    </template>
                </template>
                <template #summary>
                    <a-table-summary-row>
                        <a-table-summary-cell :index="0" :col-span="2"><b>Total</b></a-table-summary-cell>
                        <a-table-summary-cell :index="2"><b class="text-blue-600">{{ formatAmount(viewingEntry.lines?.reduce((s,l)=>s+(+l.debit),0)) }}</b></a-table-summary-cell>
                        <a-table-summary-cell :index="3"><b class="text-green-600">{{ formatAmount(viewingEntry.lines?.reduce((s,l)=>s+(+l.credit),0)) }}</b></a-table-summary-cell>
                    </a-table-summary-row>
                </template>
            </a-table>

        </template>
    </a-modal>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { PlusOutlined, DeleteOutlined, EyeOutlined, MinusCircleOutlined, ShoppingOutlined, AccountBookOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader, PlusOutlined, DeleteOutlined, EyeOutlined, MinusCircleOutlined, ShoppingOutlined, AccountBookOutlined },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading = ref(false);
        const saving = ref(false);
        const entries = ref([]);
        const allAccounts = ref([]);
        const dateRange = ref(null);
        const pagination = ref({ current: 1, pageSize: 20, total: 0 });

        const addModalVisible = ref(false);
        const viewModalVisible = ref(false);
        const viewingEntry = ref(null);

        const newLine = () => ({ key: Date.now() + Math.random(), account_id: null, description: '', debit: 0, credit: 0 });
        const entryForm = ref({ entry_date: dayjs(), reference: '', description: '', lines: [newLine(), newLine()] });

        const totalDebit = computed(() => entryForm.value.lines.reduce((s, l) => s + (+l.debit || 0), 0));
        const totalCredit = computed(() => entryForm.value.lines.reduce((s, l) => s + (+l.credit || 0), 0));
        const isBalanced = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.01 && totalDebit.value > 0);

        const columns = [
            { title: 'Entry #', dataIndex: 'entry_number', key: 'entry_number', width: 160 },
            { title: 'Date', key: 'entry_date', width: 110 },
            { title: 'Description / Products', key: 'description', ellipsis: false },
            { title: 'Reference', dataIndex: 'reference', key: 'reference', width: 120 },
            { title: 'Items', key: 'items', width: 70, align: 'center' },
            { title: 'Total', key: 'total_debit', width: 130 },
            { title: 'Status', key: 'status', width: 100 },
            { title: 'Action', key: 'action', width: 90, fixed: 'right' },
        ];

        const lineColumns = [
            { title: 'Account', key: 'account_id', width: 260 },
            { title: 'Description', key: 'description' },
            { title: 'Debit', key: 'debit', width: 130 },
            { title: 'Credit', key: 'credit', width: 130 },
            { title: '', key: 'remove', width: 50 },
        ];

        const viewLineColumns = [
            { title: 'Account', dataIndex: ['account', 'account_name'], key: 'account' },
            { title: 'Description', dataIndex: 'description', key: 'description' },
            { title: 'Debit', key: 'debit', width: 130, align: 'right' },
            { title: 'Credit', key: 'credit', width: 130, align: 'right' },
        ];

        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '-';
        const formatAmount = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const filterAccount = (input, option) => {
            const label = option.children?.() ?? '';
            return String(label).toLowerCase().includes(input.toLowerCase());
        };

        const loadAccounts = async () => {
            const res = await axiosAdmin.get('accounting/coa');
            allAccounts.value = (res.data.flat || []).filter(a => a.parent_id);
        };

        const loadEntries = async () => {
            loading.value = true;
            try {
                const params = { per_page: pagination.value.pageSize, page: pagination.value.current };
                if (dateRange.value?.[0]) { params.date_from = dateRange.value[0].format('YYYY-MM-DD'); params.date_to = dateRange.value[1].format('YYYY-MM-DD'); }
                const res = await axiosAdmin.get('accounting/journal-entries', { params });
                entries.value = res.data.data || res.data;
                pagination.value.total = res.data.total || entries.value.length;
            } catch (e) { message.error('Failed to load entries'); }
            finally { loading.value = false; }
        };

        const handleTableChange = (pag) => { pagination.value.current = pag.current; loadEntries(); };

        const openAddModal = () => {
            entryForm.value = { entry_date: dayjs(), reference: '', description: '', lines: [newLine(), newLine()] };
            addModalVisible.value = true;
        };

        const addLine = () => entryForm.value.lines.push(newLine());
        const removeLine = (i) => entryForm.value.lines.splice(i, 1);

        const saveEntry = async () => {
            if (!isBalanced.value) { message.warning('Debit and Credit totals must be equal'); return; }
            saving.value = true;
            try {
                const payload = {
                    entry_date: entryForm.value.entry_date.format('YYYY-MM-DD'),
                    reference: entryForm.value.reference,
                    description: entryForm.value.description,
                    lines: entryForm.value.lines.filter(l => l.account_id),
                };
                await axiosAdmin.post('accounting/journal-entries', payload);
                message.success('Journal entry posted');
                addModalVisible.value = false;
                loadEntries();
            } catch (e) { message.error(e.response?.data?.message || 'Error saving entry'); }
            finally { saving.value = false; }
        };

        const viewEntry = (record) => { viewingEntry.value = record; viewModalVisible.value = true; };

        const deleteEntry = async (id) => {
            try { await axiosAdmin.delete(`accounting/journal-entries/${id}`); message.success('Deleted'); loadEntries(); }
            catch (e) { message.error('Cannot delete'); }
        };

        onMounted(() => { loadAccounts(); loadEntries(); });

        return { loading, saving, entries, allAccounts, dateRange, pagination, addModalVisible, viewModalVisible, viewingEntry, entryForm, totalDebit, totalCredit, isBalanced, columns, lineColumns, viewLineColumns, formatDate, formatAmount, filterAccount, loadEntries, handleTableChange, openAddModal, addLine, removeLine, saveEntry, viewEntry, deleteEntry };
    }
});
</script>

<style scoped>
/* Product chips in list */
.je-product-preview { margin-top: 5px; display: flex; flex-wrap: wrap; gap: 4px; }
.je-prod-chip {
    display: inline-block; padding: 1px 8px; border-radius: 10px;
    background: #f0fdf4; border: 1px solid #bbf7d0;
    color: #15803d; font-size: 11px; font-weight: 600;
}
.je-prod-more {
    display: inline-block; padding: 1px 8px; border-radius: 10px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    color: #64748b; font-size: 11px;
}

/* Section headings in view modal */
.je-section-title {
    font-size: 13px; font-weight: 700; color: #1e293b;
    margin-bottom: 10px; display: flex; align-items: center; gap: 7px;
    padding-bottom: 6px; border-bottom: 2px solid #f1f5f9;
}
.je-section-badge {
    margin-left: auto; background: #10b981; color: #fff;
    border-radius: 10px; padding: 1px 9px; font-size: 11px; font-weight: 700;
}

/* Product table in view modal */
.je-product-table {
    width: 100%; border-collapse: collapse; font-size: 12.5px;
    margin-bottom: 16px; border-radius: 10px; overflow: hidden;
}
.je-product-table thead tr { background: #f0fdf4; }
.je-product-table th {
    padding: 8px 10px; text-align: left; font-size: 11px; font-weight: 700;
    color: #15803d; text-transform: uppercase; letter-spacing: .4px;
    border-bottom: 2px solid #bbf7d0;
}
.je-product-table td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.je-product-table tbody tr:hover { background: #f8fafc; }
.je-product-table tbody tr:last-child td { border-bottom: none; }
.je-right { text-align: right; }
.je-td-idx { color: #94a3b8; width: 28px; font-size: 11px; }
.je-td-name { font-weight: 600; color: #1e293b; max-width: 200px; }
.je-td-qty { font-weight: 700; color: #0ea5e9; }
.je-td-subtotal { font-weight: 700; color: #15803d; }
.je-code { background: #f1f5f9; border-radius: 5px; padding: 2px 6px; font-size: 11px; font-family: monospace; color: #475569; }
.je-discount { color: #dc2626; font-weight: 600; }
.je-tax { color: #f59e0b; font-weight: 600; }
.je-foot-row { background: #f0fdf4; font-weight: 700; }
.je-foot-label { padding: 10px; color: #15803d; text-align: right; font-size: 13px; }
.je-foot-total { padding: 10px; color: #15803d; font-size: 14px; font-weight: 800; text-align: right; }
</style>
