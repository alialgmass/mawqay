<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import MoneyText from './MoneyText.vue';

export type ProductCardData = {
    name: string;
    url: string;
    price: string;
    originalPrice: string | null;
    imageUrl: string | null;
};

defineProps<{ product: ProductCardData }>();
</script>

<template>
    <Link
        :href="product.url"
        class="group block overflow-hidden shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"
        :style="{
            border: '1px solid var(--color-border)',
            borderRadius: 'var(--radius-lg)',
            backgroundColor: 'var(--color-bg)',
        }"
    >
        <div class="relative aspect-square w-full overflow-hidden bg-[var(--color-surface)]">
            <span
                v-if="product.originalPrice"
                class="absolute start-2 top-2 z-10 px-2 py-0.5 text-xs font-bold"
                :style="{ backgroundColor: 'var(--color-danger)', color: '#fff', borderRadius: '9999px' }"
                >تخفيض</span
            >
            <img
                v-if="product.imageUrl"
                :src="product.imageUrl"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div v-else class="flex h-full w-full items-center justify-center text-3xl opacity-40" aria-hidden="true">🛍️</div>
        </div>
        <div class="space-y-1 p-3">
            <h3 class="truncate text-sm font-semibold">{{ product.name }}</h3>
            <MoneyText
                :price="product.price"
                :original-price="product.originalPrice"
            />
        </div>
    </Link>
</template>
