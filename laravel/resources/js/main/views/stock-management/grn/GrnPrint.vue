<template>
    <div class="grn-print-page" v-if="grn">
        <div class="no-print" style="padding: 16px; border-bottom: 1px solid #eee;">
            <a-button class="mr-2" @click="goBack">{{ $t('common.back') }}</a-button>
            <a-button type="primary" @click="doPrint">{{ $t('common.print') }}</a-button>
        </div>
        <div id="grn-print-content" class="grn-print-content">
            <div class="grn-header">
                <h1>GRN</h1>
            </div>
            <table class="grn-info-table">
                <tr><td><strong>Document No</strong></td><td>{{ grn.invoice_number }}</td></tr>
                <tr><td><strong>GRN Date</strong></td><td>{{ formatDate(grn.order_date) }}</td></tr>
                <tr><td><strong>PO Number</strong></td><td>{{ poNumber }}</td></tr>
                <tr><td><strong>Supplier Name</strong></td><td>{{ grn.user?.name || '-' }}</td></tr>
                <tr><td><strong>Supplier Invoice Number</strong></td><td>{{ grn.supplier_invoice_number || '-' }}</td></tr>
                <tr><td><strong>Delivery Challan No</strong></td><td>{{ grn.delivery_challan_no || '-' }}</td></tr>
                <tr><td><strong>Warehouse / Store</strong></td><td>{{ grn.warehouse?.name || '-' }}</td></tr>
            </table>
            <h3>Item Details</h3>
            <table class="grn-items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Name</th>
                        <th>PO Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in (grn.items || [])" :key="item.xid || index">
                        <td>{{ index + 1 }}</td>
                        <td>{{ (item.item_code ?? item.product?.item_code) || '-' }}</td>
                        <td>{{ item.name || item.product?.name || '-' }}</td>
                        <td>{{ item.quantity }}</td>
                    </tr>
                </tbody>
            </table>
            <h3>Summary</h3>
            <table class="grn-summary-table">
                <tr><td><strong>Total PO Items</strong></td><td>{{ summaryTotalPo }}</td></tr>
                <tr><td><strong>Total Received</strong></td><td>{{ summaryTotalReceived }}</td></tr>
                <tr><td><strong>Total Short/Damaged</strong></td><td>{{ summaryTotalShort }}</td></tr>
            </table>
            <h3>Receiver Information</h3>
            <table class="grn-signature-table">
                <thead>
                    <tr>
                        <th>Received By</th>
                        <th>Checked By</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <p><strong>{{ grn.received_by_name || '-' }}</strong></p>
                            <p>Signature: _________________</p>
                            <p>Date: {{ formatDate(grn.received_by_date) }}</p>
                        </td>
                        <td>
                            <p><strong>{{ grn.checked_by_name || '-' }}</strong></p>
                            <p>Signature: _________________</p>
                            <p>Date: {{ formatDate(grn.checked_by_date) }}</p>
                        </td>
                        <td>
                            <p><strong>{{ grn.approved_by_name || '-' }}</strong></p>
                            <p>Signature: _________________</p>
                            <p>Date: {{ formatDate(grn.approved_by_date) }}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div v-else style="padding: 20px;">{{ $t('common.loading') }}...</div>
</template>
<script>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import dayjs from "dayjs";

export default {
    setup() {
        const route = useRoute();
        const router = useRouter();
        const { t } = useI18n();
        const grn = ref(null);
        const dateFormat = "YYYY-MM-DD";

        const poNumber = computed(() => (grn.value?.parent_order?.invoice_number) || "-");

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

        function doPrint() {
            window.print();
        }
        function goBack() {
            router.push({ name: "admin.stock.grn.details", params: { id: route.params.id } });
        }

        onMounted(() => {
            const id = route.params.id;
            if (!id) return;
            axiosAdmin.get(`grn/${id}`).then((res) => {
                const d = res.data || res;
                grn.value = { ...(d.order || d), items: d.items || [] };
                if (grn.value.parent_order_id && d.order && !grn.value.parent_order) {
                    grn.value.parent_order = { invoice_number: '-' };
                }
                setTimeout(() => window.print(), 400);
            });
        });

        return {
            grn,
            poNumber,
            summaryTotalPo,
            summaryTotalReceived,
            summaryTotalShort,
            formatDate,
            doPrint,
            goBack,
        };
    },
};
</script>
<style scoped>
.grn-print-page { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; }
.grn-print-content { padding: 24px; }
.grn-header h1 { font-size: 22px; margin-bottom: 20px; }
.grn-info-table, .grn-summary-table { width: 100%; margin-bottom: 24px; border-collapse: collapse; }
.grn-info-table td, .grn-summary-table td { padding: 6px 12px; border: 1px solid #ddd; }
.grn-items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
.grn-items-table th, .grn-items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.grn-items-table thead { background: #f5f5f5; }
.grn-signature-table { width: 100%; border-collapse: collapse; }
.grn-signature-table th, .grn-signature-table td { border: 1px solid #ddd; padding: 12px; vertical-align: top; width: 33%; }
.grn-signature-table thead { background: #f5f5f5; }
h3 { font-size: 14px; margin: 16px 0 8px; }
@media print {
    .no-print { display: none !important; }
    .grn-print-content { padding: 0; }
}
</style>
