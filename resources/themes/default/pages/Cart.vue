<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import EmptyState from '../components/EmptyState.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

type CartLine = {
    id: number;
    name: string;
    size: string;
    color: string;
    imageUrl: string | null;
    productUrl: string;
    quantity: number;
    available: number;
    unitPrice: string;
    lineTotal: string;
    unavailable: boolean;
    priceChanged: boolean;
    previousPrice: string | null;
};

defineProps<{
    seo: Seo;
    items: CartLine[];
    total: string;
    canCheckout: boolean;
    hasChanges: boolean;
    storeOpen: boolean;
}>();

const shell = useShell();

function setQuantity(line: CartLine, event: Event) {
    const quantity = Number((event.target as HTMLInputElement).value);

    if (quantity >= 1) {
        router.put(
            `${shell.value.actions.cartItems}/${line.id}`,
            { quantity },
            { preserveScroll: true },
        );
    }
}

function remove(line: CartLine) {
    router.delete(`${shell.value.actions.cartItems}/${line.id}`, {
        preserveScroll: true,
    });
}

function acceptPrices() {
    router.post(shell.value.actions.acceptPrices, {}, { preserveScroll: true });
}
</script>

<template>
    <StoreLayout :seo="seo">
        <h2
            class="mb-4 text-xl font-semibold"
            :style="{ fontFamily: 'var(--font-heading)' }"
        >
            سلة التسوق
        </h2>

        <p
            v-if="!storeOpen"
            role="alert"
            class="mb-4 p-3 text-sm"
            :style="{ backgroundColor: '#FEF3C7', color: '#92400E' }"
        >
            هذا المتجر متوقف مؤقتًا عن استقبال الطلبات.
        </p>

        <EmptyState v-if="items.length === 0" message="سلتك فارغة" />

        <template v-else>
            <ul class="space-y-3">
                <li
                    v-for="line in items"
                    :key="line.id"
                    class="flex flex-wrap items-center gap-3 p-3"
                    :style="{
                        border: '1px solid var(--color-border)',
                        borderRadius: 'var(--radius-md)',
                        backgroundColor: 'var(--color-surface)',
                    }"
                >
                    <div class="h-16 w-16 shrink-0 bg-black/5">
                        <img
                            v-if="line.imageUrl"
                            :src="line.imageUrl"
                            :alt="line.name"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div class="min-w-0 flex-1 space-y-1">
                        <Link :href="line.productUrl" class="font-medium">{{
                            line.name
                        }}</Link>
                        <p class="text-sm">
                            {{ line.size }} / {{ line.color }}
                        </p>
                        <p
                            v-if="line.unavailable"
                            role="alert"
                            class="text-sm"
                            :style="{ color: 'var(--color-danger)' }"
                        >
                            المتاح الآن {{ line.available }} فقط — عدّلي الكمية
                            أو احذفي المنتج.
                        </p>
                        <p
                            v-if="line.priceChanged"
                            role="alert"
                            class="text-sm"
                            :style="{ color: 'var(--color-warning)' }"
                        >
                            تغيّر السعر من
                            <s dir="ltr">{{ line.previousPrice }}</s>
                            إلى <span dir="ltr">{{ line.unitPrice }}</span>
                        </p>
                    </div>
                    <label class="text-sm">
                        <span class="sr-only">الكمية</span>
                        <input
                            type="number"
                            min="1"
                            :value="line.quantity"
                            class="min-h-11 w-20 px-2"
                            :style="{
                                border: '1px solid var(--color-border)',
                                borderRadius: 'var(--radius-md)',
                            }"
                            @change="setQuantity(line, $event)"
                        />
                    </label>
                    <span class="w-28 text-end font-medium" dir="ltr">{{
                        line.lineTotal
                    }}</span>
                    <button
                        type="button"
                        class="min-h-11 px-3 text-sm underline"
                        :aria-label="`حذف ${line.name}`"
                        @click="remove(line)"
                    >
                        حذف
                    </button>
                </li>
            </ul>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <p class="text-lg font-semibold">
                    المجموع: <span dir="ltr">{{ total }}</span>
                </p>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-if="hasChanges"
                        type="button"
                        class="min-h-11 px-4 text-sm underline"
                        @click="acceptPrices"
                    >
                        اعتماد الأسعار الجديدة
                    </button>
                    <Link
                        v-if="canCheckout"
                        :href="shell.urls.checkout"
                        class="inline-flex min-h-11 items-center px-5 text-sm font-medium"
                        :style="{
                            backgroundColor: 'var(--color-primary)',
                            color: 'var(--color-on-primary)',
                            borderRadius: 'var(--radius-md)',
                        }"
                    >
                        إتمام الطلب
                    </Link>
                    <span
                        v-else
                        aria-disabled="true"
                        class="inline-flex min-h-11 items-center px-5 text-sm opacity-50"
                    >
                        إتمام الطلب
                    </span>
                </div>
            </div>
        </template>
    </StoreLayout>
</template>
