<script setup lang="ts">
import ProductGrid from '../components/ProductGrid.vue';
import type { ProductPage } from '../components/ProductGrid.vue';
import StorefrontBreadcrumbs from '../components/StorefrontBreadcrumbs.vue';
import type { Crumb } from '../components/StorefrontBreadcrumbs.vue';
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
    query: string;
    products: ProductPage;
    breadcrumbs: Crumb[];
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
        :query="query"
    >
        <StorefrontBreadcrumbs :items="breadcrumbs" />

        <h2 v-if="query" class="mb-4 text-lg font-semibold">
            نتائج البحث عن «{{ query }}»
        </h2>

        <ProductGrid
            :products="products"
            :empty-message="
                query ? 'لا توجد نتائج مطابقة' : 'اكتب اسم المنتج للبحث'
            "
        />
    </StoreLayout>
</template>
