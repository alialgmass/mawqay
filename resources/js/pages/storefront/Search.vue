<script setup lang="ts">
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import type { ProductPage } from '@/components/storefront/ProductGrid.vue';
import StorefrontBreadcrumbs from '@/components/storefront/StorefrontBreadcrumbs.vue';
import type { Crumb } from '@/components/storefront/StorefrontBreadcrumbs.vue';
import StoreLayout from '@/components/storefront/StoreLayout.vue';
import type { Seo, StoreShell } from '@/components/storefront/StoreLayout.vue';

defineProps<{
    store: StoreShell;
    tokens: Record<string, string>;
    seo: Seo;
    query: string;
    products: ProductPage;
    breadcrumbs: Crumb[];
}>();
</script>

<template>
    <StoreLayout :store="store" :tokens="tokens" :seo="seo" :query="query">
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
