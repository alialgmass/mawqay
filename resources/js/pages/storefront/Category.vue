<script setup lang="ts">
import { router } from '@inertiajs/vue3';
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
    category: { name: string; slug: string };
    products: ProductPage;
    sort: string;
    breadcrumbs: Crumb[];
}>();

function sortBy(event: Event) {
    router.get(
        window.location.pathname,
        { sort: (event.target as HTMLSelectElement).value },
        { preserveScroll: true },
    );
}
</script>

<template>
    <StoreLayout :store="store" :tokens="tokens" :seo="seo">
        <StorefrontBreadcrumbs :items="breadcrumbs" />

        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">{{ category.name }}</h2>
            <label class="flex items-center gap-2 text-sm">
                <span>الترتيب</span>
                <select
                    :value="sort"
                    class="rounded-md px-2 py-1"
                    :style="{ border: '1px solid var(--color-border)' }"
                    @change="sortBy"
                >
                    <option value="newest">الأحدث</option>
                    <option value="price_asc">السعر: الأقل أولًا</option>
                    <option value="price_desc">السعر: الأعلى أولًا</option>
                </select>
            </label>
        </div>

        <ProductGrid
            :products="products"
            empty-message="لا توجد منتجات في هذا التصنيف"
        />
    </StoreLayout>
</template>
