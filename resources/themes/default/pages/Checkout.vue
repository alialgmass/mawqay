<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EmptyState from '../components/EmptyState.vue';
import FormField from '../components/FormField.vue';
import SubmitButton from '../components/SubmitButton.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

type CheckoutAddress = {
    id: number;
    label: string;
    recipientName: string;
    phone: string;
    governorateName: string;
    city: string;
    addressLine: string;
    isDefault: boolean;
    shippingFee: string | null;
    total: string | null;
};

const props = defineProps<{
    seo: Seo;
    ready: boolean;
    items: {
        name: string;
        size: string;
        color: string;
        quantity: number;
        lineTotal: string;
    }[];
    subtotal: string;
    addresses: CheckoutAddress[];
    paymentMethods: { value: string; label: string }[];
    governorates: { value: string; name: string }[];
    maxAddresses: number;
    issues: string[];
}>();

const shell = useShell();
const page = usePage();
const errors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);
const step = ref(1);
const chosenAddress = ref<number | null>(
    props.addresses.find((a) => a.isDefault)?.id ??
        props.addresses[0]?.id ??
        null,
);
const paymentMethod = ref<string>(props.paymentMethods[0]?.value ?? '');

const address = computed(
    () => props.addresses.find((a) => a.id === chosenAddress.value) ?? null,
);
const method = computed(() =>
    props.paymentMethods.find((m) => m.value === paymentMethod.value),
);

const order = useForm({ address_id: 0, payment_method: '' });
const newAddress = useForm({
    // The server only follows same-store paths, never absolute URLs.
    redirect: new URL(shell.value.urls.checkout, window.location.origin).pathname,
    label: '',
    recipient_name: '',
    phone: '',
    governorate: props.governorates[0]?.value ?? '',
    city: '',
    address_line: '',
    notes: '',
    is_default: false,
});

const issueText: Record<string, string> = {
    store_paused: 'هذا المتجر متوقف مؤقتًا عن استقبال الطلبات.',
    empty: 'سلتك فارغة.',
    cart_needs_review:
        'راجعي السلة: بعض المنتجات تغيّر سعرها أو لم تعد متوفرة.',
    shipping_not_set: 'لم يحدد المتجر رسوم الشحن بعد.',
};

function saveAddress() {
    const knownIds = props.addresses.map((a) => a.id);

    newAddress.post(shell.value.actions.addresses, {
        preserveScroll: true,
        onSuccess: () => {
            newAddress.reset();

            const created = props.addresses.find(
                (a) => !knownIds.includes(a.id),
            );

            if (created) {
                chosenAddress.value = created.id;
                step.value = created.shippingFee === null ? 1 : 2;
            }
        },
    });
}

function placeOrder() {
    if (address.value === null || address.value.shippingFee === null) {
        return;
    }

    order.address_id = address.value.id;
    order.payment_method = paymentMethod.value;
    order.post(shell.value.actions.checkout);
}

const steps = ['العنوان', 'الشحن والدفع', 'المراجعة'];
</script>

