<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status: string; label: string }>();

/** System colours: pending gray, review yellow, paid/processing blue, shipped purple, delivered/completed green, cancelled/refunded red. */
const colours = computed(() => {
    switch (props.status) {
        case 'payment_review':
            return { bg: '#FEF3C7', fg: '#92400E' };
        case 'paid':
        case 'processing':
            return { bg: '#DBEAFE', fg: '#1E40AF' };
        case 'shipped':
            return { bg: '#EDE9FE', fg: '#5B21B6' };
        case 'delivered':
        case 'completed':
            return { bg: '#DCFCE7', fg: '#166534' };
        case 'cancelled':
        case 'refunded':
            return { bg: '#FEE2E2', fg: '#991B1B' };
        default:
            return { bg: '#E5E7EB', fg: '#374151' };
    }
});
</script>

<template>
    <span
        class="inline-block px-3 py-1 text-xs font-semibold"
        :data-status="status"
        :style="{
            backgroundColor: colours.bg,
            color: colours.fg,
            borderRadius: '9999px',
        }"
    >
        {{ label }}
    </span>
</template>
