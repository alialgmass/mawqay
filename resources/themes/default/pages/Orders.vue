<script setup lang="ts">
import EmptyState from '../components/EmptyState.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type {
    NavLink,
    Seo,
    ShellUrls,
    StoreShell,
} from '../layouts/StoreLayout.vue';

defineProps<{
    store: StoreShell;
    tokens: Record<string, string>;
    seo: Seo;
    urls: ShellUrls;
    navigation: NavLink[];
    cartCount: number;
    orders: { number: string; status: string; total: string }[];
}>();
</script>

<template>
    <StoreLayout
        :store="store"
        :tokens="tokens"
        :seo="seo"
        :urls="urls"
        :navigation="navigation"
        :cart-count="cartCount"
    >
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
