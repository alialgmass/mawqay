<script setup lang="ts">
import EmptyState from '../components/EmptyState.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
    orders: { number: string; status: string; total: string }[];
}>();
</script>

<template>
    <StoreLayout :seo="seo">
        <h2
            class="mb-4 text-lg font-semibold"
            :style="{ fontFamily: 'var(--font-heading)' }"
        >
            طلباتي
        </h2>
        <EmptyState v-if="orders.length === 0" message="لا توجد طلبات بعد" />
        <ul v-else class="space-y-2">
            <li
                v-for="order in orders"
                :key="order.number"
                class="flex justify-between"
            >
                <span>{{ order.number }} — {{ order.status }}</span>
                <span dir="ltr">{{ order.total }}</span>
            </li>
        </ul>
    </StoreLayout>
</template>
