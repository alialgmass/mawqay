<script setup lang="ts">
import { computed, ref } from 'vue';
import MoneyText from '../components/MoneyText.vue';
import StorefrontBreadcrumbs from '../components/StorefrontBreadcrumbs.vue';
import type { Crumb } from '../components/StorefrontBreadcrumbs.vue';
import VariantPicker from '../components/VariantPicker.vue';
import type { VariantData } from '../components/VariantPicker.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type {
    NavLink,
    Seo,
    ShellUrls,
    StoreShell,
} from '../layouts/StoreLayout.vue';

const props = defineProps<{
    store: StoreShell;
    tokens: Record<string, string>;
    seo: Seo;
    urls: ShellUrls;
    navigation: NavLink[];
    cartCount: number;
    breadcrumbs: Crumb[];
    product: {
        name: string;
        description: string;
        price: string;
        originalPrice: string | null;
        images: { thumb: string; large: string }[];
        variants: VariantData[];
    };
}>();

const activeImage = ref(0);
const chosen = ref<VariantData | null>(null);
const image = computed(() => props.product.images[activeImage.value] ?? null);
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
        <StorefrontBreadcrumbs :items="breadcrumbs" />

        <div class="grid gap-6 md:grid-cols-2">
            <section aria-label="الصور">
                <div
                    class="aspect-square w-full overflow-hidden bg-black/5"
                    :style="{ borderRadius: 'var(--radius-md)' }"
                >
                    <img
                        v-if="image"
                        :src="image.large"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                    />
                </div>
                <ul v-if="product.images.length > 1" class="mt-3 flex gap-2">
                    <li v-for="(img, index) in product.images" :key="index">
                        <button
                            type="button"
                            class="h-16 w-16 overflow-hidden rounded-md"
                            :style="{
                                border: `2px solid ${activeImage === index ? 'var(--color-primary)' : 'transparent'}`,
                            }"
                            @click="activeImage = index"
                        >
                            <img
                                :src="img.thumb"
                                :alt="`${product.name} ${index + 1}`"
                                class="h-full w-full object-cover"
                            />
                        </button>
                    </li>
                </ul>
            </section>

            <section class="space-y-5">
                <h2
                    class="text-2xl font-semibold"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                >
                    {{ product.name }}
                </h2>
                <MoneyText
                    :price="chosen?.price ?? product.price"
                    :original-price="chosen ? null : product.originalPrice"
                />
                <VariantPicker
                    :variants="product.variants"
                    @select="chosen = $event"
                />
                <p
                    v-if="product.description"
                    class="text-sm leading-7 whitespace-pre-line"
                >
                    {{ product.description }}
                </p>
            </section>
        </div>
    </StoreLayout>
</template>
