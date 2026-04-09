<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Category Accounting Mapping" class="p-0">
                <template #extra>
                    <a-button class="cam-save-all-btn" type="primary" :loading="saving" @click="saveAll">
                        <SaveOutlined /> Save All Changes
                        <span v-if="dirtyCount > 0" class="cam-dirty-badge">{{ dirtyCount }}</span>
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
                <a-breadcrumb-item>Category Mapping</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- ── Hero Banner ─────────────────────────────────────────── -->
    <div class="cam-hero">
        <div class="cam-hero-inner">
            <div class="cam-hero-left">
                <div class="cam-hero-icon"><ApartmentOutlined /></div>
                <div>
                    <div class="cam-hero-title">Category Accounting Mapping</div>
                    <div class="cam-hero-sub">Link product categories to ledger accounts for automatic journal entry generation</div>
                </div>
            </div>
            <div class="cam-kpi-chips">
                <div class="cam-chip cam-chip-total">
                    <span class="cam-chip-val">{{ categories.length }}</span>
                    <span class="cam-chip-label">Total</span>
                </div>
                <div class="cam-chip cam-chip-mapped">
                    <span class="cam-chip-val">{{ mappedCount }}</span>
                    <span class="cam-chip-label">Mapped</span>
                </div>
                <div class="cam-chip cam-chip-unmapped">
                    <span class="cam-chip-val">{{ categories.length - mappedCount }}</span>
                    <span class="cam-chip-label">Unmapped</span>
                </div>
                <div class="cam-chip cam-chip-unsaved" v-if="dirtyCount > 0">
                    <span class="cam-chip-val">{{ dirtyCount }}</span>
                    <span class="cam-chip-label">Unsaved</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Info Banner ─────────────────────────────────────────── -->
    <div class="cam-info-bar">
        <div class="cam-info-inner">
            <InfoCircleOutlined class="cam-info-icon" />
            <span>Journal entries for <b>sales</b>, <b>purchases</b>, <b>COGS</b>, and <b>inventory</b> will automatically use these accounts when transactions occur.</span>
        </div>
    </div>

    <!-- ── Filter Bar ──────────────────────────────────────────── -->
    <div class="cam-filter-bar">
        <div class="cam-filter-inner">
            <div class="cam-filter-left">
                <a-input-search
                    v-model:value="search"
                    placeholder="Search categories…"
                    allow-clear
                    class="cam-search"
                    style="width: 240px"
                />
                <a-select v-model:value="statusFilterVal" placeholder="All Status" allow-clear style="width: 140px" class="cam-sel">
                    <a-select-option value="mapped">Mapped</a-select-option>
                    <a-select-option value="unmapped">Not Mapped</a-select-option>
                    <a-select-option value="unsaved">Unsaved</a-select-option>
                </a-select>
            </div>
            <div class="cam-filter-right">
                <div class="cam-count-badge">{{ filteredCategories.length }} categories</div>
                <a-button class="cam-save-all-btn-sm" type="primary" :loading="saving" @click="saveAll">
                    <SaveOutlined /> Save All
                    <span v-if="dirtyCount > 0" class="cam-dirty-badge">{{ dirtyCount }}</span>
                </a-button>
            </div>
        </div>
    </div>

    <!-- ── Table Card ──────────────────────────────────────────── -->
    <div class="cam-table-wrap">
        <a-spin :spinning="loading">
            <a-table
                :dataSource="filteredCategories"
                :columns="columns"
                :pagination="{ pageSize: 20, showSizeChanger: true, showTotal: (t) => `${t} categories` }"
                rowKey="id"
                size="middle"
                :scroll="{ x: 1200 }"
                class="cam-table"
                bordered
            >
                <template #bodyCell="{ column, record }">

                    <template v-if="column.key === 'name'">
                        <div class="cam-cat-cell">
                            <div class="cam-cat-dot"></div>
                            <span class="cam-cat-name">{{ record.name }}</span>
                        </div>
                    </template>

                    <template v-if="column.key === 'inventory_account_id'">
                        <a-select
                            v-model:value="record.inventory_account_id"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                            :options="accountOptions('Asset')"
                            allow-clear
                            placeholder="Select account…"
                            class="cam-acc-sel"
                            @change="markDirty(record)"
                        />
                    </template>

                    <template v-if="column.key === 'purchase_account_id'">
                        <a-select
                            v-model:value="record.purchase_account_id"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                            :options="accountOptions('Asset')"
                            allow-clear
                            placeholder="Select account…"
                            class="cam-acc-sel"
                            @change="markDirty(record)"
                        />
                    </template>

                    <template v-if="column.key === 'sales_account_id'">
                        <a-select
                            v-model:value="record.sales_account_id"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                            :options="accountOptions('Income')"
                            allow-clear
                            placeholder="Select account…"
                            class="cam-acc-sel"
                            @change="markDirty(record)"
                        />
                    </template>

                    <template v-if="column.key === 'cogs_account_id'">
                        <a-select
                            v-model:value="record.cogs_account_id"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                            :options="accountOptions('COGS')"
                            allow-clear
                            placeholder="Select account…"
                            class="cam-acc-sel"
                            @change="markDirty(record)"
                        />
                    </template>

                    <template v-if="column.key === 'status'">
                        <span v-if="record._dirty" class="cam-badge cam-badge-unsaved">Unsaved</span>
                        <span v-else-if="isMapped(record)" class="cam-badge cam-badge-mapped">Mapped</span>
                        <span v-else class="cam-badge cam-badge-unmapped">Not Mapped</span>
                    </template>

                    <template v-if="column.key === 'actions'">
                        <a-button
                            size="small"
                            :type="record._dirty ? 'primary' : 'default'"
                            :loading="record._saving"
                            @click="saveOne(record)"
                            :disabled="!record._dirty"
                            class="cam-save-row-btn"
                        >
                            <SaveOutlined /> Save
                        </a-button>
                    </template>

                </template>
            </a-table>
        </a-spin>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { SaveOutlined, ApartmentOutlined, InfoCircleOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    name: 'CategoryAccountingMapping',
    components: { AdminPageHeader, SaveOutlined, ApartmentOutlined, InfoCircleOutlined },

    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading   = ref(false);
        const saving    = ref(false);
        const search    = ref('');
        const statusFilterVal = ref(undefined);
        const categories = ref([]);
        const accounts   = ref([]);

        const columns = [
            { title: 'Category',          key: 'name',                 dataIndex: 'name',    width: 200, fixed: 'left' },
            { title: 'Inventory Account', key: 'inventory_account_id', width: 220, tooltip: 'Asset account debited when inventory is received' },
            { title: 'Purchase Account',  key: 'purchase_account_id',  width: 220, tooltip: 'Asset account debited when purchasing stock' },
            { title: 'Sales Account',     key: 'sales_account_id',     width: 220, tooltip: 'Revenue account credited when goods are sold' },
            { title: 'COGS Account',      key: 'cogs_account_id',      width: 220, tooltip: 'Cost account debited when goods are sold' },
            { title: 'Status',            key: 'status',               width: 110 },
            { title: 'Action',            key: 'actions',              width: 90,  fixed: 'right' },
        ];

        const filteredCategories = computed(() => {
            let list = categories.value;
            if (search.value) {
                const q = search.value.toLowerCase();
                list = list.filter(c => c.name.toLowerCase().includes(q));
            }
            if (statusFilterVal.value === 'mapped') list = list.filter(c => isMapped(c) && !c._dirty);
            if (statusFilterVal.value === 'unmapped') list = list.filter(c => !isMapped(c));
            if (statusFilterVal.value === 'unsaved') list = list.filter(c => c._dirty);
            return list;
        });

        const dirtyCount = computed(() => categories.value.filter(c => c._dirty).length);
        const mappedCount = computed(() => categories.value.filter(c => isMapped(c)).length);

        const accountOptions = (type) => {
            const filtered = type ? accounts.value.filter(a => a.account_type === type) : accounts.value;
            return filtered.map(a => ({ value: a.id, label: `${a.account_code} — ${a.account_name}` }));
        };

        const isMapped = (record) =>
            record.inventory_account_id && record.purchase_account_id && record.sales_account_id && record.cogs_account_id;

        const markDirty = (record) => { record._dirty = true; };

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/category-mappings');
                const data = res.data ?? res;
                categories.value = (data.categories || []).map(c => ({ ...c, _dirty: false, _saving: false }));
                accounts.value = data.accounts || [];
            } catch (e) { message.error('Failed to load category mappings'); }
            finally { loading.value = false; }
        };

        const saveOne = async (record) => {
            record._saving = true;
            try {
                await axiosAdmin.put(`accounting/category-mappings/${record.id}`, {
                    sales_account_id:     record.sales_account_id     || null,
                    cogs_account_id:      record.cogs_account_id      || null,
                    inventory_account_id: record.inventory_account_id || null,
                    purchase_account_id:  record.purchase_account_id  || null,
                });
                record._dirty = false;
                message.success(`${record.name} mapping saved`);
            } catch (e) { message.error(`Failed to save ${record.name}`); }
            finally { record._saving = false; }
        };

        const saveAll = async () => {
            const dirty = categories.value.filter(c => c._dirty);
            if (!dirty.length) { message.info('No changes to save'); return; }
            saving.value = true;
            try {
                const mappings = dirty.map(c => ({
                    id: c.id,
                    sales_account_id:     c.sales_account_id     || null,
                    cogs_account_id:      c.cogs_account_id      || null,
                    inventory_account_id: c.inventory_account_id || null,
                    purchase_account_id:  c.purchase_account_id  || null,
                }));
                const res = await axiosAdmin.post('accounting/category-mappings/bulk', { mappings });
                const updated = res.data?.updated ?? res.updated ?? dirty.length;
                dirty.forEach(c => { c._dirty = false; });
                message.success(`${updated} category mappings saved successfully`);
            } catch (e) { message.error('Failed to save all mappings'); }
            finally { saving.value = false; }
        };

        onMounted(load);

        return { loading, saving, search, statusFilterVal, categories, accounts, columns, filteredCategories, dirtyCount, mappedCount, accountOptions, isMapped, markDirty, saveOne, saveAll };
    },
});
</script>

