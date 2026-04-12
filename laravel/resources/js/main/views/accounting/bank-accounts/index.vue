<template>
    <div class="bank-accounts-page">
        <AdminPageHeader>
            <template #header>
                <a-page-header title="Bank Accounts" class="p-0">
                    <template #extra>
                        <a-button type="primary" class="btn-add-bank" @click="openCreateModal">
                            <PlusOutlined /> Add Bank Account
                        </a-button>
                    </template>
                </a-page-header>
            </template>
            <template #breadcrumb>
                <a-breadcrumb separator="-" style="font-size: 12px">
                    <a-breadcrumb-item>
                        <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                    </a-breadcrumb-item>
                    <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                    <a-breadcrumb-item>Bank Accounts</a-breadcrumb-item>
                </a-breadcrumb>
            </template>
        </AdminPageHeader>

        <!-- Hero / Summary Cards -->
        <div class="bank-hero">
            <a-row :gutter="[16, 16]">
                <a-col :xs="24" :sm="12" :md="6">
                    <div class="bank-stat-card bank-stat--primary">
                        <div class="bsc-icon"><BankOutlined /></div>
                        <div class="bsc-body">
                            <div class="bsc-label">Total Banks</div>
                            <div class="bsc-value">{{ summary.total_banks }}</div>
                            <div class="bsc-sub">active accounts</div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <div class="bank-stat-card bank-stat--green">
                        <div class="bsc-icon"><WalletOutlined /></div>
                        <div class="bsc-body">
                            <div class="bsc-label">Total Balance</div>
                            <div class="bsc-value">{{ formatPKR(summary.total_balance) }}</div>
                            <div class="bsc-sub">combined balance</div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <div class="bank-stat-card bank-stat--blue">
                        <div class="bsc-icon"><ArrowDownOutlined /></div>
                        <div class="bsc-body">
                            <div class="bsc-label">Total Receipts</div>
                            <div class="bsc-value">{{ formatPKR(summary.total_in) }}</div>
                            <div class="bsc-sub">payments in</div>
                        </div>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="12" :md="6">
                    <div class="bank-stat-card bank-stat--orange">
                        <div class="bsc-icon"><ArrowUpOutlined /></div>
                        <div class="bsc-body">
                            <div class="bsc-label">Total Payments</div>
                            <div class="bsc-value">{{ formatPKR(summary.total_out) }}</div>
                            <div class="bsc-sub">payments out</div>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </div>

        <!-- Bank Account Cards Grid -->
        <div class="bank-grid" v-if="!loading && accounts.length">
            <a-row :gutter="[16, 16]">
                <a-col :xs="24" :sm="24" :md="12" :lg="8" v-for="acc in accounts" :key="acc.xid">
                    <div class="bank-card" :class="{ 'bank-card--negative': acc.current_balance < 0 }">
                        <div class="bank-card__header">
                            <div class="bank-card__icon">
                                <BankOutlined />
                            </div>
                            <div class="bank-card__title">
                                <div class="bank-card__name">{{ acc.account_name }}</div>
                                <div class="bank-card__code">{{ acc.account_code }}</div>
                            </div>
                            <a-dropdown>
                                <a-button type="text" size="small" class="bank-card__menu">
                                    <MoreOutlined />
                                </a-button>
                                <template #overlay>
                                    <a-menu>
                                        <a-menu-item key="edit" @click="openEditModal(acc)">
                                            <EditOutlined /> Edit
                                        </a-menu-item>
                                        <a-menu-item key="txn" @click="openTransactions(acc)">
                                            <UnorderedListOutlined /> Transactions
                                        </a-menu-item>
                                        <a-menu-item key="delete" @click="deleteAccount(acc)" class="text-red">
                                            <DeleteOutlined /> Delete
                                        </a-menu-item>
                                    </a-menu>
                                </template>
                            </a-dropdown>
                        </div>

                        <div class="bank-card__body">
                            <div class="bank-card__balance-label">Current Balance</div>
                            <div class="bank-card__balance" :class="{ 'neg': acc.current_balance < 0 }">
                                {{ formatPKR(acc.current_balance) }}
                            </div>
                        </div>

                        <div class="bank-card__footer">
                            <div class="bank-card__info-item" v-if="acc.account_number">
                                <span class="info-label">A/C No</span>
                                <span class="info-val">{{ acc.account_number }}</span>
                            </div>
                            <div class="bank-card__info-item" v-if="acc.branch_name">
                                <span class="info-label">Branch</span>
                                <span class="info-val">{{ acc.branch_name }}</span>
                            </div>
                            <div class="bank-card__info-item">
                                <span class="info-label">Opening</span>
                                <span class="info-val">{{ formatPKR(acc.opening_balance) }}</span>
                            </div>
                        </div>

                        <div class="bank-card__stats">
                            <div class="bank-card__stat bank-card__stat--in">
                                <ArrowDownOutlined />
                                <span>{{ formatPKR(acc.total_in) }}</span>
                            </div>
                            <div class="bank-card__stat-divider"></div>
                            <div class="bank-card__stat bank-card__stat--out">
                                <ArrowUpOutlined />
                                <span>{{ formatPKR(acc.total_out) }}</span>
                            </div>
                            <div class="bank-card__txn-count">
                                {{ acc.transaction_count }} txns
                            </div>
                        </div>

                        <div class="bank-card__actions">
                            <a-button size="small" block @click="openTransactions(acc)">
                                <UnorderedListOutlined /> View Transactions
                            </a-button>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </div>

        <div v-else-if="!loading && !accounts.length" class="bank-empty">
            <BankOutlined class="bank-empty__icon" />
            <div class="bank-empty__title">No Bank Accounts Yet</div>
            <div class="bank-empty__sub">Add your first bank account to start tracking balances</div>
            <a-button type="primary" @click="openCreateModal" style="margin-top: 16px">
                <PlusOutlined /> Add Bank Account
            </a-button>
        </div>

        <div v-if="loading" style="text-align:center; padding: 60px">
            <a-spin size="large" />
        </div>

        <!-- Create / Edit Modal -->
        <a-modal
            v-model:open="showFormModal"
            :title="editingAccount ? 'Edit Bank Account' : 'Add Bank Account'"
            :footer="null"
            :width="520"
            centered
        >
            <a-form layout="vertical" @finish="saveAccount">
                <a-row :gutter="[16, 0]">
                    <a-col :span="24">
                        <a-form-item label="Bank Name" required>
                            <a-input v-model:value="form.account_name" placeholder="e.g. HBL, Meezan Bank, MCB" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Account Number">
                            <a-input v-model:value="form.account_number" placeholder="IBAN / Account No" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Branch Name">
                            <a-input v-model:value="form.branch_name" placeholder="e.g. Main Branch, Gulberg" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Opening Balance (PKR)">
                            <a-input-number
                                v-model:value="form.opening_balance"
                                :min="0"
                                style="width: 100%"
                                :formatter="v => v ? v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0'"
                                :parser="v => v.replace(/,/g, '')"
                            />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12" v-if="editingAccount">
                        <a-form-item label="Status">
                            <a-select v-model:value="form.status" style="width: 100%">
                                <a-select-option value="active">Active</a-select-option>
                                <a-select-option value="inactive">Inactive</a-select-option>
                            </a-select>
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Description / Notes">
                            <a-textarea v-model:value="form.description" :rows="2" placeholder="Optional notes" />
                        </a-form-item>
                    </a-col>
                </a-row>

                <div style="text-align: right; margin-top: 8px">
                    <a-space>
                        <a-button @click="showFormModal = false">Cancel</a-button>
                        <a-button type="primary" html-type="submit" :loading="saving">
                            {{ editingAccount ? 'Update' : 'Create Account' }}
                        </a-button>
                    </a-space>
                </div>
            </a-form>
        </a-modal>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';
