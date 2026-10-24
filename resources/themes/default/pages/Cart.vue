<script setup lang="ts">
import EmptyState from '../components/EmptyState.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
    items: { name: string; quantity: number; price: string }[];
    total: string | null;
}>();
</script>

<template>
    <StoreLayout :seo="seo">
        <h2
            class="mb-4 text-lg font-semibold"
            :style="{ fontFamily: 'var(--font-heading)' }"
        >
            سلة التسوق
        </h2>
        <EmptyState v-if="items.length === 0" message="سلتك فارغة" />
        <ul v-else class="space-y-2">
            <li
                v-for="item in items"
                :key="item.name"
                class="flex justify-between"
            >
                <span>{{ item.name }} × {{ item.quantity }}</span>
                <span dir="ltr">{{ item.price }}</span>
            </li>
            <li v-if="total" class="flex justify-between font-semibold">
                <span>الإجمالي</span>
                <span dir="ltr">{{ total }}</span>
            </li>
        </ul>
    </StoreLayout>
</template>
