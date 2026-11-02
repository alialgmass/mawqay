<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import EmptyState from '../components/EmptyState.vue';
import ProductCard from '../components/ProductCard.vue';
import type { ProductCardData } from '../components/ProductCard.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
    latest: ProductCardData[];
    categories: { name: string; url: string }[];
}>();

const shell = useShell();

const initial = (name: string) => name.trim().charAt(0);
</script>

<template>
    <StoreLayout :seo="seo">
        <section
            class="relative mb-10 grid items-center gap-6 overflow-hidden px-6 py-8 sm:px-10 sm:py-12 md:grid-cols-2"
            :style="{
                borderRadius: 'var(--radius-lg)',
                backgroundColor: 'var(--color-primary)',
                backgroundImage:
                    'radial-gradient(circle at 10% 15%, rgba(255,255,255,0.25), transparent 45%), radial-gradient(circle at 95% 95%, rgba(255,255,255,0.15), transparent 40%)',
                color: 'var(--color-on-primary)',
            }"
        >
            <div class="relative space-y-4">
                <span
                    class="inline-block px-3 py-1 text-xs font-bold"
                    :style="{
                        backgroundColor: 'rgba(255,255,255,0.2)',
                        borderRadius: '9999px',
                    }"
                >
                    وصل حديثًا
                </span>
                <h1
                    class="text-3xl font-bold leading-tight sm:text-4xl"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                >
                    {{ shell.store.name }}
                </h1>
                <p class="max-w-md text-base opacity-90">
                    {{
                        shell.store.footerText ||
                        'اكتشفي أحدث المنتجات واطلبيها بسهولة.'
                    }}
                </p>
                <div class="flex flex-wrap gap-3 pt-1">
                    <Link
                        :href="shell.urls.search"
                        class="inline-flex min-h-11 items-center px-6 text-sm font-semibold transition-all duration-200 hover:shadow-lg"
                        :style="{
                            backgroundColor: 'var(--color-bg)',
                            color: 'var(--color-primary)',
                            borderRadius: 'var(--radius-sm)',
                        }"
                    >
                        تسوقي الآن
                    </Link>
                    <Link
                        v-if="latest[0]"
                        :href="latest[0].url"
                        class="inline-flex min-h-11 items-center px-6 text-sm font-semibold transition-all duration-200 hover:bg-white/10"
                        :style="{
                            border: '1.5px solid currentColor',
                            borderRadius: 'var(--radius-sm)',
                        }"
                    >
                        شاهدي الجديد
                    </Link>
                </div>
            </div>

            <Link
                v-if="latest[0]?.imageUrl"
                :href="latest[0].url"
                class="relative mx-auto hidden aspect-square w-full max-w-xs overflow-hidden shadow-xl transition-transform duration-300 hover:scale-[1.02] md:block"
                :style="{ borderRadius: 'var(--radius-lg)' }"
                :aria-label="latest[0].name"
            >
                <img
                    :src="latest[0].imageUrl"
                    :alt="latest[0].name"
                    class="h-full w-full object-cover"
                />
            </Link>
        </section>

        <section v-if="categories.length" class="mb-10">
            <h2
                class="mb-4 text-xl font-bold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                تسوقي حسب التصنيف
            </h2>
            <nav
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                aria-label="التصنيفات"
            >
                <Link
                    v-for="category in categories"
                    :key="category.url"
                    :href="category.url"
                    class="flex items-center gap-3 p-3 text-sm font-semibold shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"
                    :style="{
                        border: '1px solid var(--color-border)',
                        borderRadius: 'var(--radius-lg)',
                        backgroundColor: 'var(--color-bg)',
                    }"
                >
                    <span
                        aria-hidden="true"
                        class="flex h-11 w-11 shrink-0 items-center justify-center text-lg font-bold"
                        :style="{
                            backgroundColor: 'var(--color-secondary)',
                            color: 'var(--color-primary)',
                            borderRadius: '9999px',
                            fontFamily: 'var(--font-heading)',
                        }"
                        >{{ initial(category.name) }}</span
                    >
                    <span class="truncate">{{ category.name }}</span>
                </Link>
            </nav>
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2
                    class="text-xl font-bold"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                >
                    أحدث المنتجات
                </h2>
                <Link
                    :href="shell.urls.search"
                    class="inline-flex min-h-11 items-center text-sm font-semibold text-[var(--color-primary)] hover:underline"
                >
                    عرض الكل
                </Link>
            </div>

            <EmptyState
                v-if="latest.length === 0"
                message="لا توجد منتجات بعد"
            />
            <div
                v-else
                class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4"
            >
                <ProductCard
                    v-for="product in latest"
                    :key="product.url"
                    :product="product"
                />
            </div>
        </section>
    </StoreLayout>
</template>
