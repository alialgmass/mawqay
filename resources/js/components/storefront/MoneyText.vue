<script setup lang="ts">
defineProps<{
    price: string;
    originalPrice?: string | null;
}>();
</script>

<template>
    <span class="inline-flex items-baseline gap-2">
        <span class="font-semibold" dir="ltr">{{ price }}</span>
        <s
            v-if="originalPrice"
            class="text-sm"
            dir="ltr"
            :style="{ color: 'var(--color-text-muted)' }"
            >{{ originalPrice }}</s
        >
    </span>
</template>
