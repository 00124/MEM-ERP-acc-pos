<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Chart of Accounts" class="p-0">
                <template #extra>
                    <a-button class="coa-add-btn" type="primary" @click="openAddModal()">
                        <PlusOutlined /> Add Account
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
                <a-breadcrumb-item>Chart of Accounts</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- ── Hero Banner ─────────────────────────────────────────── -->
    <div class="coa-hero">
        <div class="coa-hero-inner">
            <div class="coa-hero-left">
                <div class="coa-hero-icon"><BankOutlined /></div>
                <div>
                    <div class="coa-hero-title">Chart of Accounts</div>
                    <div class="coa-hero-sub">Manage your company's general ledger account structure</div>
                </div>
            </div>
            <div class="coa-kpi-chips">
                <div class="coa-chip" v-for="kpi in kpiChips" :key="kpi.label" :style="{ background: kpi.bg, borderColor: kpi.border, color: kpi.color }">
                    <span class="coa-chip-val">{{ kpi.val }}</span>
                    <span class="coa-chip-label">{{ kpi.label }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Filter Bar ──────────────────────────────────────────── -->
    <div class="coa-filter-bar">
        <div class="coa-filter-inner">
            <div class="coa-filter-left">
                <a-input-search
                    v-model:value="search"
                    placeholder="Search accounts by name or code…"
                    allow-clear
                    class="coa-search"
                    style="width: 280px"
                />
                <a-select v-model:value="typeFilter" placeholder="All Types" allow-clear style="width: 160px" class="coa-sel">
                    <a-select-option v-for="t in accountTypes" :key="t" :value="t">{{ t }}</a-select-option>
                </a-select>
                <a-select v-model:value="statusFilter" placeholder="All Status" allow-clear style="width: 130px" class="coa-sel">
                    <a-select-option value="active">Active</a-select-option>
                    <a-select-option value="inactive">Inactive</a-select-option>
                </a-select>
            </div>
            <div class="coa-filter-right">
                <div class="coa-count-badge">{{ filteredAccounts.length }} accounts</div>
                <a-button class="coa-add-btn-sm" type="primary" @click="openAddModal()">
                    <PlusOutlined /> Add Account
                </a-button>
            </div>
        </div>
    </div>

    <!-- ── Table Card ──────────────────────────────────────────── -->
    <div class="coa-table-wrap">
        <a-spin :spinning="loading">
            <a-table
                :dataSource="filteredAccounts"
                :columns="columns"
                :pagination="{ pageSize: 20, showSizeChanger: true, showQuickJumper: true, showTotal: (t) => `${t} accounts` }"
                :scroll="{ x: 900 }"
                rowKey="id"
                size="middle"
                class="coa-table"
                :row-class-name="(r) => r.parent_id ? 'coa-row-child' : 'coa-row-parent'"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'account_code'">
                        <span class="coa-code">{{ record.account_code }}</span>
                    </template>
                    <template v-if="column.key === 'account_name'">
                        <div class="coa-name-cell">
                            <span v-if="record.parent_id" class="coa-child-dot"></span>
                            <span :class="record.parent_id ? 'coa-child-name' : 'coa-parent-name'">
                                {{ record.account_name }}
                            </span>
                            <span v-if="record.description" class="coa-desc-hint">{{ record.description }}</span>
                        </div>
                    </template>
                    <template v-if="column.key === 'account_type'">
                        <span class="coa-type-badge" :style="typeBadgeStyle(record.account_type)">
                            {{ record.account_type }}
                        </span>
                    </template>
                    <template v-if="column.key === 'status'">
                        <span :class="record.status ? 'coa-status-active' : 'coa-status-inactive'">
                            <span class="coa-status-dot"></span>
                            {{ record.status ? 'Active' : 'Inactive' }}
                        </span>
                    </template>
                    <template v-if="column.key === 'action'">
                        <a-space>
                            <a-button class="coa-action-btn" size="small" @click="openEditModal(record)">
                                <EditOutlined /> Edit
                            </a-button>
                            <a-popconfirm
                                title="Delete this account?"
                                ok-text="Delete"
                                ok-type="danger"
                                @confirm="deleteAccount(record.id)"
                                v-if="record.parent_id"
                            >
                                <a-button class="coa-del-btn" size="small" danger>
                                    <DeleteOutlined />
                                </a-button>
                            </a-popconfirm>
                        </a-space>
                    </template>
                </template>
            </a-table>
        </a-spin>
    </div>

    <!-- ── Add / Edit Modal ────────────────────────────────────── -->
    <a-modal
        v-model:open="modalVisible"
        :title="null"
        :footer="null"
        :width="520"
        class="coa-modal"
        destroy-on-close
    >
        <div class="coa-modal-header">
            <div class="coa-modal-icon"><BankOutlined /></div>
            <div>
                <div class="coa-modal-title">{{ editingId ? 'Edit Account' : 'New Account' }}</div>
                <div class="coa-modal-sub">{{ editingId ? 'Update account details' : 'Add a new ledger account' }}</div>
            </div>
        </div>

        <a-form layout="vertical" :model="form" class="coa-modal-form">
            <a-row :gutter="16">
                <a-col :span="10">
                    <a-form-item label="Account Code" required>
                        <a-input v-model:value="form.account_code" placeholder="e.g. 11001" class="coa-form-input" />
                    </a-form-item>
                </a-col>
                <a-col :span="14">
                    <a-form-item label="Account Name" required>
                        <a-input v-model:value="form.account_name" placeholder="Account name" class="coa-form-input" />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Account Type" required>
                        <a-select v-model:value="form.account_type" style="width:100%" class="coa-form-sel">
                            <a-select-option value="Asset">Asset</a-select-option>
                            <a-select-option value="Liability">Liability</a-select-option>
                            <a-select-option value="Equity">Equity</a-select-option>
                            <a-select-option value="Income">Income (Revenue)</a-select-option>
                            <a-select-option value="Expense">Expense</a-select-option>
                            <a-select-option value="COGS">Cost of Goods Sold</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Parent Account">
                        <a-select v-model:value="form.parent_id" style="width:100%" allow-clear placeholder="None (Top-level)" class="coa-form-sel">
                            <a-select-option v-for="a in parentAccounts" :key="a.id" :value="a.id">
                                {{ a.account_code }} — {{ a.account_name }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-form-item label="Description">
                <a-textarea v-model:value="form.description" :rows="2" placeholder="Optional description…" class="coa-form-input" />
            </a-form-item>
        </a-form>

        <!-- Type preview chip -->
        <div class="coa-modal-type-preview" v-if="form.account_type">
            <span class="coa-type-badge" :style="typeBadgeStyle(form.account_type)">{{ form.account_type }}</span>
            <span class="coa-preview-hint">This account will be categorised as {{ form.account_type }}</span>
        </div>

        <div class="coa-modal-footer">
            <a-button @click="modalVisible = false">Cancel</a-button>
            <a-button type="primary" :loading="saving" @click="saveAccount" class="coa-save-btn">
                {{ editingId ? 'Update Account' : 'Create Account' }}
            </a-button>
        </div>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined, BankOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    components: { AdminPageHeader, PlusOutlined, EditOutlined, DeleteOutlined, BankOutlined },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading = ref(false);
        const saving = ref(false);
        const flatAccounts = ref([]);
        const modalVisible = ref(false);
        const editingId = ref(null);
        const search = ref('');
        const typeFilter = ref(undefined);
        const statusFilter = ref(undefined);
        const form = ref({ account_code: '', account_name: '', account_type: 'Asset', parent_id: null, description: '' });

        const accountTypes = ['Asset', 'Liability', 'Equity', 'Income', 'Expense', 'COGS'];

        const columns = [
            { title: 'Code', dataIndex: 'account_code', key: 'account_code', width: 100 },
            { title: 'Account Name', dataIndex: 'account_name', key: 'account_name' },
            { title: 'Type', key: 'account_type', width: 140 },
            { title: 'Status', key: 'status', width: 100 },
            { title: 'Actions', key: 'action', width: 130, fixed: 'right' },
        ];

        const parentAccounts = computed(() => flatAccounts.value.filter(a => !a.parent_id));

        const filteredAccounts = computed(() => {
            let list = flatAccounts.value;
            if (search.value) {
                const q = search.value.toLowerCase();
                list = list.filter(a => a.account_name.toLowerCase().includes(q) || a.account_code.toLowerCase().includes(q));
            }
            if (typeFilter.value) list = list.filter(a => a.account_type === typeFilter.value);
            if (statusFilter.value === 'active') list = list.filter(a => a.status);
            if (statusFilter.value === 'inactive') list = list.filter(a => !a.status);
            return list;
        });

        const kpiChips = computed(() => {
            const all = flatAccounts.value;
            const typeMap = { Asset: { bg: 'rgba(59,130,246,.15)', border: 'rgba(59,130,246,.3)', color: '#3b82f6' }, Liability: { bg: 'rgba(239,68,68,.15)', border: 'rgba(239,68,68,.3)', color: '#ef4444' }, Equity: { bg: 'rgba(168,85,247,.15)', border: 'rgba(168,85,247,.3)', color: '#a855f7' }, Income: { bg: 'rgba(34,197,94,.15)', border: 'rgba(34,197,94,.3)', color: '#22c55e' }, Expense: { bg: 'rgba(249,115,22,.15)', border: 'rgba(249,115,22,.3)', color: '#f97316' }, COGS: { bg: 'rgba(234,179,8,.15)', border: 'rgba(234,179,8,.3)', color: '#eab308' } };
            return accountTypes.map(t => ({ label: t, val: all.filter(a => a.account_type === t).length, ...typeMap[t] }));
        });

        const typeColors = { Asset: { bg: '#dbeafe', color: '#1e40af' }, Liability: { bg: '#fee2e2', color: '#991b1b' }, Equity: { bg: '#f3e8ff', color: '#6b21a8' }, Income: { bg: '#dcfce7', color: '#166534' }, Expense: { bg: '#ffedd5', color: '#9a3412' }, COGS: { bg: '#fef9c3', color: '#713f12' } };
        const typeBadgeStyle = (type) => {
            const c = typeColors[type] || { bg: '#f1f5f9', color: '#475569' };
            return { background: c.bg, color: c.color };
        };

        const loadAccounts = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/coa');
                flatAccounts.value = res.data.flat || [];
            } catch (e) { message.error('Failed to load accounts'); }
            finally { loading.value = false; }
        };

        const openAddModal = () => {
            editingId.value = null;
            form.value = { account_code: '', account_name: '', account_type: 'Asset', parent_id: null, description: '' };
            modalVisible.value = true;
        };

        const openEditModal = (record) => {
            editingId.value = record.id;
            form.value = { account_code: record.account_code, account_name: record.account_name, account_type: record.account_type, parent_id: record.parent_id, description: record.description || '' };
            modalVisible.value = true;
        };

        const saveAccount = async () => {
            if (!form.value.account_code || !form.value.account_name) { message.warning('Code and Name are required'); return; }
            saving.value = true;
            try {
                if (editingId.value) {
                    await axiosAdmin.put(`accounting/coa/${editingId.value}`, form.value);
                    message.success('Account updated successfully');
                } else {
                    await axiosAdmin.post('accounting/coa', form.value);
                    message.success('Account created successfully');
                }
                modalVisible.value = false;
                loadAccounts();
            } catch (e) { message.error(e.response?.data?.message || 'Error saving account'); }
            finally { saving.value = false; }
        };

        const deleteAccount = async (id) => {
            try {
                await axiosAdmin.delete(`accounting/coa/${id}`);
                message.success('Account deleted');
                loadAccounts();
            } catch (e) { message.error(e.response?.data?.message || 'Cannot delete this account'); }
        };

        onMounted(loadAccounts);

        return { loading, saving, flatAccounts, modalVisible, editingId, form, columns, parentAccounts, filteredAccounts, kpiChips, typeFilter, statusFilter, search, accountTypes, typeBadgeStyle, openAddModal, openEditModal, saveAccount, deleteAccount };
    }
});
</script>

