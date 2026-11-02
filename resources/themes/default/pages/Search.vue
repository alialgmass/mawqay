<script setup lang="ts">
import ProductGrid from '../components/ProductGrid.vue';
import type { ProductPage } from '../components/ProductGrid.vue';
import StorefrontBreadcrumbs from '../components/StorefrontBreadcrumbs.vue';
import type { Crumb } from '../components/StorefrontBreadcrumbs.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
    query: string;
    products: ProductPage;
    breadcrumbs: Crumb[];
}>();
</script>

<template>
    <StoreLayout :seo="seo" :query="query">
        <StorefrontBreadcrumbs :items="breadcrumbs" />

        <h2 v-if="query" class="mb-4 text-xl font-semibold" :style="{ fontFamily: 'var(--font-heading)' }">
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
