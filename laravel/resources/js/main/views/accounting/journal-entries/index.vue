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
            <a-col :span="16" style="text-align:right; color:#64748b; font-size:13px; line-height:32px;">
                Click any row to expand entry details &nbsp;<DownOutlined style="font-size:11px" />
            </a-col>
        </a-row>

        <a-spin :spinning="loading">
            <a-table
                :dataSource="entries"
                :columns="columns"
                :pagination="pagination"
                @change="handleTableChange"
                rowKey="id"
                size="middle"
                :scroll="{ x: 1100 }"
                :expandable="expandable"
                :rowClassName="() => 'je-main-row'"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'entry_date'">
                        <span style="font-size:12px;color:#64748b">{{ formatDate(record.entry_date) }}</span>
                    </template>
                    <template v-if="column.key === 'entry_number'">
                        <span style="font-weight:700;color:#1e293b;font-family:monospace;font-size:12.5px">{{ record.entry_number }}</span>
                    </template>
                    <template v-if="column.key === 'description'">
                        <div style="font-weight:500;color:#334155">{{ record.description || '—' }}</div>
                        <div v-if="record.order_items && record.order_items.length" class="je-product-preview">
                            <span v-for="(item, i) in record.order_items.slice(0,2)" :key="i" class="je-prod-chip">
                                {{ item.product_name }} × {{ item.quantity }}
                            </span>
                            <span v-if="record.order_items.length > 2" class="je-prod-more">+{{ record.order_items.length - 2 }} more</span>
                        </div>
                    </template>
                    <template v-if="column.key === 'reference'">
                        <a-tag v-if="record.reference" color="blue" style="font-family:monospace">{{ record.reference }}</a-tag>
                        <span v-else class="text-gray-300">—</span>
                    </template>
                    <template v-if="column.key === 'total_debit'">
                        <span style="font-weight:700;color:#2563eb">{{ formatAmount(record.lines?.reduce((s,l)=>s+(+l.debit),0)) }}</span>
                    </template>
                    <template v-if="column.key === 'status'">
                        <a-tag :color="record.status==='posted'?'green':'orange'" style="font-size:11px">{{ record.status }}</a-tag>
                    </template>
                    <template v-if="column.key === 'action'">
                        <a-popconfirm title="Delete this entry?" @confirm="deleteEntry(record.id)" ok-text="Yes" cancel-text="No">
                            <a-button size="small" type="link" danger title="Delete"><DeleteOutlined /></a-button>
                        </a-popconfirm>
                    </template>
                </template>

                <!-- ── Expanded Row: Full Entry Detail ─────────────── -->
                <template #expandedRowRender="{ record }">
                    <div class="je-expanded">

                        <!-- Entry header strip -->
                        <div class="je-exp-header">
                            <div class="je-exp-badge"><FileTextOutlined /> {{ record.entry_number }}</div>
                            <div class="je-exp-meta">
                                <span><CalendarOutlined /> {{ formatDate(record.entry_date) }}</span>
                                <span v-if="record.reference"><LinkOutlined /> {{ record.reference }}</span>
                                <span v-if="record.order_type"><ShoppingOutlined /> {{ record.order_type }}</span>
                                <span><CheckCircleOutlined /> {{ record.status }}</span>
                            </div>
                            <div v-if="record.description" class="je-exp-desc">"{{ record.description }}"</div>
                        </div>

                        <div class="je-exp-body">

                            <!-- LEFT: Accounting Lines -->
                            <div class="je-exp-col je-exp-ledger">
                                <div class="je-exp-col-title"><AccountBookOutlined /> Ledger Lines</div>
                                <table class="je-ledger-table">
                                    <thead>
                                        <tr>
                                            <th>Account Code</th>
                                            <th>Account Name</th>
                                            <th>Type</th>
                                            <th>Note</th>
                                            <th class="je-right">Debit (PKR)</th>
                                            <th class="je-right">Credit (PKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="line in record.lines" :key="line.id" :class="line.debit > 0 ? 'je-dr-row' : 'je-cr-row'">
                                            <td><span class="je-code">{{ line.account?.account_code || '—' }}</span></td>
                                            <td class="je-acct-name">
                                                <span :class="line.debit > 0 ? 'je-dr-indicator' : 'je-cr-indicator'">
                                                    {{ +line.debit > 0 ? 'Dr' : 'Cr' }}
                                                </span>
                                                {{ line.account?.account_name || '—' }}
                                            </td>
                                            <td><span class="je-acct-type">{{ line.account?.account_type || '—' }}</span></td>
                                            <td style="color:#64748b;font-size:12px">{{ line.description || '—' }}</td>
                                            <td class="je-right">
                                                <span v-if="+line.debit > 0" class="je-dr-amount">{{ formatAmount(line.debit) }}</span>
                                                <span v-else class="text-gray-300">—</span>
                                            </td>
                                            <td class="je-right">
                                                <span v-if="+line.credit > 0" class="je-cr-amount">{{ formatAmount(line.credit) }}</span>
                                                <span v-else class="text-gray-300">—</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="je-total-row">
                                            <td colspan="4" class="je-total-label">Total</td>
                                            <td class="je-right je-total-dr">{{ formatAmount(record.lines?.reduce((s,l)=>s+(+l.debit),0)) }}</td>
                                            <td class="je-right je-total-cr">{{ formatAmount(record.lines?.reduce((s,l)=>s+(+l.credit),0)) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- RIGHT: Products (only if linked to order) -->
                            <div v-if="record.order_items && record.order_items.length" class="je-exp-col je-exp-products">
                                <div class="je-exp-col-title"><ShoppingOutlined /> Products Sold</div>
                                <table class="je-product-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th>Code</th>
                                            <th class="je-right">Qty</th>
                                            <th class="je-right">Price</th>
                                            <th class="je-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, i) in record.order_items" :key="i">
                                            <td class="je-td-idx">{{ i+1 }}</td>
                                            <td class="je-td-name">{{ item.product_name }}</td>
                                            <td><span class="je-code">{{ item.item_code || '—' }}</span></td>
                                            <td class="je-right je-td-qty">{{ item.quantity }}</td>
                                            <td class="je-right">{{ formatAmount(item.unit_price) }}</td>
                                            <td class="je-right je-td-subtotal">{{ formatAmount(item.subtotal) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="je-total-row">
                                            <td colspan="5" class="je-total-label">Total</td>
                                            <td class="je-right je-total-cr">{{ formatAmount(record.order_items.reduce((s,i)=>s+(+i.subtotal),0)) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                        </div><!-- /je-exp-body -->
                    </div><!-- /je-expanded -->
                </template>

            </a-table>
        </a-spin>
    </a-card>

    <!-- ─── New Journal Entry Modal ─────────────────────────────────── -->
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

            <a-table :dataSource="entryForm.lines" :columns="lineColumns" :pagination="false" size="small" rowKey="key">
                <template #bodyCell="{ column, record, index }">
                    <template v-if="column.key === 'account_id'">
                        <a-select v-model:value="record.account_id" style="width:100%" show-search :filter-option="filterAccount" placeholder="Select account">
                            <a-select-option v-for="a in allAccounts" :key="a.id" :value="a.id">{{ a.account_code }} - {{ a.account_name }}</a-select-option>
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
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <a-button type="dashed" size="small" @click="addLine"><PlusOutlined /> Add Line</a-button>
                        <div style="font-weight:600;font-size:13px">
                            <span class="mr-4">Debit: <span style="color:#2563eb">{{ totalDebit.toFixed(2) }}</span></span>
                            <span>Credit: <span style="color:#16a34a">{{ totalCredit.toFixed(2) }}</span></span>
                            <span v-if="!isBalanced" style="color:#dc2626;margin-left:12px">⚠ Not balanced</span>
                            <span v-else style="color:#16a34a;margin-left:12px">✓ Balanced</span>
                        </div>
                    </div>
                </template>
            </a-table>
        </a-form>
    </a-modal>
</template>

<script>
import { defineComponent, ref, computed, onMounted, h } from 'vue';
import {
    PlusOutlined, DeleteOutlined, MinusCircleOutlined,
    ShoppingOutlined, AccountBookOutlined, FileTextOutlined,
    CalendarOutlined, LinkOutlined, CheckCircleOutlined, DownOutlined
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: {
        AdminPageHeader,
        PlusOutlined, DeleteOutlined, MinusCircleOutlined,
        ShoppingOutlined, AccountBookOutlined, FileTextOutlined,
        CalendarOutlined, LinkOutlined, CheckCircleOutlined, DownOutlined
    },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading = ref(false);
        const saving = ref(false);
        const entries = ref([]);
        const allAccounts = ref([]);
        const dateRange = ref(null);
        const pagination = ref({ current: 1, pageSize: 20, total: 0 });
        const addModalVisible = ref(false);

        const newLine = () => ({ key: Date.now() + Math.random(), account_id: null, description: '', debit: 0, credit: 0 });
        const entryForm = ref({ entry_date: dayjs(), reference: '', description: '', lines: [newLine(), newLine()] });

        const totalDebit = computed(() => entryForm.value.lines.reduce((s, l) => s + (+l.debit || 0), 0));
        const totalCredit = computed(() => entryForm.value.lines.reduce((s, l) => s + (+l.credit || 0), 0));
        const isBalanced = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.01 && totalDebit.value > 0);

        const columns = [
            { title: 'Entry #', key: 'entry_number', width: 190 },
            { title: 'Date', key: 'entry_date', width: 110 },
            { title: 'Description / Products', key: 'description' },
            { title: 'Reference', key: 'reference', width: 120 },
            { title: 'Total (PKR)', key: 'total_debit', width: 140, align: 'right' },
            { title: 'Status', key: 'status', width: 90 },
            { title: '', key: 'action', width: 60, fixed: 'right' },
        ];

        const lineColumns = [
            { title: 'Account', key: 'account_id', width: 260 },
            { title: 'Description', key: 'description' },
            { title: 'Debit', key: 'debit', width: 130 },
            { title: 'Credit', key: 'credit', width: 130 },
            { title: '', key: 'remove', width: 50 },
        ];

        // Expandable config — all rows expandable, click row to expand
        const expandedKeys = ref([]);
        const expandable = computed(() => ({
            expandedRowKeys: expandedKeys.value,
            onExpand: (expanded, record) => {
                expandedKeys.value = expanded
                    ? [...expandedKeys.value, record.id]
                    : expandedKeys.value.filter(k => k !== record.id);
            },
            rowExpandable: () => true,
        }));

        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '-';
        const formatAmount = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const filterAccount = (input, option) => {
            const label = String(option?.label || '').toLowerCase();
            return label.includes(input.toLowerCase());
        };

        const loadAccounts = async () => {
            const res = await axiosAdmin.get('accounting/coa');
            allAccounts.value = (res.data.flat || []).filter(a => a.parent_id);
        };

        const loadEntries = async () => {
            loading.value = true;
            try {
                const params = { per_page: pagination.value.pageSize, page: pagination.value.current };
                if (dateRange.value?.[0]) {
                    params.date_from = dateRange.value[0].format('YYYY-MM-DD');
                    params.date_to = dateRange.value[1].format('YYYY-MM-DD');
                }
                const res = await axiosAdmin.get('accounting/journal-entries', { params });
                entries.value = res.data.data || res.data;
                pagination.value.total = res.data.total || entries.value.length;
                expandedKeys.value = [];
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

        const deleteEntry = async (id) => {
            try { await axiosAdmin.delete(`accounting/journal-entries/${id}`); message.success('Deleted'); loadEntries(); }
            catch (e) { message.error('Cannot delete'); }
        };

        onMounted(() => { loadAccounts(); loadEntries(); });

        return {
            loading, saving, entries, allAccounts, dateRange, pagination,
            addModalVisible, entryForm, totalDebit, totalCredit, isBalanced,
            columns, lineColumns, expandable,
            formatDate, formatAmount, filterAccount,
            loadEntries, handleTableChange, openAddModal, addLine, removeLine, saveEntry, deleteEntry,
        };
    }
});
</script>

<style scoped>
/* ── Row hover ── */
:deep(.je-main-row:hover > td) { background: #f8fafc !important; cursor: pointer; }
:deep(.ant-table-expanded-row > td) { background: #f8fafc !important; padding: 0 !important; }

/* ── Expanded wrapper ── */
.je-expanded { padding: 0; }

/* ── Header strip ── */
.je-exp-header {
    background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
    color: #fff; padding: 12px 20px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
}
.je-exp-badge { font-family: monospace; font-size: 13px; font-weight: 800; background: rgba(255,255,255,.15); padding: 4px 12px; border-radius: 8px; }
.je-exp-meta { display: flex; gap: 16px; font-size: 12px; opacity: .85; }
.je-exp-meta span { display: flex; align-items: center; gap: 4px; }
.je-exp-desc { margin-left: auto; font-style: italic; font-size: 12px; opacity: .75; }

/* ── Body ── */
.je-exp-body { display: flex; gap: 0; border-top: none; }
.je-exp-col { flex: 1; padding: 16px 20px; }
.je-exp-products { border-left: 1px solid #e2e8f0; flex: 0 0 420px; }
.je-exp-col-title {
    font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #475569; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
}

/* ── Ledger table ── */
.je-ledger-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.je-ledger-table thead tr { background: #f1f5f9; }
.je-ledger-table th { padding: 7px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .3px; border-bottom: 2px solid #e2e8f0; }
.je-ledger-table td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; }
.je-ledger-table tbody tr:last-child td { border-bottom: none; }
.je-dr-row { background: #eff6ff; }
.je-cr-row { background: #f0fdf4; }
.je-dr-row:hover { background: #dbeafe !important; }
.je-cr-row:hover { background: #dcfce7 !important; }
.je-dr-indicator { display: inline-block; background: #2563eb; color: #fff; font-size: 10px; font-weight: 800; padding: 1px 5px; border-radius: 4px; margin-right: 5px; }
.je-cr-indicator { display: inline-block; background: #16a34a; color: #fff; font-size: 10px; font-weight: 800; padding: 1px 5px; border-radius: 4px; margin-right: 5px; }
.je-acct-name { font-weight: 600; color: #1e293b; }
.je-acct-type { background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 11px; color: #64748b; }
.je-dr-amount { font-weight: 700; color: #2563eb; }
.je-cr-amount { font-weight: 700; color: #16a34a; }
.je-total-row { background: #f8fafc; font-weight: 700; }
.je-total-label { padding: 10px; color: #334155; font-size: 13px; }
.je-total-dr { color: #2563eb; font-size: 13px; font-weight: 800; padding: 10px; }
.je-total-cr { color: #16a34a; font-size: 13px; font-weight: 800; padding: 10px; }

/* ── Product table ── */
.je-product-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.je-product-table thead tr { background: #f0fdf4; }
.je-product-table th { padding: 7px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase; letter-spacing: .3px; border-bottom: 2px solid #bbf7d0; }
.je-product-table td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.je-product-table tbody tr:hover { background: #f8fafc; }
.je-product-table tbody tr:last-child td { border-bottom: none; }

/* ── Shared ── */
.je-right { text-align: right; }
.je-td-idx { color: #94a3b8; width: 24px; font-size: 11px; }
.je-td-name { font-weight: 600; color: #1e293b; }
.je-td-qty { font-weight: 700; color: #0ea5e9; }
.je-td-subtotal { font-weight: 700; color: #15803d; }
.je-code { background: #f1f5f9; border-radius: 4px; padding: 2px 6px; font-size: 11px; font-family: monospace; color: #475569; }

/* ── Chips in list ── */
.je-product-preview { margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px; }
.je-prod-chip { display: inline-block; padding: 1px 8px; border-radius: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; font-size: 11px; font-weight: 600; }
.je-prod-more { display: inline-block; padding: 1px 8px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; font-size: 11px; }
</style>