<style lang="less">
/* ── Hero ─────────────────────────────────────────────────────── */
.cam-hero {
    background: linear-gradient(135deg, #78350f 0%, #92400e 40%, #b45309 100%);
    padding: 24px 32px 20px;
    position: relative; overflow: hidden;
}
.cam-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(251,191,36,.2) 0%, transparent 60%);
    pointer-events: none;
}
.cam-hero-inner {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 20px; position: relative; z-index: 1;
}
.cam-hero-left { display: flex; align-items: center; gap: 16px; }
.cam-hero-icon {
    width: 52px; height: 52px; border-radius: 16px;
    background: rgba(255,255,255,.15); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: #fff;
}
.cam-hero-title { font-size: 22px; font-weight: 800; color: #fff; line-height: 1.2; }
.cam-hero-sub { font-size: 13px; color: rgba(255,255,255,.72); margin-top: 2px; }

/* KPI Chips */
.cam-kpi-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.cam-chip {
    display: flex; flex-direction: column; align-items: center;
    padding: 6px 16px; border-radius: 12px; border: 1px solid;
    backdrop-filter: blur(4px); min-width: 70px;
}
.cam-chip-val { font-size: 18px; font-weight: 800; line-height: 1.1; }
.cam-chip-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; margin-top: 1px; }
.cam-chip-total  { background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.3); color: #fff; }
.cam-chip-mapped { background: rgba(34,197,94,.2); border-color: rgba(34,197,94,.4); color: #86efac; }
.cam-chip-unmapped { background: rgba(239,68,68,.2); border-color: rgba(239,68,68,.4); color: #fca5a5; }
.cam-chip-unsaved { background: rgba(249,115,22,.25); border-color: rgba(249,115,22,.5); color: #fdba74; }

/* ── Info Bar ─────────────────────────────────────────────────── */
.cam-info-bar {
    background: #fffbeb; border-bottom: 1px solid #fde68a;
    padding: 10px 32px;
}
.cam-info-inner { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #92400e; }
.cam-info-icon { font-size: 15px; color: #d97706; flex-shrink: 0; }

/* ── Filter Bar ───────────────────────────────────────────────── */
.cam-filter-bar {
    background: rgba(255,255,255,.95); backdrop-filter: blur(12px);
    border-bottom: 1px solid #e2e8f0; padding: 14px 32px;
}
.cam-filter-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.cam-filter-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.cam-filter-right { display: flex; align-items: center; gap: 10px; }
.cam-search .ant-input { border-radius: 8px !important; }
.cam-sel .ant-select-selector { border-radius: 8px !important; }
.cam-count-badge {
    background: #fffbeb; border: 1px solid #fde68a;
    color: #92400e; border-radius: 20px; padding: 3px 12px;
    font-size: 12px; font-weight: 700;
}
.cam-save-all-btn {
    border-radius: 8px !important; font-weight: 700 !important;
    background: #d97706 !important; border-color: #d97706 !important;
    display: inline-flex; align-items: center; gap: 6px;
}
.cam-save-all-btn:hover { background: #b45309 !important; border-color: #b45309 !important; }
.cam-save-all-btn-sm {
    border-radius: 8px !important; font-weight: 700 !important;
    background: #d97706 !important; border-color: #d97706 !important;
    display: inline-flex; align-items: center; gap: 6px;
}
.cam-save-all-btn-sm:hover { background: #b45309 !important; border-color: #b45309 !important; }
.cam-dirty-badge {
    background: rgba(255,255,255,.3); border-radius: 20px;
    padding: 0 7px; font-size: 11px; font-weight: 800;
    min-width: 20px; text-align: center;
}

/* ── Table ────────────────────────────────────────────────────── */
.cam-table-wrap {
    background: #fff; margin: 20px 24px; border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.07); overflow: hidden;
}
.cam-table .ant-table-thead > tr > th {
    background: #fffbeb !important; color: #78350f !important;
    font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 2px solid #fde68a !important;
}
.cam-table .ant-table-tbody > tr:hover td { background: #fffbeb !important; }

.cam-cat-cell { display: flex; align-items: center; gap: 8px; }
.cam-cat-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); flex-shrink: 0; }
.cam-cat-name { font-weight: 700; color: #1e293b; font-size: 14px; }

.cam-acc-sel .ant-select-selector { border-radius: 6px !important; font-size: 12px !important; }

.cam-badge {
    display: inline-block; padding: 2px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
}
.cam-badge-mapped  { background: #dcfce7; color: #166534; }
.cam-badge-unmapped { background: #fee2e2; color: #991b1b; }
.cam-badge-unsaved { background: #ffedd5; color: #9a3412; }

.cam-save-row-btn { border-radius: 6px !important; font-size: 12px !important; font-weight: 600 !important; }
</style>
