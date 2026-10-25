<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import PaymentInstructions from '../components/PaymentInstructions.vue';
import type { PaymentData } from '../components/PaymentInstructions.vue';
import StatusBadge from '../components/StatusBadge.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

type OrderData = {
    number: string;
    status: string;
    statusLabel: string;
    total: string;
    subtotal: string;
    shippingFee: string;
    placedAt: string;
    expiresAt: string | null;
    paymentMethodLabel: string;
    canCancel: boolean;
    cancelUrl: string;
    payment: PaymentData;
    address: Record<string, string | null>;
    items: {
        name: string;
        sku: string;
        size: string;
        color: string;
        quantity: number;
        unitPrice: string;
        lineTotal: string;
    }[];
};

defineProps<{ seo: Seo; order: OrderData }>();

function cancel(url: string) {
    router.post(url);
}
</script>

<template>
    <StoreLayout :seo="seo">
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2
                    class="text-xl font-semibold"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                >
                    الطلب {{ order.number }}
                </h2>
                <StatusBadge
                    :status="order.status"
                    :label="order.statusLabel"
                />
            </div>

            <p class="text-sm">تاريخ الطلب: {{ order.placedAt }}</p>
            <p v-if="order.expiresAt" class="text-sm">
                ينتهي حجز المنتجات في
                <time :datetime="order.expiresAt">{{ order.expiresAt }}</time>
            </p>

            <PaymentInstructions
                :method-label="order.paymentMethodLabel"
                :payment="order.payment"
            />

            <ul class="space-y-2">
                <li
                    v-for="(item, index) in order.items"
                    :key="index"
                    class="flex justify-between text-sm"
                >
                    <span
                        >{{ item.name }} ({{ item.size }} / {{ item.color }}) ×
                        {{ item.quantity }}</span
                    >
                    <span dir="ltr">{{ item.lineTotal }}</span>
                </li>
            </ul>

            <dl class="space-y-1 text-sm">
                <div class="flex justify-between">
                    <dt>المجموع الفرعي</dt>
                    <dd dir="ltr">{{ order.subtotal }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>الشحن</dt>
                    <dd dir="ltr">{{ order.shippingFee }}</dd>
                </div>
                <div class="flex justify-between font-semibold">
                    <dt>الإجمالي</dt>
                    <dd dir="ltr">{{ order.total }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>طريقة الدفع</dt>
                    <dd>{{ order.paymentMethodLabel }}</dd>
                </div>
            </dl>

            <p class="text-sm">
                التوصيل إلى: {{ order.address.recipient_name }}،
                {{ order.address.governorate_name }}، {{ order.address.city }}،
                {{ order.address.address_line }}
            </p>

            <button
                v-if="order.canCancel"
                type="button"
                class="min-h-11 px-5 text-sm font-medium"
                :style="{
                    border: '1px solid var(--color-danger)',
                    color: 'var(--color-danger)',
                    borderRadius: 'var(--radius-md)',
                }"
                @click="cancel(order.cancelUrl)"
            >
                إلغاء الطلب
            </button>
        </div>
    </StoreLayout>
</template>