<style lang="less">
/* ── Hero ─────────────────────────────────────────────────────── */
.coa-hero {
    background: #fff;
    padding: 24px 32px 20px;
    margin-bottom: 0;
    position: relative;
    overflow: hidden;
    border-bottom: 1px solid #e2e8f0;
}
.coa-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(16,185,129,.06) 0%, transparent 60%);
    pointer-events: none;
}
.coa-hero-inner {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 20px; position: relative; z-index: 1;
}
.coa-hero-left { display: flex; align-items: center; gap: 16px; }
.coa-hero-icon {
    width: 52px; height: 52px; border-radius: 16px;
    background: linear-gradient(135deg, #059669, #047857);
    border: 1px solid rgba(5,150,105,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: #fff;
    box-shadow: 0 4px 12px rgba(5,150,105,.25);
}
.coa-hero-title { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1.2; }
.coa-hero-sub { font-size: 13px; color: #64748b; margin-top: 2px; }

/* KPI Chips */
.coa-kpi-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.coa-chip {
    display: flex; flex-direction: column; align-items: center;
    padding: 6px 14px; border-radius: 12px; border: 1px solid;
    min-width: 68px; cursor: default;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.coa-chip-val { font-size: 18px; font-weight: 800; line-height: 1.1; }
.coa-chip-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; opacity: .8; margin-top: 1px; }

/* ── Filter Bar ───────────────────────────────────────────────── */
.coa-filter-bar {
    background: rgba(255,255,255,.95); backdrop-filter: blur(12px);
    border-bottom: 1px solid #e2e8f0; padding: 14px 32px;
}
.coa-filter-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.coa-filter-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.coa-filter-right { display: flex; align-items: center; gap: 10px; }
.coa-search .ant-input { border-radius: 8px !important; }
.coa-sel .ant-select-selector { border-radius: 8px !important; }
.coa-count-badge {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    color: #166534; border-radius: 20px; padding: 3px 12px;
    font-size: 12px; font-weight: 700;
}
.coa-add-btn { border-radius: 8px !important; font-weight: 600; background: #059669 !important; border-color: #059669 !important; }
.coa-add-btn:hover { background: #047857 !important; border-color: #047857 !important; }
.coa-add-btn-sm { border-radius: 8px !important; font-weight: 600; background: #059669 !important; border-color: #059669 !important; }
.coa-add-btn-sm:hover { background: #047857 !important; border-color: #047857 !important; }

/* ── Table ────────────────────────────────────────────────────── */
.coa-table-wrap {
    background: #fff; margin: 20px 24px; border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.07); overflow: hidden;
}
.coa-table .ant-table-thead > tr > th {
    background: #f8fafc !important; color: #475569 !important;
    font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 2px solid #e2e8f0 !important;
}
.coa-row-parent { background: #fff !important; }
.coa-row-child { background: #f8fffe !important; }
.coa-row-parent:hover td, .coa-row-child:hover td { background: #f0fdf9 !important; }

.coa-code { font-family: 'Courier New', monospace; font-size: 12px; background: #f1f5f9; padding: 2px 7px; border-radius: 6px; color: #475569; font-weight: 700; }
.coa-name-cell { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.coa-child-dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; flex-shrink: 0; }
.coa-parent-name { font-weight: 700; color: #1e293b; font-size: 14px; }
.coa-child-name { font-weight: 500; color: #334155; font-size: 13px; }
.coa-desc-hint { font-size: 11px; color: #94a3b8; font-style: italic; }

.coa-type-badge {
    display: inline-block; padding: 2px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
}

.coa-status-active, .coa-status-inactive {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 600;
}
.coa-status-active { color: #059669; }
.coa-status-active .coa-status-dot { width: 7px; height: 7px; border-radius: 50%; background: #10b981; animation: coa-pulse 2s infinite; }
.coa-status-inactive { color: #ef4444; }
.coa-status-inactive .coa-status-dot { width: 7px; height: 7px; border-radius: 50%; background: #ef4444; }
@keyframes coa-pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

.coa-action-btn { border-radius: 6px !important; font-size: 12px !important; border-color: #e2e8f0 !important; color: #059669 !important; font-weight: 600 !important; }
.coa-action-btn:hover { background: #f0fdf4 !important; border-color: #10b981 !important; }
.coa-del-btn { border-radius: 6px !important; }

/* ── Modal ────────────────────────────────────────────────────── */
.coa-modal .ant-modal-content { border-radius: 20px !important; overflow: hidden; padding: 0 !important; }
.coa-modal .ant-modal-body { padding: 28px !important; }
.coa-modal-header {
    display: flex; align-items: center; gap: 14px;
    background: linear-gradient(135deg, #064e3b, #059669);
    margin: -28px -28px 24px; padding: 22px 28px;
}
.coa-modal-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(255,255,255,.2); display: flex; align-items: center;
    justify-content: center; font-size: 20px; color: #fff;
}
.coa-modal-title { font-size: 18px; font-weight: 800; color: #fff; }
.coa-modal-sub { font-size: 12px; color: rgba(255,255,255,.75); margin-top: 2px; }
.coa-modal-form .ant-form-item-label label { font-size: 12px; font-weight: 700; color: #475569; }
.coa-form-input { border-radius: 8px !important; }
.coa-form-sel .ant-select-selector { border-radius: 8px !important; }
.coa-modal-type-preview {
    display: flex; align-items: center; gap: 10px;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 10px 14px; margin-top: 4px; margin-bottom: 20px;
}
.coa-preview-hint { font-size: 12px; color: #64748b; }
.coa-modal-footer {
    display: flex; justify-content: flex-end; gap: 10px;
    border-top: 1px solid #f1f5f9; padding-top: 16px;
}
.coa-save-btn { background: #059669 !important; border-color: #059669 !important; border-radius: 8px !important; font-weight: 700 !important; padding: 0 24px !important; }
.coa-save-btn:hover { background: #047857 !important; border-color: #047857 !important; }
</style>
