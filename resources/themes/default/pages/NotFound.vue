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
            الصفحة غير موجودة
        </h2>
        <EmptyState message="تعذّر العثور على الصفحة التي تبحثين عنها" />
    </StoreLayout>
</template>
