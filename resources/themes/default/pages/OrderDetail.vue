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
    timeline: { status: string; label: string; at: string }[];
    shipment: {
        courier: string;
        trackingNumber: string | null;
        trackingUrl: string | null;
        shippedAt: string;
        deliveredAt: string | null;
    } | null;
    survey: { pending: boolean; url: string } | null;
    canConfirmReceipt: boolean;
    confirmReceiptUrl: string;
    storeContact: {
        email: string | null;
        phone: string | null;
        whatsapp: string | null;
    };
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

function confirmReceipt(url: string) {
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

            <section v-if="order.timeline.length" class="space-y-2">
                <h3 class="font-semibold">مسار الطلب</h3>
                <ol class="space-y-1 text-sm">
                    <li
                        v-for="(step, index) in order.timeline"
                        :key="index"
                        class="flex justify-between"
                    >
                        <span>{{ step.label }}</span>
                        <time :datetime="step.at" dir="ltr">{{
                            step.at.slice(0, 10)
                        }}</time>
                    </li>
                </ol>
            </section>

            <section v-if="order.shipment" class="space-y-1 text-sm">
                <h3 class="font-semibold">الشحنة</h3>
                <p>شركة الشحن: {{ order.shipment.courier }}</p>
                <p v-if="order.shipment.trackingNumber">
                    رقم التتبع:
                    <a
                        v-if="order.shipment.trackingUrl"
                        :href="order.shipment.trackingUrl"
                        target="_blank"
                        rel="noopener"
                        dir="ltr"
                        class="underline"
                        >{{ order.shipment.trackingNumber }}</a
                    >
                    <span v-else dir="ltr">{{
                        order.shipment.trackingNumber
                    }}</span>
                </p>
            </section>

            <button
                v-if="order.canConfirmReceipt"
                type="button"
                class="min-h-11 px-5 text-sm font-medium"
                :style="{
                    background: 'var(--color-primary)',
                    color: 'var(--color-on-primary)',
                    borderRadius: 'var(--radius-md)',
                }"
                @click="confirmReceipt(order.confirmReceiptUrl)"
            >
                استلمت طلبي
            </button>

            <a
                v-if="order.survey?.pending"
                :href="order.survey.url"
                class="inline-flex min-h-11 items-center px-5 text-sm font-medium underline"
            >
                قيّمي تجربتك مع هذا الطلب
            </a>

            <section
                v-if="
                    order.storeContact.email ||
                    order.storeContact.phone ||
                    order.storeContact.whatsapp
                "
                class="space-y-1 text-sm"
            >
                <h3 class="font-semibold">
                    لأي مشكلة أو استرجاع تواصلي مع المتجر
                </h3>
                <p v-if="order.storeContact.phone" dir="ltr">
                    {{ order.storeContact.phone }}
                </p>
                <p v-if="order.storeContact.whatsapp" dir="ltr">
                    {{ order.storeContact.whatsapp }}
                </p>
                <p v-if="order.storeContact.email" dir="ltr">
                    {{ order.storeContact.email }}
                </p>
            </section>

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
