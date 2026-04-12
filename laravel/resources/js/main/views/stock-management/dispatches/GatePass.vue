<template>
    <div class="gate-pass-wrapper">
        <div class="no-print" style="padding: 16px; border-bottom: 1px solid #eee; display: flex; gap: 8px">
            <a-button @click="$router.go(-1)">← Back</a-button>
            <a-button type="primary" @click="printPass">
                <PrinterOutlined /> Print Gate Pass
            </a-button>
        </div>

        <div v-if="loading" style="padding: 60px; text-align: center">
            <a-spin size="large" />
        </div>

        <div v-else-if="dispatch" id="gate-pass-content" class="gate-pass-print">
            <!-- Company Header -->
            <div class="gp-header">
                <div class="gp-company-name">{{ companyName }}</div>
                <h2 class="gp-title">GATE PASS</h2>
                <div class="gp-doc-no">Dispatch No: <strong>{{ dispatch.dispatch_number }}</strong></div>
            </div>

            <!-- Info Grid -->
            <table class="gp-info-table">
                <tr>
                    <td><strong>Dispatch Date</strong></td>
                    <td>{{ formatDate(dispatch.dispatch_date) }}</td>
                    <td><strong>Sale Invoice</strong></td>
                    <td>{{ dispatch.sale?.invoice_number || '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Customer</strong></td>
                    <td>{{ dispatch.customer?.name || '-' }}</td>
                    <td><strong>Salesman</strong></td>
                    <td>{{ dispatch.sale?.staff_member?.name || '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Warehouse / Store</strong></td>
                    <td>{{ dispatch.warehouse?.name || '-' }}</td>
                    <td><strong>Status</strong></td>
                    <td><strong>{{ dispatch.status?.toUpperCase() }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Driver</strong></td>
                    <td>{{ dispatch.driver_name || '-' }}</td>
                    <td><strong>Vehicle No</strong></td>
                    <td>{{ dispatch.vehicle_no || '-' }}</td>
                </tr>
                <tr v-if="dispatch.remarks">
                    <td><strong>Remarks</strong></td>
                    <td colspan="3">{{ dispatch.remarks }}</td>
                </tr>
            </table>

            <!-- Items -->
            <h3 class="gp-section-title">Item Details</h3>
            <table class="gp-items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Product Name</th>
                        <th>Warehouse / Store</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in dispatch.items" :key="item.xid || idx">
                        <td>{{ idx + 1 }}</td>
                        <td>{{ item.product?.item_code || '-' }}</td>
                        <td>{{ item.product?.name || '-' }}</td>
                        <td>{{ item.warehouse?.name || dispatch.warehouse?.name }}</td>
                        <td>{{ item.quantity }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Signature Section -->
            <table class="gp-sig-table">
                <thead>
                    <tr>
                        <th>Prepared By</th>
                        <th>Checked By</th>
                        <th>Security Guard</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="sig-line">Signature: _____________</div>
                            <div class="sig-line">Date: _________________</div>
                        </td>
                        <td>
                            <div class="sig-line">Signature: _____________</div>
                            <div class="sig-line">Date: _________________</div>
                        </td>
                        <td>
                            <div class="sig-line">Signature: _____________</div>
                            <div class="sig-line">Date: _________________</div>
                        </td>
                        <td>
                            <div class="sig-line">Signature: _____________</div>
                            <div class="sig-line">Date: _________________</div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="gp-footer">
                This is a computer generated document. Authorized signature required.
            </div>
        </div>

        <div v-else style="padding: 40px; text-align: center; color: red">
            Dispatch not found.
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useStore } from "vuex";
import { PrinterOutlined } from "@ant-design/icons-vue";
import dayjs from "dayjs";

export default defineComponent({
    name: "DispatchGatePass",
    components: { PrinterOutlined },
    setup() {
        const route      = useRoute();
        const store      = useStore();
        const dispatch   = ref(null);
        const loading    = ref(true);
        const appSetting = computed(() => store.state.auth.appSetting);
        const companyName = computed(() => store.state.auth.user?.company?.name || "MA Electronics");

        const formatDate = (d) =>
            d ? dayjs(d).format(appSetting.value?.date_format || "DD-MM-YYYY") : "-";

        const printPass = () => window.print();

        onMounted(async () => {
            try {
                const res  = await window.axiosAdmin.get(`dispatches/${route.params.id}/gate-pass`);
                dispatch.value = res.data.data;
            } catch {
                dispatch.value = null;
            } finally {
                loading.value = false;
            }
        });

        return { dispatch, loading, appSetting, companyName, formatDate, printPass };
    },
});
</script>

<style scoped>
.gate-pass-wrapper { background: #f0f2f5; min-height: 100vh; }
.gate-pass-print {
    width: 210mm;
    min-height: 148mm;
    margin: 20px auto;
    background: #fff;
    padding: 20mm 15mm;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    font-family: Arial, sans-serif;
    font-size: 12px;
}
.gp-header { text-align: center; margin-bottom: 16px; }
.gp-company-name { font-size: 20px; font-weight: bold; }
.gp-title { font-size: 16px; letter-spacing: 4px; margin: 4px 0; }
.gp-doc-no { font-size: 13px; }
.gp-info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.gp-info-table td { padding: 5px 8px; border: 1px solid #ccc; }
.gp-section-title { font-size: 13px; font-weight: bold; margin: 16px 0 6px; }
.gp-items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.gp-items-table th,
.gp-items-table td { border: 1px solid #333; padding: 5px 8px; text-align: left; }
.gp-items-table th { background: #f5f5f5; font-weight: bold; }
.gp-sig-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.gp-sig-table th,
.gp-sig-table td { border: 1px solid #ccc; padding: 8px; text-align: center; width: 25%; }
.sig-line { margin: 10px 0; font-size: 11px; }
.gp-footer { text-align: center; font-size: 10px; color: #888; margin-top: 12px; }
@media print {
    .no-print { display: none !important; }
    .gate-pass-wrapper { background: #fff; }
    .gate-pass-print { box-shadow: none; margin: 0; padding: 10mm; }
}
</style>
