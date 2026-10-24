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
        class="block overflow-hidden"
        :style="{
            border: '1px solid var(--color-border)',
            borderRadius: 'var(--radius-md)',
            backgroundColor: 'var(--color-surface)',
        }"
    >
        <div class="aspect-square w-full bg-black/5">
            <img
                v-if="product.imageUrl"
                :src="product.imageUrl"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover"
            />
        </div>
        <div class="space-y-1 p-3">
            <h3 class="truncate text-sm font-medium">{{ product.name }}</h3>
            <MoneyText
                :price="product.price"
                :original-price="product.originalPrice"
            />
        </div>
    </Link>
</template>
