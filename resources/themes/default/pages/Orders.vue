<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import EmptyState from '../components/EmptyState.vue';
import StatusBadge from '../components/StatusBadge.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
    orders: {
        number: string;
        status: string;
        statusLabel: string;
        total: string;
        placedAt: string;
        url: string;
    }[];
}>();
</script>

<template>
    <StoreLayout :seo="seo">
        <h2
            class="mb-4 text-xl font-semibold"
            :style="{ fontFamily: 'var(--font-heading)' }"
        >
            طلباتي
        </h2>

        <EmptyState v-if="orders.length === 0" message="لا توجد طلبات بعد" />

        <ul v-else class="space-y-3">
            <li
                v-for="order in orders"
                :key="order.number"
                class="flex flex-wrap items-center justify-between gap-3 p-4 shadow-sm"
                :style="{
                    border: '1px solid var(--color-border)',
                    borderRadius: 'var(--radius-lg)',
                    backgroundColor: 'var(--color-surface)',
                }"
            >
                <div>
                    <Link :href="order.url" class="font-semibold text-[var(--color-primary)] hover:underline">{{
                        order.number
                    }}</Link>
                    <p
                        class="text-sm"
                        :style="{ color: 'var(--color-text-muted)' }"
                    >
                        {{ order.placedAt }}
                    </p>
                </div>
                <StatusBadge
                    :status="order.status"
                    :label="order.statusLabel"
                />
                <span class="font-medium" dir="ltr">{{ order.total }}</span>
            </li>
        </ul>
    </StoreLayout>
</template>