import {
    BankOutlined, WalletOutlined, PlusOutlined, EditOutlined,
    DeleteOutlined, MoreOutlined, UnorderedListOutlined,
    ArrowDownOutlined, ArrowUpOutlined,
} from '@ant-design/icons-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        BankOutlined, WalletOutlined, PlusOutlined, EditOutlined,
        DeleteOutlined, MoreOutlined, UnorderedListOutlined,
        ArrowDownOutlined, ArrowUpOutlined,
    },

    setup() {
        const router = useRouter();
        const accounts = ref([]);
        const loading = ref(false);
        const saving = ref(false);
        const showFormModal = ref(false);
        const editingAccount = ref(null);

        const summary = ref({ total_banks: 0, total_balance: 0, total_in: 0, total_out: 0 });

        const emptyForm = () => ({
            account_name: '', account_number: '', branch_name: '',
            opening_balance: 0, description: '', status: 'active',
        });
        const form = ref(emptyForm());

        const formatPKR = (val) => {
            if (val === null || val === undefined) return 'PKR 0';
            const n = Number(val);
            return (n < 0 ? '-PKR ' : 'PKR ') + Math.abs(n).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        };

        const fetchAccounts = async () => {
            loading.value = true;
            try {
                const res = await window.axiosAdmin.get('bank-accounts');
                accounts.value = res.data.data;
                summary.value = res.data.summary;
            } catch {
                message.error('Failed to load bank accounts.');
            } finally {
                loading.value = false;
            }
        };

        const openCreateModal = () => {
            editingAccount.value = null;
            form.value = emptyForm();
            showFormModal.value = true;
        };

        const openEditModal = (acc) => {
            editingAccount.value = acc;
            form.value = {
                account_name: acc.account_name,
                account_number: acc.account_number || '',
                branch_name: acc.branch_name || '',
                opening_balance: acc.opening_balance || 0,
                description: acc.description || '',
                status: acc.status || 'active',
            };
            showFormModal.value = true;
        };

        const saveAccount = async () => {
            if (!form.value.account_name.trim()) {
                message.warning('Bank name is required.');
                return;
            }
            saving.value = true;
            try {
                if (editingAccount.value) {
                    await window.axiosAdmin.put(`bank-accounts/${editingAccount.value.xid}`, form.value);
                    message.success('Bank account updated.');
                } else {
                    await window.axiosAdmin.post('bank-accounts', form.value);
                    message.success('Bank account created and added to Chart of Accounts.');
                }
                showFormModal.value = false;
                fetchAccounts();
            } catch (err) {
                message.error(err.response?.data?.message || 'Save failed.');
            } finally {
                saving.value = false;
            }
        };

        const deleteAccount = async (acc) => {
            try {
                await window.axiosAdmin.delete(`bank-accounts/${acc.xid}`);
                message.success('Bank account deleted.');
                fetchAccounts();
            } catch (err) {
                message.error(err.response?.data?.message || 'Delete failed.');
            }
        };

        const openTransactions = (acc) => {
            router.push({ name: 'admin.accounting.bank_accounts.transactions', params: { id: acc.xid } });
        };

        onMounted(fetchAccounts);

        return {
            accounts, loading, saving, showFormModal, editingAccount, form, summary,
            formatPKR, fetchAccounts, openCreateModal, openEditModal, saveAccount,
            deleteAccount, openTransactions,
        };
    },
});
</script>

