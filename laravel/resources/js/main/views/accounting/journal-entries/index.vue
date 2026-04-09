<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Journal Entries" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="/" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Journal Entries</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- ── Filters bar (matches Sales page layout) ───────────────────── -->
    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <!-- Left: action buttons -->
            <a-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">
                <a-space>
                    <a-button type="primary" @click="openAddModal">
                        <template #icon><PlusOutlined /></template>
                        New Entry
                    </a-button>
                    <a-button @click="openObModal" style="color:#7c3aed;border-color:#ddd6fe;">
                        <template #icon><BankOutlined /></template>
                        Opening Balance
                    </a-button>
                </a-space>
            </a-col>

            <!-- Right: search + date range -->
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col :xs="24" :sm="12" :md="10" :lg="9" :xl="8">
                        <a-input-search
                            v-model:value="searchString"
                            placeholder="Search entry #, description…"
                            allow-clear
                            style="width:100%"
                            @search="loadEntries"
                            @change="e => { if (!e.target.value) loadEntries(); }"
                        />
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="10" :lg="9" :xl="8">
                        <a-range-picker v-model:value="dateRange" style="width:100%" @change="loadEntries" />
                    </a-col>
                </a-row>
            </a-col>
        </a-row>

        <!-- Health KPI chips -->
        <div v-if="health" class="je-kpi-row">
            <div class="je-kpi">
                <div class="je-kpi-val">{{ health.total_entries }}</div>
                <div class="je-kpi-lbl">Total Entries</div>
            </div>
            <div class="je-kpi">
                <div class="je-kpi-val je-kpi-ok">{{ health.balanced }}</div>
                <div class="je-kpi-lbl">Balanced</div>
            </div>
            <div class="je-kpi" :class="health.imbalanced?.length ? 'je-kpi-danger' : ''">
                <div class="je-kpi-val" :class="health.imbalanced?.length ? 'je-kpi-red' : 'je-kpi-ok'">
                    {{ health.imbalanced?.length ?? 0 }}
                </div>
                <div class="je-kpi-lbl">Imbalanced</div>
            </div>
            <div class="je-kpi">
                <div class="je-kpi-val" :class="health.je_coverage_pct < 100 ? 'je-kpi-warn' : 'je-kpi-ok'">{{ health.je_coverage_pct }}%</div>
                <div class="je-kpi-lbl">Coverage</div>
            </div>
            <div class="je-kpi">
                <div class="je-kpi-val" :class="health.cogs_entries === 0 ? 'je-kpi-red' : 'je-kpi-ok'">{{ health.cogs_entries }}</div>
                <div class="je-kpi-lbl">COGS Entries</div>
            </div>
        </div>

        <!-- Imbalance alert -->
        <div v-if="health?.imbalanced?.length" class="je-imbalance-bar">
            <ExclamationCircleOutlined class="je-imb-icon" />
            <span><b>{{ health.imbalanced.length }} imbalanced</b> entries detected: {{ health.imbalanced.map(e => e.entry_number).join(', ') }}</span>
        </div>
    </admin-page-filters>

    <!-- ── Table (matches Sales page layout) ─────────────────────────── -->
    <admin-page-table-content>
        <!-- Status tabs -->
        <a-row>
            <a-col :span="24">
                <a-tabs v-model:activeKey="statusFilter" @change="() => { pagination.current = 1; loadEntries(); }">
                    <a-tab-pane key="all" tab="All Entries" />
                    <a-tab-pane key="posted" tab="Posted" />
                    <a-tab-pane key="draft" tab="Draft" />
                </a-tabs>
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
                :bordered="true"
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
    </admin-page-table-content>

    <!-- ── New Journal Entry Modal ──────────────────────────────── -->
    <a-modal v-model:open="addModalVisible" :footer="null" width="900px" class="je-modal" :bodyStyle="{ padding:0 }">
        <template #title>
            <div class="je-modal-hd">
                <span class="je-modal-ico"><FileTextOutlined /></span>
                New Journal Entry
            </div>
        </template>

        <div class="je-modal-body">
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
    CalendarOutlined, LinkOutlined, CheckCircleOutlined,
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
        CalendarOutlined, LinkOutlined, CheckCircleOutlined,
        BankOutlined, ExclamationCircleOutlined, InfoCircleOutlined,
    },
    setup() {
        const axiosAdmin    = window.axiosAdmin;
        const loading       = ref(false);
        const saving        = ref(false);
        const entries       = ref([]);
        const allAccounts   = ref([]);
        const dateRange     = ref(null);
        const searchString  = ref('');
        const statusFilter  = ref('all');
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
            { title: 'Entry #',     key: 'entry_number', width: 200 },
            { title: 'Date',        key: 'entry_date',   width: 115 },
            { title: 'Description', key: 'description' },
            { title: 'Reference',   key: 'reference',    width: 130 },
            { title: 'Total (PKR)', key: 'total_debit',  width: 145, align: 'right' },
            { title: 'Status',      key: 'status',       width: 100 },
            { title: '',            key: 'action',       width: 56,  fixed: 'right' },
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
                if (statusFilter.value && statusFilter.value !== 'all') params.status = statusFilter.value;
                if (searchString.value?.trim()) params.search = searchString.value.trim();
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
            loading, saving, entries, allAccounts, dateRange, searchString, statusFilter, pagination,
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
/*
 * Design tokens — mirror the app's Inter-based theme
 * dark text : #272B41   secondary : #5A5F7D   border : #E3E6EF
 * bg-light  : #F8F9FB   success   : #20C997   warning : #FA8B0C
 */
.r { text-align: right; }

/* ══ KPI CHIPS ═════════════════════════════════════════════════ */
.je-kpi-row {
    display: flex; gap: 10px; flex-wrap: wrap;
    margin-top: 14px; padding-top: 14px;
    border-top: 1px solid #E3E6EF;
    font-family: 'Inter', sans-serif;
}
.je-kpi {
    padding: 8px 16px; border-radius: 8px; text-align: center;
    min-width: 88px; background: #F8F9FB; border: 1px solid #E3E6EF;
}
.je-kpi-danger { background: #fff1f0 !important; border-color: #ffccc7 !important; animation: je-pulse 1.6s infinite; }
.je-kpi-val  { font-size: 16px; font-weight: 700; color: #272B41; line-height: 1.2; font-family: 'Inter', sans-serif; }
.je-kpi-lbl  { font-size: 10px; font-weight: 600; color: #5A5F7D; text-transform: uppercase; letter-spacing: .5px; margin-top: 3px; font-family: 'Inter', sans-serif; }
.je-kpi-ok   { color: #20C997 !important; }
.je-kpi-red  { color: #FF4D4F !important; }
.je-kpi-warn { color: #FA8B0C !important; }
@keyframes je-pulse { 0%,100%{opacity:1} 50%{opacity:.6} }

/* Imbalance bar */
.je-imbalance-bar {
    background: #fff1f0; border: 1px solid #ffccc7; border-radius: 8px;
    padding: 10px 16px; color: #cf1322; font-size: 13px; margin-top: 12px;
    display: flex; align-items: center; gap: 10px;
    font-family: 'Inter', sans-serif;
}
.je-imb-icon { color: #FF4D4F; font-size: 15px; flex-shrink: 0; }

/* ══ TABLE CELLS ════════════════════════════════════════════════ */
:deep(.je-main-row:hover > td) { background: #F8F9FB !important; cursor: pointer; }
:deep(.ant-table-expanded-row > td) { background: #F8F9FB !important; padding: 0 !important; }

.je-entry-num { display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; color: #1677ff; }
.je-en-ico    { font-size: 11px; opacity: .7; }
.je-date-cell { font-size: 13px; color: #5A5F7D; font-family: 'Inter', sans-serif; }
.je-desc      { font-weight: 500; color: #272B41; font-size: 13px; font-family: 'Inter', sans-serif; }
.je-nil       { color: #ADB4D2; }
.je-chip-row  { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
.je-chip      { padding: 1px 8px; border-radius: 20px; background: #e6f4ff; border: 1px solid #91caff; color: #1677ff; font-size: 11px; font-weight: 600; font-family: 'Inter', sans-serif; }
.je-chip-more { padding: 1px 8px; border-radius: 20px; background: #F8F9FB; border: 1px solid #E3E6EF; color: #5A5F7D; font-size: 11px; font-family: 'Inter', sans-serif; }
.je-ref-pill  { display: inline-flex; align-items: center; gap: 4px; background: #F4F5F7; border-radius: 4px; font-size: 12px; color: #5A5F7D; font-family: 'Inter', sans-serif; padding: 2px 8px; }
.je-amount    { font-weight: 700; color: #272B41; font-size: 13px; font-variant-numeric: tabular-nums; font-family: 'Inter', sans-serif; }

.je-status-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; font-family: 'Inter', sans-serif; text-transform: capitalize; }
.je-status-dot  { width: 6px; height: 6px; border-radius: 50%; }
.je-status-posted .je-status-dot { background: #20C997; }
.je-status-draft  .je-status-dot { background: #FA8B0C; }
.je-status-posted { background: #d6fff4; color: #0CAB7C; }
.je-status-draft  { background: #fff7e6; color: #D47407; }

.je-del-btn { background: none; border: none; cursor: pointer; color: #ADB4D2; font-size: 15px; padding: 4px 8px; border-radius: 4px; transition: all .15s; }
.je-del-btn:hover { background: #fff1f0; color: #FF4D4F; }

/* ── Expanded row ── */
.je-exp-strip {
    background: #fff;
    border-bottom: 1px solid #E3E6EF;
    padding: 12px 22px;
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    font-family: 'Inter', sans-serif;
}
.je-exp-num   { font-size: 13px; font-weight: 700; background: #e6f4ff; color: #1677ff; padding: 4px 12px; border-radius: 6px; display: flex; align-items: center; gap: 6px; }
.je-exp-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.je-exp-pill  { font-size: 11px; background: #F4F5F7; border: 1px solid #E3E6EF; border-radius: 4px; padding: 2px 10px; display: flex; align-items: center; gap: 4px; color: #5A5F7D; }
.je-pill-type   { background: #e6f4ff; border-color: #91caff; color: #1677ff; }
.je-pill-status { background: #d6fff4; border-color: #87e8ca; color: #0CAB7C; font-weight: 700; }
.je-exp-desc  { margin-left: auto; font-style: italic; font-size: 12px; color: #ADB4D2; }

.je-exp-body  { display: flex; }
.je-exp-pane  { flex: 1; padding: 18px 22px; }
.je-pane-products { border-left: 1px solid #E3E6EF; flex: 0 0 420px; }
.je-pane-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #5A5F7D; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif; }
.je-pane-title-green { color: #0CAB7C; }

/* Ledger table */
.je-ledger-tbl { width: 100%; border-collapse: collapse; font-size: 13px; font-family: 'Inter', sans-serif; }
.je-ledger-tbl thead tr { background: #F8F9FB; }
.je-ledger-tbl th { padding: 8px 10px; font-size: 11px; font-weight: 700; color: #5A5F7D; text-transform: uppercase; letter-spacing: .3px; border-bottom: 2px solid #E3E6EF; text-align: left; }
.je-ledger-tbl td { padding: 9px 10px; border-bottom: 1px solid #F1F2F6; }
.je-dr-row { background: #e6f4ff; }
.je-cr-row { background: #d6fff4; }
.je-dr-row:hover td { background: #bae0ff !important; }
.je-cr-row:hover td { background: #b7ffe8 !important; }
.je-dr-tag  { display: inline-block; background: #1677ff; color: #fff; font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 3px; margin-right: 5px; font-family: 'Inter', sans-serif; }
.je-cr-tag  { display: inline-block; background: #20C997; color: #fff; font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 3px; margin-right: 5px; font-family: 'Inter', sans-serif; }
.je-acct-nm { font-weight: 600; color: #272B41; font-family: 'Inter', sans-serif; }
.je-type-chip { background: #F4F5F7; border-radius: 4px; padding: 1px 6px; font-size: 11px; color: #5A5F7D; font-family: 'Inter', sans-serif; }
.je-note    { color: #5A5F7D; font-size: 12px; font-family: 'Inter', sans-serif; }
.je-dr-amt  { font-weight: 700; color: #1677ff; font-variant-numeric: tabular-nums; }
.je-cr-amt  { font-weight: 700; color: #0CAB7C; font-variant-numeric: tabular-nums; }
.je-ledger-foot { background: #F4F5F7; }
.je-foot-lbl { padding: 10px; font-weight: 700; color: #272B41; font-size: 13px; font-family: 'Inter', sans-serif; }
.je-foot-dr  { color: #1677ff; font-weight: 700; font-size: 13px; padding: 10px; }
.je-foot-cr  { color: #0CAB7C; font-weight: 700; font-size: 13px; padding: 10px; }

/* Product table */
.je-prod-tbl { width: 100%; border-collapse: collapse; font-size: 13px; font-family: 'Inter', sans-serif; }
.je-prod-tbl thead tr { background: #F8F9FB; }
.je-prod-tbl th { padding: 8px 10px; font-size: 11px; font-weight: 700; color: #5A5F7D; text-transform: uppercase; letter-spacing: .3px; border-bottom: 2px solid #E3E6EF; text-align: left; }
.je-prod-tbl td { padding: 9px 10px; border-bottom: 1px solid #F1F2F6; color: #272B41; font-family: 'Inter', sans-serif; }
.je-prod-tbl tbody tr:hover td { background: #F8F9FB; }
.je-prod-foot { background: #F4F5F7; }
.je-idx   { color: #ADB4D2; font-size: 11px; width: 26px; }
.je-pname { font-weight: 600; color: #272B41; }
.je-qty   { font-weight: 700; color: #1677ff; }
.je-uprice { color: #5A5F7D; }
.je-psub  { font-weight: 700; color: #0CAB7C; }

.je-code       { background: #F4F5F7; border-radius: 4px; padding: 1px 6px; font-size: 11px; color: #5A5F7D; font-family: 'Inter', sans-serif; }
.je-code-green { background: #d6fff4; color: #0CAB7C; }

/* ══ MODALS ════════════════════════════════════════════════════ */
.je-modal-hd {
    display: flex; align-items: center; gap: 10px;
    font-size: 15px; font-weight: 700; color: #272B41;
    font-family: 'Inter', sans-serif;
}
.je-modal-ico {
    font-size: 16px; background: #e6f4ff; color: #1677ff;
    border-radius: 6px; width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
}
.je-modal-ico-ob { background: #f5f0ff; color: #722ed1; }

.je-ob-info {
    display: flex; align-items: flex-start; gap: 10px;
    background: #e6f4ff; border: 1px solid #91caff; border-radius: 8px;
    padding: 12px 16px; margin: 16px 24px; font-size: 13px; color: #0958d9; line-height: 1.5;
    font-family: 'Inter', sans-serif;
}

.je-modal-meta {
    display: flex; gap: 14px; padding: 16px 24px; flex-wrap: wrap;
    background: #F8F9FB; border-bottom: 1px solid #E3E6EF;
}
.je-modal-field { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 160px; }
.je-modal-lbl   { font-size: 11px; font-weight: 700; color: #5A5F7D; text-transform: uppercase; letter-spacing: .4px; font-family: 'Inter', sans-serif; }
.je-req { color: #FF4D4F; }
.je-modal-input { border-radius: 6px !important; }

.je-modal-lines { padding: 20px 24px 0; }
.je-modal-lines-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.je-lines-title { font-size: 13px; font-weight: 700; color: #272B41; display: flex; align-items: center; gap: 7px; font-family: 'Inter', sans-serif; }

.je-balance-strip {
    display: flex; align-items: center; gap: 16px; font-size: 12px;
    padding: 6px 14px; border-radius: 6px; font-weight: 600;
    font-family: 'Inter', sans-serif;
}
.je-balanced   { background: #d6fff4; color: #0CAB7C; border: 1px solid #87e8ca; }
.je-unbalanced { background: #fff7e6; color: #D47407; border: 1px solid #ffd591; }
.je-bal-status { font-weight: 700; }

/* Entry lines table */
.je-entry-tbl { width: 100%; border-collapse: collapse; font-size: 13px; font-family: 'Inter', sans-serif; }
.je-entry-tbl thead tr { background: #F8F9FB; }
.je-entry-tbl th { padding: 9px 10px; font-size: 11px; font-weight: 700; color: #5A5F7D; text-transform: uppercase; letter-spacing: .3px; border-bottom: 2px solid #E3E6EF; text-align: left; }
.je-entry-row td { padding: 7px 8px; border-bottom: 1px solid #F1F2F6; }
.je-entry-row:hover td { background: #F8F9FB; }

/* OB lines */
.je-ob-lines { display: flex; flex-direction: column; gap: 8px; margin-bottom: 4px; }
.je-ob-line  { display: flex; align-items: center; gap: 8px; }

/* Remove / footer */
.je-rm-btn {
    background: none; border: none; cursor: pointer; color: #ADB4D2;
    font-size: 15px; padding: 4px; border-radius: 4px; transition: all .15s;
    display: flex; align-items: center;
}
.je-rm-btn:hover:not(:disabled) { color: #FF4D4F; background: #fff1f0; }
.je-rm-btn:disabled { opacity: .35; cursor: not-allowed; }

.je-entry-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 0 20px; margin-top: 10px;
    border-top: 1px solid #E3E6EF;
}
.je-add-line-btn {
    display: flex; align-items: center; gap: 6px;
    background: none; border: 1px dashed #E3E6EF; border-radius: 6px;
    color: #5A5F7D; font-size: 13px; font-weight: 600;
    padding: 6px 14px; cursor: pointer; transition: all .15s;
    font-family: 'Inter', sans-serif;
}
.je-add-line-btn:hover { border-color: #1677ff; color: #1677ff; background: #e6f4ff; }

.je-modal-actions { display: flex; gap: 8px; }
.je-cancel-btn {
    background: #F4F5F7; color: #5A5F7D; border: none; border-radius: 6px;
    font-size: 13px; font-weight: 600; padding: 0 18px; height: 36px; cursor: pointer; transition: all .15s;
    font-family: 'Inter', sans-serif;
}
.je-cancel-btn:hover { background: #E3E6EF; color: #272B41; }
.je-save-btn {
    display: flex; align-items: center; gap: 7px;
    background: #1677ff; color: #fff;
    border: none; border-radius: 6px;
    font-size: 13px; font-weight: 600; padding: 0 22px; height: 36px; cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: all .2s;
}
.je-save-btn:hover:not(:disabled) { background: #0958d9; }
.je-save-btn:disabled { opacity: .5; cursor: not-allowed; }
.je-save-ob { background: #722ed1 !important; }
.je-save-ob:hover:not(:disabled) { background: #531dab !important; }

.je-spinner {
    width: 13px; height: 13px; border: 2px solid rgba(255,255,255,.4);
    border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
