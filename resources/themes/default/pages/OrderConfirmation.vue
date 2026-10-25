<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PaymentInstructions from '../components/PaymentInstructions.vue';
import type { PaymentData } from '../components/PaymentInstructions.vue';
import StatusBadge from '../components/StatusBadge.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
    order: {
        number: string;
        status: string;
        statusLabel: string;
        total: string;
        expiresAt: string | null;
        paymentMethodLabel: string;
        url: string;
        payment: PaymentData;
    };
}>();

const shell = useShell();
</script>

<template>
    <StoreLayout :seo="seo">
        <div class="mx-auto max-w-xl space-y-6">
            <h2
                class="text-xl font-semibold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                تم إنشاء طلبك
            </h2>

            <p>
                رقم الطلب:
                <strong dir="ltr" data-order-number>{{ order.number }}</strong>
            </p>
            <p>
                <StatusBadge
                    :status="order.status"
                    :label="order.statusLabel"
                />
            </p>
            <p>
                الإجمالي: <strong dir="ltr">{{ order.total }}</strong>
            </p>
            <p v-if="order.expiresAt" class="text-sm">
                نحجز لك المنتجات حتى
                <time :datetime="order.expiresAt">{{ order.expiresAt }}</time>
            </p>

            <PaymentInstructions
                :method-label="order.paymentMethodLabel"
                :payment="order.payment"
            />

            <div class="flex flex-wrap gap-4 text-sm">
                <Link
                    :href="order.url"
                    class="inline-flex min-h-11 items-center underline"
                    >تفاصيل الطلب</Link
                >
                <Link
                    :href="shell.urls.orders"
                    class="inline-flex min-h-11 items-center underline"
                    >كل طلباتي</Link
                >
            </div>
        </div>
    </StoreLayout>
</template>
