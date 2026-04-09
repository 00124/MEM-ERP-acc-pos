<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Journal Entries" class="p-0">
                <template #extra>
                    <button class="je-hdr-btn je-hdr-ob" @click="openObModal"><BankOutlined /> Opening Balance</button>
                    <button class="je-hdr-btn je-hdr-new" @click="openAddModal"><PlusOutlined /> New Entry</button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="/" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Journal Entries</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- ── Hero ─────────────────────────────────────────────────────── -->
    <div class="je-hero">
        <div class="je-hero-glow je-glow-1"></div>
        <div class="je-hero-glow je-glow-2"></div>

        <div class="je-hero-top">
            <div class="je-hero-brand">
                <span class="je-hero-icon"><AccountBookOutlined /></span>
                <div>
                    <h1 class="je-h1">Journal Entries</h1>
                    <p class="je-sub">Double-entry accounting ledger</p>
                </div>
            </div>

            <!-- Health KPIs -->
            <div v-if="health" class="je-kpi-row">
                <div class="je-kpi je-kpi-glass">
                    <div class="je-kpi-val">{{ health.total_entries }}</div>
                    <div class="je-kpi-lbl">Total Entries</div>
                </div>
                <div class="je-kpi je-kpi-glass">
                    <div class="je-kpi-val je-kpi-ok">{{ health.balanced }}</div>
                    <div class="je-kpi-lbl">Balanced</div>
                </div>
                <div class="je-kpi je-kpi-glass" :class="health.imbalanced?.length ? 'je-kpi-danger' : ''">
                    <div class="je-kpi-val" :class="health.imbalanced?.length ? 'je-kpi-red' : 'je-kpi-ok'">
                        {{ health.imbalanced?.length ?? 0 }}
                    </div>
                    <div class="je-kpi-lbl">Imbalanced</div>
                </div>
                <div class="je-kpi je-kpi-glass">
                    <div class="je-kpi-val" :class="health.je_coverage_pct < 100 ? 'je-kpi-warn' : 'je-kpi-ok'">{{ health.je_coverage_pct }}%</div>
                    <div class="je-kpi-lbl">Coverage</div>
                </div>
                <div class="je-kpi je-kpi-glass">
                    <div class="je-kpi-val" :class="health.cogs_entries === 0 ? 'je-kpi-red' : 'je-kpi-ok'">{{ health.cogs_entries }}</div>
                    <div class="je-kpi-lbl">COGS Entries</div>
                </div>
            </div>
        </div>

        <!-- Imbalanced alert -->
        <div v-if="health?.imbalanced?.length" class="je-imbalance-bar">
            <ExclamationCircleOutlined class="je-imb-icon" />
            <span><b>{{ health.imbalanced.length }} imbalanced</b> entries detected: {{ health.imbalanced.map(e => e.entry_number).join(', ') }}</span>
        </div>

        <!-- Filter bar -->
        <div class="je-filter-bar">
            <div class="je-filter-field">
                <label class="je-filter-lbl"><CalendarOutlined /> Date Range</label>
                <a-range-picker v-model:value="dateRange" class="je-filter-input" @change="loadEntries" />
            </div>
            <div class="je-filter-hint">
                <DownOutlined style="font-size:10px;margin-right:4px" />
                Click any row to expand entry details
            </div>
        </div>
    </div>

    <!-- ── Table Card ─────────────────────────────────────────────── -->
    <div class="je-table-card">
        <div class="je-table-hdr">
            <div class="je-table-title">
                <span class="je-tbl-dot"></span>
                Ledger
                <span class="je-tbl-count">{{ pagination.total }} entries</span>
            </div>
        </div>

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
                class="je-table"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'entry_number'">
                        <span class="je-entry-num"><FileTextOutlined class="je-en-ico" /> {{ record.entry_number }}</span>
                    </template>
                    <template v-if="column.key === 'entry_date'">
                        <span class="je-date-cell">{{ formatDate(record.entry_date) }}</span>
                    </template>
                    <template v-if="column.key === 'description'">
                        <div class="je-desc">{{ record.description || '—' }}</div>
                        <div v-if="record.order_items?.length" class="je-chip-row">
                            <span v-for="(item, i) in record.order_items.slice(0,2)" :key="i" class="je-chip">
                                {{ item.product_name }} × {{ item.quantity }}
                            </span>
                            <span v-if="record.order_items.length > 2" class="je-chip-more">+{{ record.order_items.length - 2 }}</span>
                        </div>
                    </template>
                    <template v-if="column.key === 'reference'">
                        <span v-if="record.reference" class="je-ref-pill"><LinkOutlined /> {{ record.reference }}</span>
                        <span v-else class="je-nil">—</span>
                    </template>
                    <template v-if="column.key === 'total_debit'">
                        <span class="je-amount">{{ formatAmount(record.lines?.reduce((s,l)=>s+(+l.debit),0)) }}</span>
                    </template>
                    <template v-if="column.key === 'status'">
                        <span class="je-status-pill" :class="record.status==='posted'?'je-status-posted':'je-status-draft'">
                            <span class="je-status-dot"></span>
                            {{ record.status }}
                        </span>
                    </template>
                    <template v-if="column.key === 'action'">
                        <a-popconfirm title="Delete this journal entry?" @confirm="deleteEntry(record.id)" ok-text="Delete" cancel-text="Cancel">
                            <button class="je-del-btn"><DeleteOutlined /></button>
                        </a-popconfirm>
                    </template>
                </template>

                <!-- ── Expanded Row ── -->
                <template #expandedRowRender="{ record }">
                    <div class="je-expanded">

                        <!-- Entry header strip -->
                        <div class="je-exp-strip">
                            <div class="je-exp-num"><FileTextOutlined /> {{ record.entry_number }}</div>
                            <div class="je-exp-pills">
                                <span class="je-exp-pill"><CalendarOutlined /> {{ formatDate(record.entry_date) }}</span>
                                <span v-if="record.reference" class="je-exp-pill"><LinkOutlined /> {{ record.reference }}</span>
                                <span v-if="record.order_type" class="je-exp-pill je-pill-type"><ShoppingOutlined /> {{ record.order_type }}</span>
                                <span class="je-exp-pill je-pill-status">{{ record.status }}</span>
                            </div>
                            <div v-if="record.description" class="je-exp-desc">"{{ record.description }}"</div>
                        </div>

                        <div class="je-exp-body">

                            <!-- LEFT: Ledger Lines -->
                            <div class="je-exp-pane je-pane-ledger">
                                <div class="je-pane-title"><AccountBookOutlined /> Ledger Lines</div>
                                <table class="je-ledger-tbl">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Account</th>
                                            <th>Type</th>
                                            <th>Note</th>
                                            <th class="r">Debit (PKR)</th>
                                            <th class="r">Credit (PKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="line in record.lines" :key="line.id" :class="line.debit>0?'je-dr-row':'je-cr-row'">
                                            <td><code class="je-code">{{ line.account?.account_code || '—' }}</code></td>
                                            <td class="je-acct-nm">
                                                <span :class="line.debit>0?'je-dr-tag':'je-cr-tag'">{{ +line.debit>0?'Dr':'Cr' }}</span>
                                                {{ line.account?.account_name || '—' }}
                                            </td>
                                            <td><span class="je-type-chip">{{ line.account?.account_type || '—' }}</span></td>
                                            <td class="je-note">{{ line.description || '—' }}</td>
                                            <td class="r">
                                                <span v-if="+line.debit>0" class="je-dr-amt">{{ formatAmount(line.debit) }}</span>
                                                <span v-else class="je-nil">—</span>
                                            </td>
                                            <td class="r">
                                                <span v-if="+line.credit>0" class="je-cr-amt">{{ formatAmount(line.credit) }}</span>
                                                <span v-else class="je-nil">—</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="je-ledger-foot">
                                            <td colspan="4" class="je-foot-lbl">Total</td>
                                            <td class="r je-foot-dr">{{ formatAmount(record.lines?.reduce((s,l)=>s+(+l.debit),0)) }}</td>
                                            <td class="r je-foot-cr">{{ formatAmount(record.lines?.reduce((s,l)=>s+(+l.credit),0)) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- RIGHT: Products -->
                            <div v-if="record.order_items?.length" class="je-exp-pane je-pane-products">
                                <div class="je-pane-title je-pane-title-green"><ShoppingOutlined /> Products</div>
                                <table class="je-prod-tbl">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th>Code</th>
                                            <th class="r">Qty</th>
                                            <th class="r">Price</th>
                                            <th class="r">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, i) in record.order_items" :key="i">
                                            <td class="je-idx">{{ i+1 }}</td>
                                            <td class="je-pname">{{ item.product_name }}</td>
                                            <td><code v-if="item.item_code" class="je-code je-code-green">{{ item.item_code }}</code></td>
                                            <td class="r je-qty">{{ item.quantity }}</td>
                                            <td class="r je-uprice">{{ formatAmount(item.unit_price) }}</td>
                                            <td class="r je-psub">{{ formatAmount(item.subtotal) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="je-prod-foot">
                                            <td colspan="5" class="je-foot-lbl">Total</td>
                                            <td class="r je-foot-cr">{{ formatAmount(record.order_items.reduce((s,i)=>s+(+i.subtotal),0)) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </a-table>
        </a-spin>
    </div>

    <!-- ── New Journal Entry Modal ──────────────────────────────── -->
    <a-modal v-model:open="addModalVisible" :footer="null" width="900px" class="je-modal" :bodyStyle="{ padding:0 }">
        <template #title>
            <div class="je-modal-hd">
                <span class="je-modal-ico"><FileTextOutlined /></span>
                New Journal Entry
            </div>
        </template>

        <div class="je-modal-body">
            <!-- Meta row -->
            <div class="je-modal-meta">
                <div class="je-modal-field">
                    <label class="je-modal-lbl">Date <span class="je-req">*</span></label>
                    <a-date-picker v-model:value="entryForm.entry_date" class="je-modal-input" />
                </div>
                <div class="je-modal-field">
                    <label class="je-modal-lbl">Reference</label>
                    <a-input v-model:value="entryForm.reference" class="je-modal-input" placeholder="Invoice #, PO #…" />
                </div>
                <div class="je-modal-field" style="flex:1.5">
                    <label class="je-modal-lbl">Description</label>
                    <a-input v-model:value="entryForm.description" class="je-modal-input" placeholder="Entry description" />
                </div>
            </div>

            <!-- Lines table -->
            <div class="je-modal-lines">
                <div class="je-modal-lines-hd">
                    <span class="je-lines-title"><AccountBookOutlined /> Ledger Lines</span>
                    <div class="je-balance-strip" :class="isBalanced ? 'je-balanced' : 'je-unbalanced'">
                        <span>Dr: <b>{{ totalDebit.toFixed(2) }}</b></span>
                        <span>Cr: <b>{{ totalCredit.toFixed(2) }}</b></span>
                        <span class="je-bal-status">{{ isBalanced ? '✓ Balanced' : '⚠ Not balanced' }}</span>
                    </div>
                </div>

                <table class="je-entry-tbl">
                    <thead>
                        <tr>
                            <th style="width:280px">Account</th>
                            <th>Description</th>
                            <th style="width:140px">Debit (PKR)</th>
                            <th style="width:140px">Credit (PKR)</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(line, index) in entryForm.lines" :key="line.key" class="je-entry-row">
                            <td>
                                <a-select v-model:value="line.account_id" style="width:100%" show-search :filter-option="filterAccount" placeholder="Select account" size="small">
                                    <a-select-option v-for="a in allAccounts" :key="a.id" :value="a.id">{{ a.account_code }} — {{ a.account_name }}</a-select-option>
                                </a-select>
                            </td>
                            <td>
                                <a-input v-model:value="line.description" placeholder="Line note" size="small" />
                            </td>
                            <td>
                                <a-input-number v-model:value="line.debit" :min="0" :precision="2" style="width:100%" size="small"
                                    @change="()=>{ if(line.debit>0) line.credit=0; }" />
                            </td>
                            <td>
                                <a-input-number v-model:value="line.credit" :min="0" :precision="2" style="width:100%" size="small"
                                    @change="()=>{ if(line.credit>0) line.debit=0; }" />
                            </td>
                            <td style="text-align:center">
                                <button class="je-rm-btn" @click="removeLine(index)" :disabled="entryForm.lines.length<=2">
                                    <MinusCircleOutlined />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="je-entry-footer">
                    <button class="je-add-line-btn" @click="addLine"><PlusOutlined /> Add Line</button>
                    <div class="je-modal-actions">
                        <button class="je-cancel-btn" @click="addModalVisible=false">Cancel</button>
                        <button class="je-save-btn" @click="saveEntry" :disabled="saving || !isBalanced">
                            <span v-if="saving" class="je-spinner"></span>
                            <CheckCircleOutlined v-else />
                            {{ saving ? 'Posting…' : 'Post Entry' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </a-modal>

    <!-- ── Opening Balance Modal ─────────────────────────────────── -->
    <a-modal v-model:open="obModalVisible" :footer="null" width="780px" class="je-modal" :bodyStyle="{ padding:0 }">
        <template #title>
            <div class="je-modal-hd je-modal-hd-ob">
                <span class="je-modal-ico je-modal-ico-ob"><BankOutlined /></span>
                Post Opening Balance
            </div>
        </template>

        <div class="je-modal-body">
            <div class="je-ob-info">
                <InfoCircleOutlined style="color:#3b82f6;font-size:15px;flex-shrink:0" />
                <span>Opening balances create a special Journal Entry dated at the start of your accounting period. Debits and Credits must balance.</span>
            </div>

            <div class="je-modal-meta" style="margin-bottom:0;border-bottom:1px solid #f1f5f9;padding-bottom:18px;">
                <div class="je-modal-field">
                    <label class="je-modal-lbl">As of Date</label>
                    <a-date-picker v-model:value="obForm.as_of_date" class="je-modal-input" />
                </div>
            </div>

            <div class="je-modal-lines">
                <div class="je-modal-lines-hd">
                    <span class="je-lines-title"><AccountBookOutlined /> Account Balances</span>
                    <div class="je-balance-strip" :class="obIsBalanced ? 'je-balanced' : 'je-unbalanced'">
                        <span>Dr: <b>{{ obDebit.toLocaleString('en-PK',{minimumFractionDigits:2}) }}</b></span>
                        <span>Cr: <b>{{ obCredit.toLocaleString('en-PK',{minimumFractionDigits:2}) }}</b></span>
                        <span class="je-bal-status">{{ obIsBalanced ? '✓ Balanced' : '⚠ Not balanced' }}</span>
                    </div>
                </div>

                <div class="je-ob-lines">
                    <div v-for="(line, idx) in obForm.lines" :key="idx" class="je-ob-line">
                        <a-select v-model:value="line.account_id" show-search :filter-option="filterAccount"
                            placeholder="Select account" style="flex:1" size="small"
                            :options="allAccounts.map(a => ({ value: a.id, label: (a.account_code?a.account_code+' — ':'')+a.account_name }))" />
                        <a-select v-model:value="line.balance_type" style="width:96px" size="small">
                            <a-select-option value="debit">Debit</a-select-option>
                            <a-select-option value="credit">Credit</a-select-option>
                        </a-select>
                        <a-input-number v-model:value="line.amount" :min="0" :precision="2" style="width:140px" size="small" placeholder="Amount" />
                        <button class="je-rm-btn" @click="removeObLine(idx)" :disabled="obForm.lines.length<=1">
                            <DeleteOutlined />
                        </button>
                    </div>
                </div>

                <div class="je-entry-footer">
                    <button class="je-add-line-btn" @click="addObLine"><PlusOutlined /> Add Account</button>
                    <div class="je-modal-actions">
                        <button class="je-cancel-btn" @click="obModalVisible=false">Cancel</button>
                        <button class="je-save-btn je-save-ob" @click="saveOpeningBalance" :disabled="obSaving">
                            <span v-if="obSaving" class="je-spinner je-spinner-ob"></span>
                            <BankOutlined v-else />
                            {{ obSaving ? 'Posting…' : 'Post Opening Balance' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </a-modal>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import {
    PlusOutlined, DeleteOutlined, MinusCircleOutlined,
    ShoppingOutlined, AccountBookOutlined, FileTextOutlined,
    CalendarOutlined, LinkOutlined, CheckCircleOutlined, DownOutlined,
    BankOutlined, ExclamationCircleOutlined, InfoCircleOutlined,
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: {
        AdminPageHeader,
        PlusOutlined, DeleteOutlined, MinusCircleOutlined,
        ShoppingOutlined, AccountBookOutlined, FileTextOutlined,
        CalendarOutlined, LinkOutlined, CheckCircleOutlined, DownOutlined,
        BankOutlined, ExclamationCircleOutlined, InfoCircleOutlined,
    },
    setup() {
        const axiosAdmin    = window.axiosAdmin;
        const loading       = ref(false);
        const saving        = ref(false);
        const entries       = ref([]);
        const allAccounts   = ref([]);
        const dateRange     = ref(null);
        const pagination    = ref({ current: 1, pageSize: 20, total: 0 });
        const addModalVisible = ref(false);

        // ── Health ──────────────────────────────────────────────────
        const health = ref(null);
        const loadHealth = async () => {
            try { const res = await axiosAdmin.get('accounting/je-health'); health.value = res.data; } catch (_) {}
        };

        // ── Opening Balance ─────────────────────────────────────────
        const obModalVisible = ref(false);
        const obSaving       = ref(false);
        const obForm         = ref({ as_of_date: dayjs(), lines: [] });

        const addObLine    = () => obForm.value.lines.push({ account_id: null, balance_type: 'debit', amount: 0 });
        const removeObLine = (i) => obForm.value.lines.splice(i, 1);

        const openObModal = () => {
            obForm.value = { as_of_date: dayjs(), lines: [{ account_id:null, balance_type:'debit', amount:0 }, { account_id:null, balance_type:'credit', amount:0 }] };
            obModalVisible.value = true;
        };

        const obDebit    = computed(() => obForm.value.lines.filter(l=>l.balance_type==='debit').reduce((s,l)=>s+(+l.amount||0),0));
        const obCredit   = computed(() => obForm.value.lines.filter(l=>l.balance_type==='credit').reduce((s,l)=>s+(+l.amount||0),0));
        const obIsBalanced = computed(() => Math.abs(obDebit.value - obCredit.value) < 0.01 && obDebit.value > 0);

        const saveOpeningBalance = async () => {
            const validLines = obForm.value.lines.filter(l => l.account_id && l.amount > 0);
            if (!validLines.length) { message.warning('Add at least one account with an amount'); return; }
            if (Math.abs(obDebit.value - obCredit.value) > 0.01) {
                message.warning(`Must balance. Dr: ${obDebit.value.toFixed(2)} — Cr: ${obCredit.value.toFixed(2)}`);
                return;
            }
            obSaving.value = true;
            try {
                const res = await axiosAdmin.post('accounting/opening-balance', { as_of_date: obForm.value.as_of_date.format('YYYY-MM-DD'), lines: validLines });
                message.success(res.data.message || 'Opening balance posted');
                obModalVisible.value = false;
                loadEntries(); loadHealth();
            } catch (e) { message.error(e.response?.data?.message || 'Error posting opening balance'); }
            finally { obSaving.value = false; }
        };

        // ── JE Form ─────────────────────────────────────────────────
        const newLine     = () => ({ key: Date.now() + Math.random(), account_id: null, description: '', debit: 0, credit: 0 });
        const entryForm   = ref({ entry_date: dayjs(), reference: '', description: '', lines: [newLine(), newLine()] });
        const totalDebit  = computed(() => entryForm.value.lines.reduce((s,l) => s + (+l.debit||0), 0));
        const totalCredit = computed(() => entryForm.value.lines.reduce((s,l) => s + (+l.credit||0), 0));
        const isBalanced  = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.01 && totalDebit.value > 0);

        const columns = [
            { title: 'Entry #',            key: 'entry_number', width: 200 },
            { title: 'Date',               key: 'entry_date',   width: 115 },
            { title: 'Description',        key: 'description' },
            { title: 'Reference',          key: 'reference',    width: 130 },
            { title: 'Total (PKR)',         key: 'total_debit',  width: 145, align: 'right' },
            { title: 'Status',             key: 'status',       width: 100 },
            { title: '',                   key: 'action',       width: 56,  fixed: 'right' },
        ];

        const expandedKeys = ref([]);
        const expandable   = computed(() => ({
            expandedRowKeys: expandedKeys.value,
            onExpand: (expanded, record) => {
                expandedKeys.value = expanded
                    ? [...expandedKeys.value, record.id]
                    : expandedKeys.value.filter(k => k !== record.id);
            },
            rowExpandable: () => true,
        }));

        const formatDate    = (d) => d ? dayjs(d).format('DD MMM YYYY') : '-';
        const formatAmount  = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const filterAccount = (input, option) => String(option?.label || '').toLowerCase().includes(input.toLowerCase());

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
                expandedKeys.value = [];
            } catch { message.error('Failed to load entries'); }
            finally { loading.value = false; }
        };

        const handleTableChange = (pag) => { pagination.value.current = pag.current; loadEntries(); };

        const openAddModal  = () => { entryForm.value = { entry_date: dayjs(), reference:'', description:'', lines:[newLine(),newLine()] }; addModalVisible.value = true; };
        const addLine       = () => entryForm.value.lines.push(newLine());
        const removeLine    = (i) => entryForm.value.lines.splice(i, 1);

        const saveEntry = async () => {
            if (!isBalanced.value) { message.warning('Debit and Credit totals must be equal'); return; }
            saving.value = true;
            try {
                await axiosAdmin.post('accounting/journal-entries', {
                    entry_date: entryForm.value.entry_date.format('YYYY-MM-DD'),
                    reference: entryForm.value.reference,
                    description: entryForm.value.description,
                    lines: entryForm.value.lines.filter(l => l.account_id),
                });
                message.success('Journal entry posted');
                addModalVisible.value = false;
                loadEntries();
            } catch (e) { message.error(e.response?.data?.message || 'Error saving entry'); }
            finally { saving.value = false; }
        };

        const deleteEntry = async (id) => {
            try { await axiosAdmin.delete(`accounting/journal-entries/${id}`); message.success('Deleted'); loadEntries(); }
            catch { message.error('Cannot delete'); }
        };

        onMounted(() => { loadAccounts(); loadEntries(); loadHealth(); });

        return {
            loading, saving, entries, allAccounts, dateRange, pagination,
            addModalVisible, entryForm, totalDebit, totalCredit, isBalanced,
            columns, expandable, expandedKeys,
            formatDate, formatAmount, filterAccount,
            loadEntries, handleTableChange, openAddModal, addLine, removeLine, saveEntry, deleteEntry,
            health,
            obModalVisible, obSaving, obForm, obDebit, obCredit, obIsBalanced,
            openObModal, addObLine, removeObLine, saveOpeningBalance,
        };
    }
});
</script>

<style scoped>
.r { text-align: right; }

/* ══ HEADER BUTTONS ════════════════════════════════════════════ */
.je-hdr-btn {
    display: inline-flex; align-items: center; gap: 7px;
    border: none; border-radius: 9px; cursor: pointer;
    font-size: 13px; font-weight: 700; height: 34px; padding: 0 16px;
    transition: all .2s;
}
.je-hdr-ob  { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; margin-right: 8px; }
.je-hdr-ob:hover  { background: #ede9fe; }
.je-hdr-new { background: linear-gradient(135deg,#0f766e,#0d9488); color: #fff; }
.je-hdr-new:hover { opacity: .9; transform: translateY(-1px); }

/* ══ HERO ══════════════════════════════════════════════════════ */
.je-hero {
    position: relative; overflow: hidden;
    border-radius: 0; margin-bottom: 0;
    padding: 24px 32px 0;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
}
.je-hero-glow { display:none; }
.je-glow-1 { display:none; }
.je-glow-2 { display:none; }

.je-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
.je-hero-brand { display:flex; align-items:center; gap:18px; }
.je-hero-icon {
    font-size:24px; color:#fff;
    background: linear-gradient(135deg, #0d9488, #0f766e);
    border:none; border-radius:14px;
    width:54px; height:54px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
    box-shadow: 0 4px 12px rgba(13,148,136,.3);
}
.je-h1  { font-size:22px; font-weight:800; color:#1e293b; margin:0 0 4px; letter-spacing:-.4px; }
.je-sub { font-size:13px; color:#64748b; margin:0; }

/* KPI chips */
.je-kpi-row { display:flex; gap:10px; flex-wrap:wrap; }
.je-kpi { padding:10px 18px; border-radius:12px; text-align:center; min-width:90px; }
.je-kpi-glass { background:#f8fafc; border:1px solid #e2e8f0; }
.je-kpi-danger { background:#fef2f2!important; border-color:#fecaca!important; animation:je-pulse 1.6s infinite; }
.je-kpi-val  { font-size:18px; font-weight:800; color:#1e293b; line-height:1.1; }
.je-kpi-lbl  { font-size:10px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-top:3px; }
.je-kpi-ok   { color:#059669!important; }
.je-kpi-red  { color:#dc2626!important; }
.je-kpi-warn { color:#d97706!important; }
@keyframes je-pulse { 0%,100%{opacity:1} 50%{opacity:.65} }

/* Imbalance bar */
.je-imbalance-bar {
    background:#fef2f2; border:1px solid #fecaca; border-radius:10px;
    padding:10px 18px; color:#991b1b; font-size:13px; margin-bottom:16px;
    display:flex; align-items:center; gap:10px;
}
.je-imb-icon { color:#dc2626; font-size:16px; flex-shrink:0; }

/* Filter bar */
.je-filter-bar {
    display:flex; align-items:flex-end; justify-content:space-between; gap:16px;
    background:#f8fafc;
    border-top:1px solid #e2e8f0;
    padding:16px 32px 20px; margin:0 -32px;
}
.je-filter-field { display:flex; flex-direction:column; gap:5px; }
.je-filter-lbl   { font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.6px; display:flex; align-items:center; gap:5px; }
.je-filter-input { border-radius:10px!important; }
.je-filter-hint  { font-size:12px; color:#94a3b8; padding-bottom:2px; display:flex; align-items:center; }

/* ══ TABLE CARD ════════════════════════════════════════════════ */
.je-table-card { background:#fff; border-radius:18px; border:1px solid #e2e8f0; box-shadow:0 4px 24px rgba(0,0,0,.06); overflow:hidden; }
.je-table-hdr  { padding:14px 22px; border-bottom:1px solid #f1f5f9; background:#fafbfc; display:flex; align-items:center; }
.je-table-title { display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; color:#0f172a; }
.je-tbl-dot  { width:10px; height:10px; border-radius:50%; background:#0d9488; box-shadow:0 0 0 3px rgba(13,148,136,.2); }
.je-tbl-count { font-size:11px; font-weight:600; background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:20px; }

/* Table cells */
:deep(.je-main-row:hover > td) { background:#f0fdfa!important; cursor:pointer; }
:deep(.ant-table-expanded-row > td) { background:#f8fafc!important; padding:0!important; }

.je-entry-num { display:flex; align-items:center; gap:6px; font-family:monospace; font-size:12.5px; font-weight:700; color:#0f766e; }
.je-en-ico    { font-size:11px; opacity:.7; }
.je-date-cell { font-size:12.5px; color:#64748b; }
.je-desc      { font-weight:500; color:#334155; font-size:13px; }
.je-nil       { color:#cbd5e1; }
.je-chip-row  { display:flex; flex-wrap:wrap; gap:4px; margin-top:4px; }
.je-chip      { padding:1px 8px; border-radius:10px; background:#f0fdfa; border:1px solid #99f6e4; color:#0f766e; font-size:11px; font-weight:600; }
.je-chip-more { padding:1px 8px; border-radius:10px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; font-size:11px; }
.je-ref-pill  { display:inline-flex; align-items:center; gap:4px; background:#f1f5f9; border-radius:6px; font-size:12px; color:#475569; font-family:monospace; padding:2px 8px; }
.je-amount    { font-weight:700; color:#0d9488; font-size:13px; font-variant-numeric:tabular-nums; }

.je-status-pill { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:20px; }
.je-status-dot  { width:6px; height:6px; border-radius:50%; }
.je-status-posted .je-status-dot { background:#16a34a; }
.je-status-draft  .je-status-dot { background:#f59e0b; }
.je-status-posted { background:#f0fdf4; color:#15803d; }
.je-status-draft  { background:#fffbeb; color:#b45309; }

.je-del-btn { background:none; border:none; cursor:pointer; color:#fca5a5; font-size:15px; padding:4px 8px; border-radius:6px; transition:all .15s; }
.je-del-btn:hover { background:#fef2f2; color:#dc2626; }

/* ── Expanded row ── */
.je-expanded { }
.je-exp-strip {
    background: linear-gradient(90deg, #0f172a, #134e4a);
    color:#fff; padding:12px 22px;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
}
.je-exp-num   { font-family:monospace; font-size:13px; font-weight:800; background:rgba(255,255,255,.15); padding:4px 12px; border-radius:8px; display:flex; align-items:center; gap:6px; }
.je-exp-pills { display:flex; gap:8px; flex-wrap:wrap; }
.je-exp-pill  { font-size:11.5px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); border-radius:6px; padding:2px 10px; display:flex; align-items:center; gap:4px; }
.je-pill-type   { background:rgba(14,165,233,.2); border-color:rgba(14,165,233,.3); }
.je-pill-status { background:rgba(16,185,129,.2); border-color:rgba(16,185,129,.3); font-weight:700; }
.je-exp-desc  { margin-left:auto; font-style:italic; font-size:12px; opacity:.7; }

.je-exp-body  { display:flex; }
.je-exp-pane  { flex:1; padding:18px 22px; }
.je-pane-products { border-left:1px solid #f1f5f9; flex:0 0 420px; }
.je-pane-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#475569; margin-bottom:12px; display:flex; align-items:center; gap:6px; }
.je-pane-title-green { color:#0f766e; }

/* Ledger table */
.je-ledger-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.je-ledger-tbl thead tr { background:#f0fdfa; }
.je-ledger-tbl th { padding:8px 10px; font-size:10.5px; font-weight:700; color:#0f766e; text-transform:uppercase; letter-spacing:.3px; border-bottom:2px solid #99f6e4; text-align:left; }
.je-ledger-tbl td { padding:9px 10px; border-bottom:1px solid #f1f5f9; }
.je-dr-row { background:#eff6ff; }
.je-cr-row { background:#f0fdf4; }
.je-dr-row:hover td { background:#dbeafe!important; }
.je-cr-row:hover td { background:#dcfce7!important; }
.je-dr-tag  { display:inline-block; background:#2563eb; color:#fff; font-size:9.5px; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:5px; }
.je-cr-tag  { display:inline-block; background:#16a34a; color:#fff; font-size:9.5px; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:5px; }
.je-acct-nm { font-weight:600; color:#1e293b; }
.je-type-chip { background:#f1f5f9; border-radius:4px; padding:1px 6px; font-size:11px; color:#64748b; }
.je-note    { color:#64748b; font-size:12px; }
.je-dr-amt  { font-weight:700; color:#2563eb; font-variant-numeric:tabular-nums; }
.je-cr-amt  { font-weight:700; color:#16a34a; font-variant-numeric:tabular-nums; }
.je-ledger-foot { background:#f8fafc; }
.je-foot-lbl { padding:10px; font-weight:700; color:#334155; font-size:13px; }
.je-foot-dr  { color:#2563eb; font-weight:800; font-size:13px; padding:10px; }
.je-foot-cr  { color:#16a34a; font-weight:800; font-size:13px; padding:10px; }

/* Product table */
.je-prod-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.je-prod-tbl thead tr { background:#f0fdf4; }
.je-prod-tbl th { padding:8px 10px; font-size:10.5px; font-weight:700; color:#15803d; text-transform:uppercase; letter-spacing:.3px; border-bottom:2px solid #bbf7d0; text-align:left; }
.je-prod-tbl td { padding:9px 10px; border-bottom:1px solid #f1f5f9; color:#334155; }
.je-prod-tbl tbody tr:hover td { background:#f8fafc; }
.je-prod-foot { background:#f0fdf4; }
.je-idx   { color:#cbd5e1; font-size:11px; width:26px; }
.je-pname { font-weight:600; color:#1e293b; }
.je-qty   { font-weight:700; color:#0ea5e9; }
.je-uprice { color:#64748b; }
.je-psub  { font-weight:700; color:#15803d; }

.je-code { background:#f1f5f9; border-radius:4px; padding:1px 6px; font-size:11px; color:#475569; font-family:monospace; }
.je-code-green { background:#dcfce7; color:#15803d; }

/* ══ MODALS ════════════════════════════════════════════════════ */
.je-modal-hd {
    display:flex; align-items:center; gap:10px;
    font-size:15px; font-weight:700; color:#0f172a;
}
.je-modal-hd-ob { }
.je-modal-ico {
    font-size:17px; background:#f0fdfa; color:#0d9488;
    border-radius:8px; width:32px; height:32px;
    display:flex; align-items:center; justify-content:center;
}
.je-modal-ico-ob { background:#f5f3ff; color:#7c3aed; }

.je-modal-body { }

.je-ob-info {
    display:flex; align-items:flex-start; gap:10px;
    background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px;
    padding:12px 16px; margin:16px 24px; font-size:13px; color:#1e40af; line-height:1.5;
}

.je-modal-meta {
    display:flex; gap:14px; padding:16px 24px; flex-wrap:wrap;
    background:#fafbfc; border-bottom:1px solid #f1f5f9;
}
.je-modal-field { display:flex; flex-direction:column; gap:5px; flex:1; min-width:160px; }
.je-modal-lbl   { font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; }
.je-req { color:#ef4444; }
.je-modal-input { border-radius:8px!important; }

.je-modal-lines { padding:20px 24px 0; }
.je-modal-lines-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.je-lines-title { font-size:13px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:7px; }

.je-balance-strip {
    display:flex; align-items:center; gap:16px; font-size:12.5px;
    padding:7px 16px; border-radius:10px; font-weight:600;
}
.je-balanced   { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.je-unbalanced { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.je-bal-status { font-weight:800; }

/* Entry lines table */
.je-entry-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.je-entry-tbl thead tr { background:#f8fafc; }
.je-entry-tbl th { padding:9px 10px; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.3px; border-bottom:2px solid #f1f5f9; text-align:left; }
.je-entry-row td { padding:7px 8px; border-bottom:1px solid #f8fafc; }
.je-entry-row:hover td { background:#f8fafc; }

/* OB lines */
.je-ob-lines { display:flex; flex-direction:column; gap:8px; margin-bottom:4px; }
.je-ob-line  { display:flex; align-items:center; gap:8px; }

/* Remove / footer */
.je-rm-btn {
    background:none; border:none; cursor:pointer; color:#fca5a5;
    font-size:15px; padding:4px; border-radius:5px; transition:all .15s;
    display:flex; align-items:center;
}
.je-rm-btn:hover:not(:disabled) { color:#dc2626; background:#fef2f2; }
.je-rm-btn:disabled { opacity:.35; cursor:not-allowed; }

.je-entry-footer {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 0 20px; margin-top:10px;
    border-top:1px solid #f1f5f9;
}
.je-add-line-btn {
    display:flex; align-items:center; gap:6px;
    background:none; border:1px dashed #cbd5e1; border-radius:8px;
    color:#64748b; font-size:13px; font-weight:600;
    padding:6px 14px; cursor:pointer; transition:all .15s;
}
.je-add-line-btn:hover { border-color:#0d9488; color:#0d9488; background:#f0fdfa; }

.je-modal-actions { display:flex; gap:8px; }
.je-cancel-btn {
    background:#f1f5f9; color:#64748b; border:none; border-radius:9px;
    font-size:13px; font-weight:600; padding:0 18px; height:36px; cursor:pointer; transition:all .15s;
}
.je-cancel-btn:hover { background:#e2e8f0; }
.je-save-btn {
    display:flex; align-items:center; gap:7px;
    background:linear-gradient(135deg,#0f766e,#0d9488); color:#fff;
    border:none; border-radius:9px;
    font-size:13px; font-weight:700; padding:0 22px; height:36px; cursor:pointer;
    box-shadow:0 4px 14px rgba(13,148,136,.35); transition:all .2s;
}
.je-save-btn:hover:not(:disabled) { opacity:.9; transform:translateY(-1px); }
.je-save-btn:disabled { opacity:.5; cursor:not-allowed; }
.je-save-ob { background:linear-gradient(135deg,#5b21b6,#7c3aed)!important; box-shadow:0 4px 14px rgba(124,58,237,.35)!important; }

.je-spinner {
    width:13px; height:13px; border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; display:inline-block;
}
.je-spinner-ob { border-color:rgba(255,255,255,.3); border-top-color:#fff; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>
