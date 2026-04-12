<template>
    <a-row>
        <a-col :span="24">
            <div class="page-header-container">
                <a-page-header :title="$t('menu.banking')" style="padding: 0 0 16px 0" />
            </div>

            <!-- Summary Cards -->
            <a-row :gutter="[16, 16]" style="margin-bottom: 24px">
                <a-col :xs="24" :sm="12" :md="6">
                    <a-card class="summary-card in-hand-card" :bordered="false">
                        <div class="card-inner">
                            <div class="card-icon"><FileTextOutlined /></div>
                            <div class="card-text">
                                <div class="card-value">{{ summary.in_hand }}</div>
                                <div class="card-label">Cheques in Hand</div>
                            </div>
                        </div>
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <a-card class="summary-card pending-card" :bordered="false">
                        <div class="card-inner">
                            <div class="card-icon"><ClockCircleOutlined /></div>
                            <div class="card-text">
                                <div class="card-value">{{ summary.pending }}</div>
                                <div class="card-label">Pending Cheques</div>
                            </div>
                        </div>
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <a-card class="summary-card cleared-card" :bordered="false">
                        <div class="card-inner">
                            <div class="card-icon"><CheckCircleOutlined /></div>
                            <div class="card-text">
                                <div class="card-value">{{ formatAmt(summary.cleared_amount) }}</div>
                                <div class="card-label">Cleared Amount</div>
                            </div>
                        </div>
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <a-card class="summary-card bounced-card" :bordered="false">
                        <div class="card-inner">
                            <div class="card-icon"><WarningOutlined /></div>
                            <div class="card-text">
                                <div class="card-value">{{ formatAmt(summary.bounced_amount) }}</div>
                                <div class="card-label">Bounced Amount</div>
                            </div>
                        </div>
                    </a-card>
                </a-col>
            </a-row>

            <!-- Tabs -->
            <a-card :bordered="false" class="banking-tabs-card">
                <a-tabs v-model:activeKey="activeTab" @change="onTabChange">
                    <a-tab-pane key="cheques" tab="Cheques">
                        <!-- Cheques Tab Toolbar -->
                        <a-row :gutter="[12, 12]" style="margin-bottom: 16px" align="middle">
                            <a-col :xs="24" :sm="8" :md="5">
                                <a-select v-model:value="filters.type" allowClear placeholder="All Types" style="width: 100%" @change="loadCheques">
                                    <a-select-option value="received">Received</a-select-option>
                                    <a-select-option value="issued">Issued</a-select-option>
                                </a-select>
                            </a-col>
                            <a-col :xs="24" :sm="8" :md="5">
                                <a-select v-model:value="filters.status" allowClear placeholder="All Statuses" style="width: 100%" @change="loadCheques">
                                    <a-select-option value="in_hand">In Hand</a-select-option>
                                    <a-select-option value="pending">Pending</a-select-option>
                                    <a-select-option value="deposited">Deposited</a-select-option>
                                    <a-select-option value="cleared">Cleared</a-select-option>
                                    <a-select-option value="bounced">Bounced</a-select-option>
                                    <a-select-option value="returned">Returned</a-select-option>
                                </a-select>
                            </a-col>
                            <a-col :xs="24" :sm="8" :md="6">
                                <a-input-search v-model:value="filters.search" placeholder="Cheque no / party name" allow-clear @search="loadCheques" @pressEnter="loadCheques" />
                            </a-col>
                            <a-col :xs="24" :sm="24" :md="8" style="text-align: right">
                                <a-button type="primary" @click="openAddCheque">
                                    <template #icon><PlusOutlined /></template>
                                    New Cheque
                                </a-button>
                            </a-col>
                        </a-row>

                        <a-table
                            :dataSource="cheques"
                            :columns="chequeColumns"
                            :loading="loadingCheques"
                            :pagination="{ pageSize: 15, showSizeChanger: true }"
                            rowKey="xid"
                            size="middle"
                            :scroll="{ x: 900 }"
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.key === 'type'">
                                    <a-tag :color="record.type === 'received' ? 'blue' : 'purple'">
                                        {{ record.type === 'received' ? 'Received' : 'Issued' }}
                                    </a-tag>
                                </template>
                                <template v-if="column.key === 'party'">
                                    <span>{{ record.party?.name || '—' }}</span>
                                    <a-tag v-if="record.party_type" :color="record.party_type === 'customer' ? 'cyan' : 'orange'" style="margin-left: 6px; font-size: 10px">
                                        {{ record.party_type }}
                                    </a-tag>
                                </template>
                                <template v-if="column.key === 'amount'">
                                    <strong>{{ formatAmt(record.amount) }}</strong>
                                </template>
                                <template v-if="column.key === 'status'">
                                    <a-tag :color="statusColor(record.status)">{{ statusLabel(record.status) }}</a-tag>
                                </template>
                                <template v-if="column.key === 'actions'">
                                    <a-space>
                                        <a-button v-if="record.status === 'in_hand'" size="small" type="primary" @click="openDeposit(record)">
                                            Deposit
                                        </a-button>
                                        <a-button v-if="['in_hand','pending','deposited'].includes(record.status)" size="small" style="background:#52c41a;color:#fff;border-color:#52c41a" @click="openClear(record)">
                                            Clear
                                        </a-button>
                                        <a-button v-if="!['bounced','returned','cleared'].includes(record.status)" size="small" danger @click="openBounce(record)">
                                            Bounce
                                        </a-button>
                                        <a-popconfirm title="Delete this cheque?" ok-text="Yes" cancel-text="No" @confirm="deleteCheque(record)">
                                            <a-button size="small" danger type="text">
                                                <template #icon><DeleteOutlined /></template>
                                            </a-button>
                                        </a-popconfirm>
                                    </a-space>
                                </template>
                            </template>
                        </a-table>
                    </a-tab-pane>

                    <a-tab-pane key="deposits" tab="Deposits & Transfers">
                        <a-row :gutter="[12, 12]" style="margin-bottom: 16px" align="middle">
                            <a-col :xs="24" :sm="8" :md="5">
                                <a-select v-model:value="txnFilters.type" allowClear placeholder="All Types" style="width: 100%" @change="loadTransactions">
                                    <a-select-option value="cash_deposit">Cash Deposit</a-select-option>
                                    <a-select-option value="bank_transfer">Bank Transfer</a-select-option>
                                </a-select>
                            </a-col>
                            <a-col :xs="24" :sm="8" :md="5">
                                <a-date-picker v-model:value="txnFilters.date_from" placeholder="From Date" style="width:100%" value-format="YYYY-MM-DD" @change="loadTransactions" />
                            </a-col>
                            <a-col :xs="24" :sm="8" :md="5">
                                <a-date-picker v-model:value="txnFilters.date_to" placeholder="To Date" style="width:100%" value-format="YYYY-MM-DD" @change="loadTransactions" />
                            </a-col>
                            <a-col :xs="24" :sm="24" :md="9" style="text-align: right">
                                <a-space>
                                    <a-button type="default" @click="openCashDeposit">
                                        <template #icon><BankOutlined /></template>
                                        Cash Deposit
                                    </a-button>
                                    <a-button type="primary" @click="openBankTransfer">
                                        <template #icon><SwapOutlined /></template>
                                        Bank Transfer
                                    </a-button>
                                </a-space>
                            </a-col>
                        </a-row>

                        <!-- Deposit/Transfer Stats -->
                        <a-row :gutter="[16, 16]" style="margin-bottom: 16px">
                            <a-col :xs="24" :sm="12">
                                <a-statistic
                                    title="Total Cash Deposits"
                                    :value="txnStats.total_deposits"
                                    :precision="2"
                                    prefix="৳"
                                    :value-style="{ color: '#52c41a' }"
                                />
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-statistic
                                    title="Total Transfers"
                                    :value="txnStats.total_transfers"
                                    :precision="2"
                                    prefix="৳"
                                    :value-style="{ color: '#1890ff' }"
                                />
                            </a-col>
                        </a-row>

                        <a-table
                            :dataSource="transactions"
                            :columns="txnColumns"
                            :loading="loadingTxns"
                            :pagination="{ pageSize: 15 }"
                            rowKey="xid"
                            size="middle"
                            :scroll="{ x: 700 }"
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.key === 'type'">
                                    <a-tag :color="record.type === 'cash_deposit' ? 'green' : 'blue'">
                                        {{ record.type === 'cash_deposit' ? 'Cash Deposit' : 'Bank Transfer' }}
                                    </a-tag>
                                </template>
                                <template v-if="column.key === 'from'">
                                    {{ record.from_account?.account_name || '—' }}
                                </template>
                                <template v-if="column.key === 'to'">
                                    {{ record.to_account?.account_name || '—' }}
                                </template>
                                <template v-if="column.key === 'amount'">
                                    <strong>{{ formatAmt(record.amount) }}</strong>
                                </template>
                                <template v-if="column.key === 'actions'">
                                    <a-popconfirm title="Delete this transaction?" ok-text="Yes" cancel-text="No" @confirm="deleteTxn(record)">
                                        <a-button size="small" danger type="text">
                                            <template #icon><DeleteOutlined /></template>
                                        </a-button>
                                    </a-popconfirm>
                                </template>
                            </template>
                        </a-table>
                    </a-tab-pane>
                </a-tabs>
            </a-card>
        </a-col>
    </a-row>

    <!-- ===== Add Cheque Modal ===== -->
    <a-modal v-model:open="chequeModal.visible" :title="'Record New Cheque'" width="560px" :footer="null" destroyOnClose>
        <a-form :model="chequeForm" layout="vertical" @finish="submitCheque">
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Cheque Type" name="type" :rules="[{required:true,message:'Required'}]">
                        <a-radio-group v-model:value="chequeForm.type" button-style="solid" style="width:100%">
                            <a-radio-button value="received" style="width:50%;text-align:center">Received</a-radio-button>
                            <a-radio-button value="issued" style="width:50%;text-align:center">Issued</a-radio-button>
                        </a-radio-group>
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Party Type" name="party_type" :rules="[{required:true,message:'Required'}]">
                        <a-radio-group v-model:value="chequeForm.party_type" button-style="solid" style="width:100%" @change="chequeForm.party_id = null; loadParties()">
                            <a-radio-button value="customer" style="width:50%;text-align:center">Customer</a-radio-button>
                            <a-radio-button value="supplier" style="width:50%;text-align:center">Supplier</a-radio-button>
                        </a-radio-group>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Party" name="party_id" :rules="[{required:true,message:'Required'}]">
                        <a-select
                            v-model:value="chequeForm.party_id"
                            show-search
                            :filter-option="filterParty"
                            placeholder="Select party"
                            style="width:100%"
                            :loading="loadingParties"
                        >
                            <a-select-option v-for="p in parties" :key="p.xid" :value="p.xid">{{ p.name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Cheque No" name="cheque_no" :rules="[{required:true,message:'Required'}]">
                        <a-input v-model:value="chequeForm.cheque_no" placeholder="e.g. 0012345" />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Amount" name="amount" :rules="[{required:true,message:'Required'}]">
                        <a-input-number v-model:value="chequeForm.amount" :min="0.01" :precision="2" style="width:100%" placeholder="0.00" />
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Cheque Date" name="cheque_date" :rules="[{required:true,message:'Required'}]">
                        <a-date-picker v-model:value="chequeForm.cheque_date" style="width:100%" value-format="YYYY-MM-DD" />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Bank Name">
                        <a-input v-model:value="chequeForm.bank_name" placeholder="e.g. Sonali Bank" />
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Linked Bank Account">
                        <a-select v-model:value="chequeForm.bank_account_id" allowClear placeholder="Optional" style="width:100%">
                            <a-select-option v-for="b in bankAccounts" :key="b.xid" :value="b.xid">{{ b.account_name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-form-item label="Remarks">
                <a-textarea v-model:value="chequeForm.remarks" :rows="2" placeholder="Optional note" />
            </a-form-item>
            <div style="text-align:right">
                <a-space>
                    <a-button @click="chequeModal.visible = false">Cancel</a-button>
                    <a-button type="primary" html-type="submit" :loading="chequeModal.saving">Save Cheque</a-button>
                </a-space>
            </div>
        </a-form>
    </a-modal>

    <!-- ===== Deposit to Bank Modal ===== -->
    <a-modal v-model:open="depositModal.visible" title="Deposit Cheque to Bank" width="420px" :footer="null" destroyOnClose>
        <a-form layout="vertical" @finish="submitDeposit">
            <a-form-item label="Destination Bank Account" :rules="[{required:true}]">
                <a-select v-model:value="depositForm.bank_account_id" placeholder="Select bank account" style="width:100%">
                    <a-select-option v-for="b in bankAccounts" :key="b.xid" :value="b.xid">{{ b.account_name }}</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Deposit Date" :rules="[{required:true}]">
                <a-date-picker v-model:value="depositForm.action_date" style="width:100%" value-format="YYYY-MM-DD" />
            </a-form-item>
            <a-form-item label="Remarks">
                <a-input v-model:value="depositForm.remarks" placeholder="Optional" />
            </a-form-item>
            <div style="text-align:right">
                <a-space>
                    <a-button @click="depositModal.visible = false">Cancel</a-button>
                    <a-button type="primary" html-type="submit" :loading="depositModal.saving">Deposit</a-button>
                </a-space>
            </div>
        </a-form>
    </a-modal>

    <!-- ===== Clear Cheque Modal ===== -->
    <a-modal v-model:open="clearModal.visible" title="Clear Cheque" width="380px" :footer="null" destroyOnClose>
        <a-form layout="vertical" @finish="submitClear">
            <a-form-item label="Clearance Date" :rules="[{required:true}]">
                <a-date-picker v-model:value="clearForm.action_date" style="width:100%" value-format="YYYY-MM-DD" />
            </a-form-item>
            <div style="text-align:right">
                <a-space>
                    <a-button @click="clearModal.visible = false">Cancel</a-button>
                    <a-button type="primary" html-type="submit" :loading="clearModal.saving" style="background:#52c41a;border-color:#52c41a">Confirm Clear</a-button>
                </a-space>
            </div>
        </a-form>
    </a-modal>

    <!-- ===== Bounce Cheque Modal ===== -->
    <a-modal v-model:open="bounceModal.visible" title="Mark Cheque as Bounced" width="380px" :footer="null" destroyOnClose>
        <a-form layout="vertical" @finish="submitBounce">
            <a-form-item label="Reason / Note">
                <a-textarea v-model:value="bounceForm.remarks" :rows="3" placeholder="Bounce reason..." />
            </a-form-item>
            <div style="text-align:right">
                <a-space>
                    <a-button @click="bounceModal.visible = false">Cancel</a-button>
                    <a-button danger html-type="submit" :loading="bounceModal.saving">Mark Bounced</a-button>
                </a-space>
            </div>
        </a-form>
    </a-modal>

    <!-- ===== Cash Deposit Modal ===== -->
    <a-modal v-model:open="cashDepositModal.visible" title="Cash Deposit to Bank" width="460px" :footer="null" destroyOnClose>
        <a-form layout="vertical" @finish="submitCashDeposit">
            <a-form-item label="Destination Bank Account" :rules="[{required:true}]">
                <a-select v-model:value="cashDepositForm.to_account_id" placeholder="Select bank" style="width:100%">
                    <a-select-option v-for="b in bankAccounts" :key="b.xid" :value="b.xid">{{ b.account_name }}</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Amount" :rules="[{required:true}]">
                <a-input-number v-model:value="cashDepositForm.amount" :min="0.01" :precision="2" style="width:100%" placeholder="0.00" />
            </a-form-item>
            <a-form-item label="Transaction Date" :rules="[{required:true}]">
                <a-date-picker v-model:value="cashDepositForm.transaction_date" style="width:100%" value-format="YYYY-MM-DD" />
            </a-form-item>
            <a-form-item label="Reference">
                <a-input v-model:value="cashDepositForm.reference" placeholder="Receipt no, voucher no, etc." />
            </a-form-item>
            <a-form-item label="Description">
                <a-input v-model:value="cashDepositForm.description" placeholder="Optional" />
            </a-form-item>
            <div style="text-align:right">
                <a-space>
                    <a-button @click="cashDepositModal.visible = false">Cancel</a-button>
                    <a-button type="primary" html-type="submit" :loading="cashDepositModal.saving">Deposit Cash</a-button>
                </a-space>
            </div>
        </a-form>
    </a-modal>

    <!-- ===== Bank Transfer Modal ===== -->
    <a-modal v-model:open="bankTransferModal.visible" title="Bank to Bank Transfer" width="460px" :footer="null" destroyOnClose>
        <a-form layout="vertical" @finish="submitBankTransfer">
            <a-form-item label="From Bank Account" :rules="[{required:true}]">
                <a-select v-model:value="bankTransferForm.from_account_id" placeholder="Source bank" style="width:100%">
                    <a-select-option v-for="b in bankAccounts" :key="b.xid" :value="b.xid">{{ b.account_name }}</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="To Bank Account" :rules="[{required:true}]">
                <a-select v-model:value="bankTransferForm.to_account_id" placeholder="Destination bank" style="width:100%">
                    <a-select-option v-for="b in bankAccounts" :key="b.xid" :value="b.xid">{{ b.account_name }}</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Amount" :rules="[{required:true}]">
                <a-input-number v-model:value="bankTransferForm.amount" :min="0.01" :precision="2" style="width:100%" placeholder="0.00" />
            </a-form-item>
            <a-form-item label="Transfer Date" :rules="[{required:true}]">
                <a-date-picker v-model:value="bankTransferForm.transaction_date" style="width:100%" value-format="YYYY-MM-DD" />
            </a-form-item>
            <a-form-item label="Reference">
                <a-input v-model:value="bankTransferForm.reference" placeholder="Optional" />
            </a-form-item>
            <a-form-item label="Description">
                <a-input v-model:value="bankTransferForm.description" placeholder="Optional" />
            </a-form-item>
            <div style="text-align:right">
                <a-space>
                    <a-button @click="bankTransferModal.visible = false">Cancel</a-button>
                    <a-button type="primary" html-type="submit" :loading="bankTransferModal.saving">Transfer</a-button>
                </a-space>
            </div>
        </a-form>
    </a-modal>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import {
    FileTextOutlined, ClockCircleOutlined, CheckCircleOutlined, WarningOutlined,
    PlusOutlined, DeleteOutlined, BankOutlined, SwapOutlined,
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

const axiosAdmin = window.axiosAdmin;

const activeTab    = ref('cheques');
const cheques      = ref([]);
const transactions = ref([]);
const bankAccounts = ref([]);
const parties      = ref([]);
const loadingCheques   = ref(false);
const loadingTxns      = ref(false);
const loadingParties   = ref(false);

const summary  = reactive({ in_hand: 0, pending: 0, cleared_amount: 0, bounced_amount: 0 });
const txnStats = reactive({ total_deposits: 0, total_transfers: 0 });

const filters    = reactive({ type: null, status: null, search: '' });
const txnFilters = reactive({ type: null, date_from: null, date_to: null });

// Cheque form
const chequeModal = reactive({ visible: false, saving: false });
const chequeForm  = reactive({ type: 'received', party_type: 'customer', party_id: null, cheque_no: '', amount: null, cheque_date: dayjs().format('YYYY-MM-DD'), bank_name: '', bank_account_id: null, remarks: '' });

// Action modals
const depositModal = reactive({ visible: false, saving: false, record: null });
const depositForm  = reactive({ bank_account_id: null, action_date: dayjs().format('YYYY-MM-DD'), remarks: '' });

const clearModal = reactive({ visible: false, saving: false, record: null });
const clearForm  = reactive({ action_date: dayjs().format('YYYY-MM-DD') });

const bounceModal = reactive({ visible: false, saving: false, record: null });
const bounceForm  = reactive({ remarks: '' });

const cashDepositModal = reactive({ visible: false, saving: false });
const cashDepositForm  = reactive({ to_account_id: null, amount: null, transaction_date: dayjs().format('YYYY-MM-DD'), reference: '', description: '' });

const bankTransferModal = reactive({ visible: false, saving: false });
const bankTransferForm  = reactive({ from_account_id: null, to_account_id: null, amount: null, transaction_date: dayjs().format('YYYY-MM-DD'), reference: '', description: '' });

// ====== Column Definitions ======
const chequeColumns = [
    { title: 'Cheque No', dataIndex: 'cheque_no', key: 'cheque_no', width: 120 },
    { title: 'Party', key: 'party', width: 200 },
    { title: 'Type', key: 'type', width: 100 },
    { title: 'Amount', key: 'amount', width: 130, align: 'right' },
    { title: 'Date', dataIndex: 'cheque_date', key: 'cheque_date', width: 110 },
    { title: 'Bank', dataIndex: 'bank_name', key: 'bank_name', width: 130 },
    { title: 'Status', key: 'status', width: 120 },
    { title: 'Actions', key: 'actions', fixed: 'right', width: 220 },
];

const txnColumns = [
    { title: 'Date', dataIndex: 'transaction_date', key: 'transaction_date', width: 110 },
    { title: 'Type', key: 'type', width: 130 },
    { title: 'From', key: 'from', width: 160 },
    { title: 'To', key: 'to', width: 160 },
    { title: 'Amount', key: 'amount', width: 130, align: 'right' },
    { title: 'Ref', dataIndex: 'reference', key: 'reference', width: 120 },
    { title: 'Description', dataIndex: 'description', key: 'description' },
    { title: '', key: 'actions', width: 60, fixed: 'right' },
];

// ====== Loaders ======
const loadCheques = async () => {
    loadingCheques.value = true;
    try {
        const params = {};
        if (filters.type)   params.type   = filters.type;
        if (filters.status) params.status = filters.status;
        if (filters.search) params.search = filters.search;
        const res = await axiosAdmin.get('party-cheques', { params });
        cheques.value = res.data.data || [];
        Object.assign(summary, res.data.summary || {});
    } catch { message.error('Failed to load cheques.'); }
    finally  { loadingCheques.value = false; }
};

const loadTransactions = async () => {
    loadingTxns.value = true;
    try {
        const params = {};
        if (txnFilters.type)      params.type      = txnFilters.type;
        if (txnFilters.date_from) params.date_from = txnFilters.date_from;
        if (txnFilters.date_to)   params.date_to   = txnFilters.date_to;
        const res = await axiosAdmin.get('bank-transactions', { params });
        transactions.value = res.data.data || [];
        txnStats.total_deposits  = res.data.total_deposits  || 0;
        txnStats.total_transfers = res.data.total_transfers || 0;
    } catch { message.error('Failed to load transactions.'); }
    finally  { loadingTxns.value = false; }
};

const loadBankAccounts = async () => {
    try {
        const res = await axiosAdmin.get('bank-accounts');
        bankAccounts.value = (res.data.data || res.data || []);
    } catch {}
};

const loadParties = async () => {
    loadingParties.value = true;
    try {
        const endpoint = chequeForm.party_type === 'customer' ? 'customers' : 'suppliers';
        const res = await axiosAdmin.get(`${endpoint}?limit=5000&fields=xid,name`);
        parties.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
    } catch {}
    finally { loadingParties.value = false; }
};

const onTabChange = (tab) => {
    if (tab === 'deposits') loadTransactions();
};

// ====== Cheque actions ======
const openAddCheque = () => {
    Object.assign(chequeForm, { type: 'received', party_type: 'customer', party_id: null, cheque_no: '', amount: null, cheque_date: dayjs().format('YYYY-MM-DD'), bank_name: '', bank_account_id: null, remarks: '' });
    loadParties();
    chequeModal.visible = true;
};

const submitCheque = async () => {
    chequeModal.saving = true;
    try {
        await axiosAdmin.post('party-cheques', chequeForm);
        message.success('Cheque recorded.');
        chequeModal.visible = false;
        loadCheques();
    } catch (e) {
        message.error(e.response?.data?.message || 'Failed to save.');
    } finally { chequeModal.saving = false; }
};

const deleteCheque = async (record) => {
    try {
        await axiosAdmin.delete(`party-cheques/${record.xid}`);
        message.success('Cheque deleted.');
        loadCheques();
    } catch (e) { message.error(e.response?.data?.message || 'Failed to delete.'); }
};

const openDeposit = (record) => {
    depositModal.record = record;
    Object.assign(depositForm, { bank_account_id: null, action_date: dayjs().format('YYYY-MM-DD'), remarks: '' });
    depositModal.visible = true;
};
const submitDeposit = async () => {
    depositModal.saving = true;
    try {
        await axiosAdmin.post(`party-cheques/${depositModal.record.xid}/deposit`, depositForm);
        message.success('Cheque deposited to bank.');
        depositModal.visible = false;
        loadCheques();
    } catch (e) { message.error(e.response?.data?.message || 'Failed.'); }
    finally { depositModal.saving = false; }
};

const openClear = (record) => {
    clearModal.record = record;
    Object.assign(clearForm, { action_date: dayjs().format('YYYY-MM-DD') });
    clearModal.visible = true;
};
const submitClear = async () => {
    clearModal.saving = true;
    try {
        await axiosAdmin.post(`party-cheques/${clearModal.record.xid}/clear`, clearForm);
        message.success('Cheque cleared.');
        clearModal.visible = false;
        loadCheques();
    } catch (e) { message.error(e.response?.data?.message || 'Failed.'); }
    finally { clearModal.saving = false; }
};

const openBounce = (record) => {
    bounceModal.record = record;
    bounceForm.remarks = '';
    bounceModal.visible = true;
};
const submitBounce = async () => {
    bounceModal.saving = true;
    try {
        await axiosAdmin.post(`party-cheques/${bounceModal.record.xid}/bounce`, bounceForm);
        message.success('Cheque marked as bounced.');
        bounceModal.visible = false;
        loadCheques();
    } catch (e) { message.error(e.response?.data?.message || 'Failed.'); }
    finally { bounceModal.saving = false; }
};

// ====== Cash Deposit / Bank Transfer ======
const openCashDeposit = () => {
    Object.assign(cashDepositForm, { to_account_id: null, amount: null, transaction_date: dayjs().format('YYYY-MM-DD'), reference: '', description: '' });
    cashDepositModal.visible = true;
};
const submitCashDeposit = async () => {
    cashDepositModal.saving = true;
    try {
        await axiosAdmin.post('bank-transactions', { ...cashDepositForm, type: 'cash_deposit' });
        message.success('Cash deposited to bank.');
        cashDepositModal.visible = false;
        loadTransactions();
    } catch (e) { message.error(e.response?.data?.message || 'Failed.'); }
    finally { cashDepositModal.saving = false; }
};

const openBankTransfer = () => {
    Object.assign(bankTransferForm, { from_account_id: null, to_account_id: null, amount: null, transaction_date: dayjs().format('YYYY-MM-DD'), reference: '', description: '' });
    bankTransferModal.visible = true;
};
const submitBankTransfer = async () => {
    bankTransferModal.saving = true;
    try {
        await axiosAdmin.post('bank-transactions', { ...bankTransferForm, type: 'bank_transfer' });
        message.success('Bank transfer recorded.');
        bankTransferModal.visible = false;
        loadTransactions();
    } catch (e) { message.error(e.response?.data?.message || 'Failed.'); }
    finally { bankTransferModal.saving = false; }
};

const deleteTxn = async (record) => {
    try {
        await axiosAdmin.delete(`bank-transactions/${record.xid}`);
        message.success('Deleted.');
        loadTransactions();
    } catch (e) { message.error(e.response?.data?.message || 'Failed.'); }
};

// ====== Helpers ======
const formatAmt = (v) => {
    const n = parseFloat(v || 0);
    return '৳ ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const statusColor = (s) => ({ in_hand: 'blue', pending: 'orange', deposited: 'cyan', cleared: 'green', bounced: 'red', returned: 'volcano' }[s] || 'default');
const statusLabel = (s) => ({ in_hand: 'In Hand', pending: 'Pending', deposited: 'Deposited', cleared: 'Cleared', bounced: 'Bounced', returned: 'Returned' }[s] || s);

const filterParty = (input, option) => option.children?.toLowerCase?.().includes(input.toLowerCase());

onMounted(() => {
    loadCheques();
    loadBankAccounts();
});
</script>

<style scoped>
.summary-card { border-radius: 12px; }
.card-inner { display: flex; align-items: center; gap: 16px; }
.card-icon { font-size: 32px; opacity: 0.85; }
.card-value { font-size: 24px; font-weight: 700; line-height: 1.2; }
.card-label { font-size: 13px; opacity: 0.8; margin-top: 2px; }

.in-hand-card  { background: linear-gradient(135deg, #1890ff, #096dd9); color: #fff; }
.pending-card  { background: linear-gradient(135deg, #fa8c16, #d46b08); color: #fff; }
.cleared-card  { background: linear-gradient(135deg, #52c41a, #389e0d); color: #fff; }
.bounced-card  { background: linear-gradient(135deg, #ff4d4f, #cf1322); color: #fff; }

.in-hand-card .card-icon,
.pending-card .card-icon,
.cleared-card .card-icon,
.bounced-card .card-icon { color: rgba(255,255,255,0.85); }

.banking-tabs-card { border-radius: 12px; }
</style>