<style scoped>
.bank-accounts-page { background: #f4f6f9; min-height: 100vh; }

/* Hero Cards */
.bank-hero { margin-bottom: 24px; }
.bank-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    transition: transform .2s;
    height: 100%;
}
.bank-stat-card:hover { transform: translateY(-2px); }
.bsc-icon {
    width: 52px; height: 52px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.bank-stat--primary .bsc-icon { background: #eff6ff; color: #2563eb; }
.bank-stat--green   .bsc-icon { background: #dcfce7; color: #16a34a; }
.bank-stat--blue    .bsc-icon { background: #e0f2fe; color: #0284c7; }
.bank-stat--orange  .bsc-icon { background: #fff7ed; color: #ea580c; }
.bsc-label { font-size: 12px; color: #6b7280; font-weight: 500; text-transform: uppercase; letter-spacing: .4px; }
.bsc-value { font-size: 20px; font-weight: 700; color: #111827; }
.bsc-sub   { font-size: 11px; color: #9ca3af; margin-top: 2px; }

/* Bank Cards Grid */
.bank-grid { margin-bottom: 24px; }
.bank-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    overflow: hidden;
    transition: box-shadow .2s, transform .2s;
    border: 2px solid transparent;
}
.bank-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.12); transform: translateY(-2px); }
.bank-card--negative { border-color: #fee2e2; }

.bank-card__header {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 16px 12px;
    border-bottom: 1px solid #f3f4f6;
}
.bank-card__icon {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 20px; flex-shrink: 0;
}
.bank-card__title { flex: 1; min-width: 0; }
.bank-card__name { font-weight: 700; font-size: 15px; color: #111827; }
.bank-card__code { font-size: 11px; color: #9ca3af; }
.bank-card__menu { color: #6b7280; }

.bank-card__body { padding: 16px 16px 8px; }
.bank-card__balance-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
.bank-card__balance {
    font-size: 26px; font-weight: 800;
    color: #111827; line-height: 1.2; margin-top: 4px;
}
.bank-card__balance.neg { color: #dc2626; }

.bank-card__footer {
    padding: 0 16px 12px;
    display: flex; flex-wrap: wrap; gap: 12px;
}
.bank-card__info-item { display: flex; flex-direction: column; }
.info-label { font-size: 10px; color: #9ca3af; text-transform: uppercase; }
.info-val   { font-size: 12px; color: #374151; font-weight: 500; }

.bank-card__stats {
    padding: 10px 16px;
    background: #f8fafc;
    display: flex; align-items: center; gap: 8px;
    border-top: 1px solid #f3f4f6;
}
.bank-card__stat { display: flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; flex: 1; }
.bank-card__stat--in  { color: #16a34a; }
.bank-card__stat--out { color: #ea580c; }
.bank-card__stat-divider { width: 1px; height: 20px; background: #e5e7eb; }
.bank-card__txn-count { font-size: 11px; color: #9ca3af; margin-left: auto; }

.bank-card__actions { padding: 12px 16px; }

/* Empty State */
.bank-empty {
    text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
}
.bank-empty__icon  { font-size: 56px; color: #d1d5db; display: block; margin-bottom: 16px; }
.bank-empty__title { font-size: 20px; font-weight: 600; color: #374151; }
.bank-empty__sub   { color: #9ca3af; margin-top: 6px; }

.btn-add-bank { border-radius: 8px !important; }
.text-red { color: #ef4444 !important; }
</style>
