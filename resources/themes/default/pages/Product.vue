<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MoneyText from '../components/MoneyText.vue';
import StorefrontBreadcrumbs from '../components/StorefrontBreadcrumbs.vue';
import type { Crumb } from '../components/StorefrontBreadcrumbs.vue';
import VariantPicker from '../components/VariantPicker.vue';
import type { VariantData } from '../components/VariantPicker.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

const props = defineProps<{
    seo: Seo;
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

const shell = useShell();
const page = usePage();
const errors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);
const cart = useForm({ variant_id: 0, quantity: 1 });

function addToCart() {
    if (chosen.value === null) {
        return;
    }

    cart.variant_id = chosen.value.id;
    cart.post(shell.value.actions.cartItems, { preserveScroll: true });
}

const activeImage = ref(0);
const chosen = ref<VariantData | null>(null);
const image = computed(() => props.product.images[activeImage.value] ?? null);
</script>

<template>
    <StoreLayout :seo="seo">
        <StorefrontBreadcrumbs :items="breadcrumbs" />

        <div class="grid gap-6 md:grid-cols-2">
            <section aria-label="الصور">
                <div
                    class="aspect-square w-full overflow-hidden bg-[var(--color-surface)]"
                    :style="{ borderRadius: 'var(--radius-lg)' }"
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
                            class="h-16 w-16 overflow-hidden rounded-[var(--radius-sm)] transition-all duration-200 hover:shadow-md"
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

            <section class="space-y-5 md:sticky md:top-4 md:self-start">
                <h2
                    class="text-2xl font-bold sm:text-3xl"
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
                <form class="space-y-3" @submit.prevent="addToCart">
                    <div class="flex items-stretch gap-3">
                        <div
                            class="flex items-center"
                            role="group"
                            aria-label="الكمية"
                            :style="{
                                border: '1px solid var(--color-border)',
                                borderRadius: 'var(--radius-sm)',
                            }"
                        >
                            <button
                                type="button"
                                class="h-11 w-11 text-lg"
                                aria-label="تقليل الكمية"
                                @click="cart.quantity = Math.max(1, cart.quantity - 1)"
                            >
                                −
                            </button>
                            <input
                                v-model.number="cart.quantity"
                                type="number"
                                min="1"
                                aria-label="الكمية"
                                class="h-11 w-12 bg-transparent text-center text-sm font-semibold"
                            />
                            <button
                                type="button"
                                class="h-11 w-11 text-lg"
                                aria-label="زيادة الكمية"
                                @click="cart.quantity += 1"
                            >
                                +
                            </button>
                        </div>
                        <button
                            type="submit"
                            class="min-h-11 flex-1 px-6 text-sm font-semibold transition-all duration-200 hover:shadow-lg disabled:opacity-50 disabled:hover:shadow-none"
                            :disabled="chosen === null || cart.processing"
                            :style="{
                                backgroundColor: 'var(--color-primary)',
                                color: 'var(--color-on-primary)',
                                borderRadius: 'var(--radius-sm)',
                            }"
                        >
                            أضيفي إلى السلة
                        </button>
                    </div>
                    <p
                        v-if="chosen === null"
                        class="text-xs"
                        :style="{ color: 'var(--color-text-muted)' }"
                    >
                        اختاري المقاس واللون أولًا.
                    </p>
                    <p
                        v-for="error in [errors.quantity, errors.variant]"
                        v-show="error"
                        :key="String(error)"
                        role="alert"
                        class="text-sm"
                        :style="{ color: 'var(--color-danger)' }"
                    >
                        {{ error }}
                    </p>
                </form>
                <p
                    v-if="product.description"
                    class="pt-4 text-sm leading-7 whitespace-pre-line"
                    :style="{ borderTop: '1px solid var(--color-border)', color: 'var(--color-text-muted)' }"
                >
                    {{ product.description }}
                </p>
            </section>
        </div>
    </StoreLayout>
</template>
