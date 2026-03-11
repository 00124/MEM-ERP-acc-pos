<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header
                title="GRN"
                @back="() => $router.go(-1)"
                class="p-0"
            >
                <template #extra>
                    <a-button type="primary" @click="printGrn">
                        <template #icon><PrinterOutlined /></template>
                        {{ $t('common.print') }}
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t('menu.purchases') }}</a-breadcrumb-item>
                <a-breadcrumb-item><router-link :to="{ name: 'admin.stock.grn.index' }">GRN</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t('common.details') }}</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card v-if="grn" class="page-content-container mt-20 mb-20">
        <a-descriptions bordered :column="2">
            <a-descriptions-item label="Document No">{{ grn.invoice_number }}</a-descriptions-item>
            <a-descriptions-item label="GRN Date">{{ formatDate(grn.order_date) }}</a-descriptions-item>
            <a-descriptions-item label="PO Number">{{ poNumber }}</a-descriptions-item>
            <a-descriptions-item label="Supplier Name">{{ grn.user?.name || '-' }}</a-descriptions-item>
            <a-descriptions-item label="Supplier Invoice Number">{{ grn.supplier_invoice_number || '-' }}</a-descriptions-item>
            <a-descriptions-item label="Delivery Challan No">{{ grn.delivery_challan_no || '-' }}</a-descriptions-item>
            <a-descriptions-item label="Warehouse / Store">{{ grn.warehouse?.name || '-' }}</a-descriptions-item>
        </a-descriptions>
        <a-divider>Item Details</a-divider>
        <a-table
            :data-source="grn.items || []"
            :columns="detailColumns"
            :pagination="false"
            size="small"
            :row-key="(r) => r.xid"
        >
            <template #bodyCell="{ column, record, index }">
                <template v-if="column.dataIndex === 'sn'">{{ index + 1 }}</template>
                <template v-else-if="column.dataIndex === 'item_code'">{{ (record.item_code ?? record.product?.item_code) || '-' }}</template>
                <template v-else-if="column.dataIndex === 'name'">{{ record.name || record.product?.name || '-' }}</template>
                <template v-else-if="column.dataIndex === 'po_qty'">{{ record.quantity }}</template>
                <template v-else-if="column.dataIndex === 'received'">{{ record.received_quantity ?? record.quantity }}</template>
                <template v-else-if="column.dataIndex === 'short_damaged'">{{ record.short_damaged_quantity ?? 0 }}</template>
            </template>
        </a-table>
        <a-divider>Summary</a-divider>
        <p><strong>Total PO Items:</strong> {{ summaryTotalPo }}</p>
        <p><strong>Total Received:</strong> {{ summaryTotalReceived }}</p>
        <p><strong>Total Short/Damaged:</strong> {{ summaryTotalShort }}</p>
        <a-divider>Receiver Information</a-divider>
        <a-row :gutter="16">
            <a-col :span="8">
                <p><strong>Received By:</strong> {{ grn.received_by_name || '-' }}</p>
                <p>Date: {{ formatDate(grn.received_by_date) }}</p>
            </a-col>
            <a-col :span="8">
                <p><strong>Checked By:</strong> {{ grn.checked_by_name || '-' }}</p>
                <p>Date: {{ formatDate(grn.checked_by_date) }}</p>
            </a-col>
            <a-col :span="8">
                <p><strong>Approved By:</strong> {{ grn.approved_by_name || '-' }}</p>
                <p>Date: {{ formatDate(grn.approved_by_date) }}</p>
            </a-col>
        </a-row>
    </a-card>
    <a-spin v-else :spinning="true" style="min-height: 200px" />
</template>
<script>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { PrinterOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import dayjs from "dayjs";

export default {
    components: { PrinterOutlined, AdminPageHeader },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const { t } = useI18n();
        const grn = ref(null);
        const dateFormat = "YYYY-MM-DD";

        const detailColumns = [
            { title: "#", dataIndex: "sn", width: 50 },
            { title: "Item Code", dataIndex: "item_code", width: 120 },
            { title: "Name", dataIndex: "name" },
            { title: "PO Qty", dataIndex: "po_qty", width: 100 },
            { title: "Received Qty", dataIndex: "received", width: 100 },
            { title: "Short/Damaged", dataIndex: "short_damaged", width: 100 },
        ];

        const poNumber = computed(() => {
            const o = grn.value;
            return (o && o.parent_order && o.parent_order.invoice_number) || "-";
        });

        const summaryTotalPo = computed(() => {
            const items = grn.value?.items || [];
            return items.reduce((s, i) => s + (Number(i.quantity) || 0), 0);
        });
        const summaryTotalReceived = computed(() => {
            const items = grn.value?.items || [];
            return items.reduce((s, i) => s + (Number(i.received_quantity ?? i.quantity) || 0), 0);
        });
        const summaryTotalShort = computed(() => {
            const items = grn.value?.items || [];
            return items.reduce((s, i) => s + (Number(i.short_damaged_quantity) || 0), 0);
        });

        function formatDate(d) {
            if (!d) return "-";
            return dayjs(d).format(dateFormat);
        }

        function printGrn() {
            if (!grn.value || !grn.value.xid) return;
            router.push({ name: "admin.stock.grn.print", params: { id: grn.value.xid } });
        }

        onMounted(() => {
            const id = route.params.id;
            if (!id) return;
            axiosAdmin.get(`grn/${id}`).then((res) => {
                const d = res.data || res;
                grn.value = { ...(d.order || d), items: d.items || (d.order && d.order.items) || [] };
            });
        });

        return {
            grn,
            detailColumns,
            poNumber,
            summaryTotalPo,
            summaryTotalReceived,
            summaryTotalShort,
            formatDate,
            printGrn,
        };
    },
};
</script>
