<template>
    <a-modal
        :open="visible"
        :closable="false"
        :maskClosable="false"
        :keyboard="false"
        :footer="null"
        width="420px"
        centered
    >
        <!-- ══ STORE HEADER ══════════════════════════════════════════ -->
        <div class="cro-header">
            <div class="cro-store-name">{{ appSetting?.name || 'MA Electronics' }}</div>
            <div class="cro-sub">Point of Sale — Shift Opening</div>
        </div>

        <!-- ══ DATE / TIME BANNER ════════════════════════════════════ -->
        <div class="cro-datetime-box">
            <div class="cro-time">{{ liveTime }}</div>
            <div class="cro-date">{{ liveDate }}</div>
        </div>

        <!-- ══ CASHIER & BRANCH INFO ═════════════════════════════════ -->
        <div class="cro-info-row">
            <div class="cro-info-item">
                <span class="cro-info-icon">👤</span>
                <div>
                    <div class="cro-info-label">Cashier</div>
                    <div class="cro-info-value">{{ user?.name || '—' }}</div>
                </div>
            </div>
            <div class="cro-info-divider"></div>
            <div class="cro-info-item">
                <span class="cro-info-icon">🏬</span>
                <div>
                    <div class="cro-info-label">Branch</div>
                    <div class="cro-info-value">{{ warehouse?.name || '—' }}</div>
                    <div v-if="warehouse?.address" style="font-size:10px; color:#888; margin-top:1px;">{{ warehouse.address }}</div>
                    <div v-if="warehouse?.phone" style="font-size:10px; color:#888;">📞 {{ warehouse.phone }}</div>
                </div>
            </div>
        </div>

        <!-- ══ OPENING BALANCE INPUT ══════════════════════════════════ -->
        <div style="margin-bottom:16px;">
            <div class="cro-field-label">💵 Opening Cash in Drawer</div>
            <a-input-number
                v-model:value="openingBalance"
                :min="0"
                :precision="0"
                :step="500"
                size="large"
                style="width:100%; font-size:22px; font-weight:700; text-align:right;"
                :placeholder="'0'"
                @pressEnter="handleOpen"
            >
                <template #prefix>
                    <span style="color:#999; font-size:14px;">Rs.</span>
                </template>
            </a-input-number>

            <!-- Quick preset buttons -->
            <div class="cro-presets">
                <span class="cro-preset-label">Quick set:</span>
                <a-button
                    v-for="amt in [0, 5000, 10000, 20000, 50000]"
                    :key="amt"
                    size="small"
                    :type="openingBalance === amt ? 'primary' : 'default'"
                    style="border-radius:12px; font-size:11px;"
                    @click="openingBalance = amt"
                >
                    {{ amt === 0 ? 'Rs. 0' : 'Rs. ' + amt.toLocaleString() }}
                </a-button>
            </div>
        </div>

        <!-- ══ NOTES ══════════════════════════════════════════════════ -->
        <div style="margin-bottom:18px;">
            <div class="cro-field-label">📝 Notes <span style="font-weight:400; color:#bbb;">(optional)</span></div>
            <a-input
                v-model:value="notes"
                placeholder="e.g. Float carried over from yesterday"
                allow-clear
            />
        </div>

        <a-alert v-if="errorMsg" type="error" :message="errorMsg" show-icon style="margin-bottom:12px;" />

        <!-- ══ OPEN BUTTON ════════════════════════════════════════════ -->
        <a-button
            type="primary"
            size="large"
            block
            :loading="loading"
            class="cro-open-btn"
            @click="handleOpen"
        >
            ✅ &nbsp; Open Register — Start Shift
        </a-button>
    </a-modal>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useStore } from "vuex";

export default {
    props: { visible: { type: Boolean, default: false } },
    emits: ["opened"],
    setup(props, { emit }) {
        const store          = useStore();
        const openingBalance = ref(0);
        const notes          = ref('');
        const loading        = ref(false);
        const errorMsg       = ref('');

        const user        = computed(() => store.state.auth.user);
        const warehouse   = computed(() => store.state.auth.warehouse);
        const appSetting  = computed(() => store.state.auth.appSetting);

        // Live clock
        const now      = ref(new Date());
        let clockTimer = null;
        onMounted(() => {
            clockTimer = setInterval(() => { now.value = new Date(); }, 1000);
        });
        onUnmounted(() => clearInterval(clockTimer));

        const liveTime = computed(() =>
            now.value.toLocaleTimeString('en-PK', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true })
        );
        const liveDate = computed(() =>
            now.value.toLocaleDateString('en-PK', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
        );

        const handleOpen = () => {
            errorMsg.value = '';
            loading.value  = true;
            axiosAdmin.post('cash-register/open', {
                opening_balance: openingBalance.value || 0,
                notes: notes.value || '',
            }).then(res => {
                emit('opened', res.data.register);
            }).catch(() => {
                errorMsg.value = 'Could not open register. Please try again.';
            }).finally(() => {
                loading.value = false;
            });
        };

        return { openingBalance, notes, loading, errorMsg, user, warehouse, appSetting, liveTime, liveDate, handleOpen };
    },
};
</script>

<style scoped>
/* ── Header ─────────────────────────────────────────────── */
.cro-header {
    text-align: center;
    padding: 8px 0 14px;
    border-bottom: 2px solid #1677ff;
    margin-bottom: 16px;
}
.cro-store-name {
    font-size: 20px;
    font-weight: 800;
    color: #1677ff;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.cro-sub {
    font-size: 11px;
    color: #888;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-top: 2px;
}

/* ── Date/Time Banner ───────────────────────────────────── */
.cro-datetime-box {
    background: linear-gradient(135deg, #001d66 0%, #003eb3 100%);
    border-radius: 10px;
    padding: 14px 18px;
    text-align: center;
    margin-bottom: 14px;
}
.cro-time {
    font-size: 32px;
    font-weight: 800;
    color: #fff;
    letter-spacing: 2px;
    font-variant-numeric: tabular-nums;
    font-family: 'Courier New', monospace;
}
.cro-date {
    font-size: 12px;
    color: #91caff;
    margin-top: 3px;
    letter-spacing: 0.5px;
}

/* ── Cashier / Branch Row ───────────────────────────────── */
.cro-info-row {
    display: flex;
    align-items: center;
    background: #f6f9ff;
    border: 1px solid #d6e4ff;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 16px;
    gap: 10px;
}
.cro-info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}
.cro-info-divider {
    width: 1px;
    height: 32px;
    background: #d6e4ff;
}
.cro-info-icon {
    font-size: 20px;
    line-height: 1;
}
.cro-info-label {
    font-size: 10px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.cro-info-value {
    font-size: 13px;
    font-weight: 700;
    color: #1d1d1d;
}

/* ── Field Label ─────────────────────────────────────────── */
.cro-field-label {
    font-size: 12px;
    font-weight: 700;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 6px;
}

/* ── Preset Buttons Row ─────────────────────────────────── */
.cro-presets {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.cro-preset-label {
    font-size: 10px;
    color: #bbb;
    white-space: nowrap;
}

/* ── Open Button ─────────────────────────────────────────── */
.cro-open-btn {
    height: 50px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.3px;
    border-radius: 8px;
}
</style>
