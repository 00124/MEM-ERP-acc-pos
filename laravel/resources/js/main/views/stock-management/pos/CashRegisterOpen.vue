<template>
    <a-modal
        :open="visible"
        :closable="false"
        :maskClosable="false"
        :keyboard="false"
        :footer="null"
        width="440px"
        centered
    >
        <div style="text-align: center; padding: 8px 0 4px;">
            <div style="font-size: 40px; margin-bottom: 8px;">🏪</div>
            <div style="font-size: 20px; font-weight: 700; color: #1d1d1d; margin-bottom: 4px;">
                Open Cash Register
            </div>
            <div style="font-size: 13px; color: #666; margin-bottom: 24px;">
                Enter the cash in hand to start today's POS session
            </div>
        </div>

        <a-form layout="vertical">
            <a-form-item label="Opening Balance (Cash in Hand)">
                <a-input-number
                    v-model:value="openingBalance"
                    :min="0"
                    :precision="2"
                    :step="100"
                    size="large"
                    style="width: 100%; font-size: 18px;"
                    placeholder="0.00"
                    @pressEnter="handleOpen"
                />
            </a-form-item>
            <a-form-item label="Notes (optional)">
                <a-input
                    v-model:value="notes"
                    placeholder="e.g. Float from previous day"
                    allow-clear
                />
            </a-form-item>
            <a-alert
                v-if="errorMsg"
                type="error"
                :message="errorMsg"
                show-icon
                style="margin-bottom: 12px;"
            />
            <a-button
                type="primary"
                size="large"
                block
                :loading="loading"
                style="height: 48px; font-size: 16px; font-weight: 600;"
                @click="handleOpen"
            >
                ✅ Open Register &amp; Start Day
            </a-button>
            <div style="margin-top: 10px; text-align: center; color: #aaa; font-size: 12px;">
                {{ todayLabel }}
            </div>
        </a-form>
    </a-modal>
</template>

<script>
import { ref, computed } from "vue";
export default {
    props: {
        visible: { type: Boolean, default: false },
    },
    emits: ["opened"],
    setup(props, { emit }) {
        const openingBalance = ref(0);
        const notes          = ref('');
        const loading        = ref(false);
        const errorMsg       = ref('');

        const todayLabel = computed(() => {
            return new Date().toLocaleDateString('en-PK', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
        });

        const handleOpen = () => {
            errorMsg.value = '';
            loading.value  = true;
            axiosAdmin.post('cash-register/open', {
                opening_balance: openingBalance.value || 0,
                notes: notes.value || '',
            }).then(res => {
                const reg = res.data.register;
                emit('opened', reg);
            }).catch(() => {
                errorMsg.value = 'Failed to open register. Please try again.';
            }).finally(() => {
                loading.value = false;
            });
        };

        return { openingBalance, notes, loading, errorMsg, todayLabel, handleOpen };
    },
};
</script>
