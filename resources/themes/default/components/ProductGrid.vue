<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import EmptyState from './EmptyState.vue';
import ProductCard from './ProductCard.vue';
import type { ProductCardData } from './ProductCard.vue';

export type ProductPage = {
    data: ProductCardData[];
    prevUrl: string | null;
    nextUrl: string | null;
    currentPage: number;
    lastPage: number;
    total: number;
};

defineProps<{ products: ProductPage; emptyMessage: string }>();
</script>

<template>
    <EmptyState v-if="products.data.length === 0" :message="emptyMessage" />

    <template v-else>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
            <ProductCard
                v-for="product in products.data"
                :key="product.url"
                :product="product"
            />
        </div>

        <nav
            v-if="products.lastPage > 1"
            class="mt-6 flex items-center justify-between text-sm"
        >
            <Link v-if="products.prevUrl" :href="products.prevUrl" class="inline-flex min-h-11 items-center px-4 font-semibold transition-all duration-200 hover:shadow-md" :style="{ border: '1px solid var(--color-border)', borderRadius: 'var(--radius-sm)' }">السابق</Link>
            <span v-else />
            <span>{{ products.currentPage }} / {{ products.lastPage }}</span>
            <Link v-if="products.nextUrl" :href="products.nextUrl" class="inline-flex min-h-11 items-center px-4 font-semibold transition-all duration-200 hover:shadow-md" :style="{ border: '1px solid var(--color-border)', borderRadius: 'var(--radius-sm)' }">التالي</Link>
            <span v-else />
        </nav>
    </template>
</template>