<template>
    <StoreLayout :seo="seo">
        <h2
            class="mb-4 text-xl font-semibold"
            :style="{ fontFamily: 'var(--font-heading)' }"
        >
            إتمام الطلب
        </h2>

        <ul
            v-if="issues.length"
            role="alert"
            class="mb-4 space-y-1 p-3 text-sm"
            :style="{ backgroundColor: '#FEF3C7', color: '#92400E' }"
        >
            <li v-for="issue in issues" :key="issue">
                {{ issueText[issue] ?? issue }}
            </li>
        </ul>

        <EmptyState v-if="items.length === 0" message="سلتك فارغة" />

        <div v-else class="mx-auto max-w-2xl space-y-6">
            <ol class="flex gap-2 text-sm" aria-label="خطوات الطلب">
                <li
                    v-for="(label, index) in steps"
                    :key="label"
                    class="flex-1 p-2 text-center"
                    :aria-current="step === index + 1 ? 'step' : undefined"
                    :style="{
                        borderBottom: `3px solid ${step >= index + 1 ? 'var(--color-primary)' : 'var(--color-border)'}`,
                    }"
                >
                    {{ index + 1 }}. {{ label }}
                </li>
            </ol>

            <section
                v-if="step === 1"
                aria-labelledby="step-address"
                class="space-y-4"
            >
                <h3 id="step-address" class="font-semibold">
                    اختاري عنوان التوصيل
                </h3>

                <fieldset v-if="addresses.length" class="space-y-2">
                    <legend class="sr-only">العناوين</legend>
                    <label
                        v-for="a in addresses"
                        :key="a.id"
                        class="flex min-h-11 cursor-pointer items-start gap-3 p-3"
                        :style="{
                            border: `1px solid ${chosenAddress === a.id ? 'var(--color-primary)' : 'var(--color-border)'}`,
                            borderRadius: 'var(--radius-sm)',
                        }"
                    >
                        <input
                            v-model="chosenAddress"
                            type="radio"
                            name="address"
                            :value="a.id"
                            class="mt-1 h-5 w-5"
                        />
                        <span class="text-sm">
                            <strong>{{ a.label }}</strong> — {{ a.recipientName
                            }}<br />
                            {{ a.governorateName }}، {{ a.city }}،
                            {{ a.addressLine }}
                            <span
                                v-if="a.shippingFee === null"
                                class="block"
                                :style="{ color: 'var(--color-danger)' }"
                            >
                                لا يوصّل المتجر إلى هذه المحافظة
                            </span>
                        </span>
                    </label>
                </fieldset>

                <details
                    v-if="addresses.length < maxAddresses"
                    :open="addresses.length === 0"
                >
                    <summary
                        class="min-h-11 cursor-pointer py-2 text-sm underline"
                    >
                        إضافة عنوان جديد
                    </summary>
                    <form
                        class="mt-3 space-y-3"
                        novalidate
                        @submit.prevent="saveAddress"
                    >
                        <FormField
                            id="label"
                            v-model="newAddress.label"
                            label="اسم العنوان (مثل: المنزل)"
                            required
                            :error="newAddress.errors.label"
                        />
                        <FormField
                            id="recipient_name"
                            v-model="newAddress.recipient_name"
                            label="اسم المستلمة"
                            required
                            :error="newAddress.errors.recipient_name"
                        />
                        <FormField
                            id="phone"
                            v-model="newAddress.phone"
                            type="tel"
                            label="رقم الموبايل"
                            dir="ltr"
                            required
                            :error="newAddress.errors.phone"
                        />
                        <div class="space-y-1">
                            <label
                                for="governorate"
                                class="block text-sm font-medium"
                                >المحافظة</label
                            >
                            <select
                                id="governorate"
                                v-model="newAddress.governorate"
                                class="min-h-11 w-full px-3"
                                :style="{
                                    border: '1px solid var(--color-border)',
                                    borderRadius: 'var(--radius-sm)',
                                    backgroundColor: 'var(--color-bg)',
                                }"
                            >
                                <option
                                    v-for="g in governorates"
                                    :key="g.value"
                                    :value="g.value"
                                >
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>
                        <FormField
                            id="city"
                            v-model="newAddress.city"
                            label="المدينة / الحي"
                            required
                            :error="newAddress.errors.city"
                        />
                        <FormField
                            id="address_line"
                            v-model="newAddress.address_line"
                            label="العنوان بالتفصيل"
                            required
                            :error="newAddress.errors.address_line"
                        />
                        <SubmitButton
                            :processing="newAddress.processing"
                            variant="plain"
                            >حفظ العنوان</SubmitButton
                        >
                    </form>
                </details>

                <button
                    type="button"
                    class="min-h-11 px-5 text-sm font-semibold transition-all duration-200 disabled:opacity-50"
                    :disabled="address === null"
                    :style="{
                        backgroundColor: 'var(--color-primary)',
                        color: 'var(--color-on-primary)',
                        borderRadius: 'var(--radius-sm)',
                    }"
                    @click="step = 2"
                >
                    التالي
                </button>
            </section>

            <section
                v-else-if="step === 2 && address"
                aria-labelledby="step-payment"
                class="space-y-4"
            >
                <h3 id="step-payment" class="font-semibold">
                    الشحن وطريقة الدفع
                </h3>

                <p v-if="address.shippingFee !== null" class="text-sm">
                    رسوم الشحن إلى {{ address.city }}:
                    <strong dir="ltr">{{ address.shippingFee }}</strong>
                </p>
                <p
                    v-else
                    role="alert"
                    class="text-sm"
                    :style="{ color: 'var(--color-danger)' }"
                >
                    لا يوصّل المتجر إلى هذه المحافظة. اختاري عنوانًا آخر.
                </p>

                <fieldset class="space-y-2">
                    <legend class="mb-1 text-sm font-medium">
                        طريقة الدفع
                    </legend>
                    <label
                        v-for="m in paymentMethods"
                        :key="m.value"
                        class="flex min-h-11 cursor-pointer items-center gap-3 p-3"
                        :style="{
                            border: `1px solid ${paymentMethod === m.value ? 'var(--color-primary)' : 'var(--color-border)'}`,
                            borderRadius: 'var(--radius-sm)',
                        }"
                    >
                        <input
                            v-model="paymentMethod"
                            type="radio"
                            name="payment_method"
                            :value="m.value"
                            class="h-5 w-5"
                        />
                        {{ m.label }}
                    </label>
                </fieldset>

                <div class="flex gap-3">
                    <button
                        type="button"
                        class="min-h-11 px-5 text-sm underline"
                        @click="step = 1"
                    >
                        السابق
                    </button>
                    <button
                        type="button"
                        class="min-h-11 px-5 text-sm font-semibold transition-all duration-200 disabled:opacity-50"
                        :disabled="
                            address.shippingFee === null || !paymentMethod
                        "
                        :style="{
                            backgroundColor: 'var(--color-primary)',
                            color: 'var(--color-on-primary)',
                            borderRadius: 'var(--radius-sm)',
                        }"
                        @click="step = 3"
                    >
                        التالي
                    </button>
                </div>
            </section>

            <section
                v-else-if="step === 3 && address"
                aria-labelledby="step-review"
                class="space-y-4"
            >
                <h3 id="step-review" class="font-semibold">مراجعة الطلب</h3>

                <ul class="space-y-1 text-sm">
                    <li
                        v-for="(item, index) in items"
                        :key="index"
                        class="flex justify-between"
                    >
                        <span
                            >{{ item.name }} ({{ item.size }} /
                            {{ item.color }}) × {{ item.quantity }}</span
                        >
                        <span dir="ltr">{{ item.lineTotal }}</span>
                    </li>
                </ul>

                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt>المجموع الفرعي</dt>
                        <dd dir="ltr">{{ subtotal }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>الشحن</dt>
                        <dd dir="ltr">{{ address.shippingFee }}</dd>
                    </div>
                    <div class="flex justify-between font-semibold">
                        <dt>الإجمالي</dt>
                        <dd dir="ltr">{{ address.total }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>الدفع</dt>
                        <dd>{{ method?.label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>التوصيل إلى</dt>
                        <dd>
                            {{ address.governorateName }}، {{ address.city }}
                        </dd>
                    </div>
                </dl>

                <p
                    v-for="error in [
                        errors.checkout,
                        errors.address_id,
                        errors.payment_method,
                    ]"
                    :key="String(error)"
                    v-show="error"
                    role="alert"
                    class="text-sm"
                    :style="{ color: 'var(--color-danger)' }"
                >
                    {{ error }}
                </p>

                <div class="flex gap-3">
                    <button
                        type="button"
                        class="min-h-11 px-5 text-sm underline"
                        @click="step = 2"
                    >
                        السابق
                    </button>
                    <button
                        type="button"
                        class="min-h-11 px-5 text-sm font-semibold transition-all duration-200 disabled:opacity-50"
                        :disabled="!ready || order.processing"
                        :style="{
                            backgroundColor: 'var(--color-primary)',
                            color: 'var(--color-on-primary)',
                            borderRadius: 'var(--radius-sm)',
                        }"
                        @click="placeOrder"
                    >
                        تأكيد الطلب
                    </button>
                </div>
                <Link
                    :href="shell.urls.cart"
                    class="inline-flex min-h-11 items-center font-semibold text-[var(--color-primary)] hover:underline text-sm"
                    >العودة إلى السلة</Link
                >
            </section>
        </div>
    </StoreLayout>
</template>
