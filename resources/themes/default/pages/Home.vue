<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import EmptyState from '../components/EmptyState.vue';
import ProductCard from '../components/ProductCard.vue';
import type { ProductCardData } from '../components/ProductCard.vue';
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
    latest: ProductCardData[];
    categories: { name: string; url: string }[];
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
        <nav
            v-if="categories.length"
            class="mb-6 flex flex-wrap gap-2"
            aria-label="التصنيفات"
        >
            <Link
                v-for="category in categories"
                :key="category.url"
                :href="category.url"
                class="rounded-full px-4 py-2 text-sm"
                :style="{
                    backgroundColor: 'var(--color-secondary)',
                    color: 'var(--color-on-secondary)',
                }"
            >
                {{ category.name }}
            </Link>
        </nav>

        <h2 class="mb-4 text-lg font-semibold">أحدث المنتجات</h2>

        <EmptyState v-if="latest.length === 0" message="لا توجد منتجات بعد" />
        <div
            v-else
            class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4"
        >
            <ProductCard
                v-for="product in latest"
                :key="product.url"
                :product="product"
            />
        </div>
    </StoreLayout>
</template>
