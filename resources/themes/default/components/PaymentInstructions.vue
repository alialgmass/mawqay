<script lang="ts">
export type PaymentData = {
    state:
        | 'awaiting_receipt'
        | 'under_review'
        | 'rejected'
        | 'exhausted'
        | 'paid'
        | 'closed';
    amount: string;
    reference: string;
    account: {
        name: string;
        number: string;
        instructions: string | null;
    } | null;
    expiresAt: string | null;
    attemptsUsed: number;
    attemptsLeft: number;
    maxAttempts: number;
    canSubmit: boolean;
    rejectionReason: string | null;
    receiptUrl: string | null;
    submitUrl: string;
    maxKilobytes: number;
    acceptedTypes: string[];
};
</script>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import FormField from './FormField.vue';
import SubmitButton from './SubmitButton.vue';

const props = defineProps<{ methodLabel: string; payment: PaymentData }>();

const form = useForm<{
    receipt: File | null;
    transfer_reference: string;
    sender_phone: string;
}>({ receipt: null, transfer_reference: '', sender_phone: '' });

const previewUrl = ref<string | null>(null);
const fileError = ref<string | null>(null);
const copied = ref<string | null>(null);
const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 1000);
});

onBeforeUnmount(() => {
    clearInterval(timer);

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

const remaining = computed(() => {
    if (!props.payment.expiresAt) {
        return null;
    }

    const seconds = Math.max(
        0,
        Math.floor(
            (new Date(props.payment.expiresAt).getTime() - now.value) / 1000,
        ),
    );
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${pad(Math.floor(seconds / 3600))}:${pad(Math.floor((seconds % 3600) / 60))}:${pad(seconds % 60)}`;
});

const isPdf = computed(() => form.receipt?.type === 'application/pdf');
const maxMegabytes = computed(() =>
    Math.round(props.payment.maxKilobytes / 1024),
);
const receiptError = computed(() => fileError.value ?? form.errors.receipt);
const orderError = computed(
    () => (form.errors as Record<string, string | undefined>).order,
);

async function copy(key: string, value: string) {
    try {
        await navigator.clipboard.writeText(value);
        copied.value = key;
        setTimeout(() => (copied.value = null), 2000);
    } catch {
        copied.value = null;
    }
}

function choose(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    fileError.value = null;
    form.clearErrors('receipt');

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }

    if (!file) {
        form.receipt = null;

        return;
    }

    if (!props.payment.acceptedTypes.includes(file.type)) {
        fileError.value =
            'نوع الملف غير مقبول. ارفعي صورة JPG أو PNG أو WebP أو ملف PDF.';
        form.receipt = null;

        return;
    }

    if (file.size > props.payment.maxKilobytes * 1024) {
        fileError.value = `حجم الملف أكبر من ${maxMegabytes.value} ميجابايت.`;
        form.receipt = null;

        return;
    }

    form.receipt = file;

    if (file.type !== 'application/pdf') {
        previewUrl.value = URL.createObjectURL(file);
    }
}

function submit() {
    if (!form.receipt) {
        fileError.value = 'اختاري ملف الإيصال أولًا.';

        return;
    }

    form.post(props.payment.submitUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <section
        aria-labelledby="payment-instructions-heading"
        data-payment-instructions
        :data-payment-state="payment.state"
        class="space-y-4 p-4"
        :style="{
            border: '1px dashed var(--color-border)',
            borderRadius: 'var(--radius-md)',
            backgroundColor: 'var(--color-surface)',
        }"
    >
        <h3 id="payment-instructions-heading" class="font-semibold">
            تعليمات الدفع
        </h3>

        <p v-if="payment.state === 'paid'" class="text-sm" data-payment-paid>
            تم قبول الدفع. شكرًا لكِ!
        </p>
        <p v-else-if="payment.state === 'closed'" class="text-sm">
            تم إغلاق هذا الطلب ولا يمكن الدفع له.
        </p>
        <p
            v-else-if="payment.state === 'under_review'"
            class="text-sm"
            role="status"
            data-payment-review
        >
            استلمنا إيصالك وهو قيد المراجعة. نحجز لكِ المنتجات إلى أن نراجعه.
        </p>

        <template v-if="payment.state !== 'paid' && payment.state !== 'closed'">
            <p
                v-if="
                    payment.state === 'rejected' ||
                    payment.state === 'exhausted'
                "
                role="alert"
                class="text-sm"
                data-payment-rejected
                :style="{ color: 'var(--color-danger)' }"
            >
                تم رفض الإيصال<template v-if="payment.rejectionReason">
                    — السبب: {{ payment.rejectionReason }}</template
                >.
                <template v-if="payment.state === 'exhausted'">
                    استنفدتِ كل المحاولات، تواصلي مع المنصة.
                </template>
                <template v-else>
                    متبقي لكِ {{ payment.attemptsLeft }} من
                    {{ payment.maxAttempts }} محاولات.
                </template>
            </p>

            <div v-if="payment.account" class="space-y-2 text-sm">
                <p>
                    حوّلي عبر <strong>{{ methodLabel }}</strong> إلى:
                </p>
                <ul class="space-y-2">
                    <li class="flex flex-wrap items-center gap-2">
                        <span>الاسم:</span>
                        <strong data-account-name>{{
                            payment.account.name
                        }}</strong>
                    </li>
                    <li class="flex flex-wrap items-center gap-2">
                        <span>الرقم:</span>
                        <strong dir="ltr" data-account-number>{{
                            payment.account.number
                        }}</strong>
                        <button
                            type="button"
                            class="min-h-11 px-3 text-xs underline"
                            @click="copy('number', payment.account.number)"
                        >
                            {{ copied === 'number' ? 'تم النسخ' : 'نسخ' }}
                        </button>
                    </li>
                    <li class="flex flex-wrap items-center gap-2">
                        <span>المبلغ بالضبط:</span>
                        <strong dir="ltr" data-amount>{{
                            payment.amount
                        }}</strong>
                        <button
                            type="button"
                            class="min-h-11 px-3 text-xs underline"
                            @click="copy('amount', payment.amount)"
                        >
                            {{ copied === 'amount' ? 'تم النسخ' : 'نسخ' }}
                        </button>
                    </li>
                    <li class="flex flex-wrap items-center gap-2">
                        <span>اكتبي رقم الطلب كمرجع:</span>
                        <strong dir="ltr" data-reference>{{
                            payment.reference
                        }}</strong>
                        <button
                            type="button"
                            class="min-h-11 px-3 text-xs underline"
                            @click="copy('reference', payment.reference)"
                        >
                            {{ copied === 'reference' ? 'تم النسخ' : 'نسخ' }}
                        </button>
                    </li>
                </ul>
                <p
                    v-if="payment.account.instructions"
                    class="whitespace-pre-line"
                    data-account-instructions
                >
                    {{ payment.account.instructions }}
                </p>
            </div>
            <p v-else class="text-sm" data-account-missing>
                بيانات الدفع غير متاحة حاليًا. تواصلي مع المنصة لإتمام الدفع.
            </p>

            <p
                v-if="remaining && payment.state !== 'under_review'"
                class="text-sm"
            >
                الوقت المتبقي لحجز الطلب:
                <strong dir="ltr" data-remaining>{{ remaining }}</strong>
            </p>

            <form
                v-if="payment.canSubmit"
                class="space-y-3"
                data-receipt-form
                novalidate
                @submit.prevent="submit"
            >
                <div class="space-y-1">
                    <label for="receipt" class="block text-sm font-medium"
                        >صورة أو ملف إيصال التحويل</label
                    >
                    <input
                        id="receipt"
                        name="receipt"
                        type="file"
                        :accept="payment.acceptedTypes.join(',')"
                        :aria-invalid="receiptError ? 'true' : undefined"
                        :aria-describedby="
                            receiptError ? 'receipt-error' : 'receipt-hint'
                        "
                        class="block min-h-11 w-full text-sm"
                        @change="choose"
                    />
                    <p
                        id="receipt-hint"
                        class="text-xs"
                        :style="{ color: 'var(--color-text-muted)' }"
                    >
                        JPG أو PNG أو WebP أو PDF، حتى
                        {{ maxMegabytes }} ميجابايت.
                    </p>
                    <p
                        v-if="receiptError"
                        id="receipt-error"
                        role="alert"
                        class="text-sm"
                        :style="{ color: 'var(--color-danger)' }"
                    >
                        {{ receiptError }}
                    </p>
                </div>

                <img
                    v-if="previewUrl"
                    :src="previewUrl"
                    alt="معاينة الإيصال"
                    class="max-h-64 w-auto"
                    data-receipt-preview
                />
                <p
                    v-else-if="isPdf && form.receipt"
                    class="text-sm"
                    data-receipt-preview
                >
                    {{ form.receipt.name }}
                </p>

                <FormField
                    id="transfer_reference"
                    v-model="form.transfer_reference"
                    label="مرجع التحويل (اختياري)"
                    dir="ltr"
                    :error="form.errors.transfer_reference"
                />
                <FormField
                    id="sender_phone"
                    v-model="form.sender_phone"
                    label="الرقم الذي حوّلتِ منه (اختياري)"
                    type="tel"
                    dir="ltr"
                    autocomplete="tel"
                    :error="form.errors.sender_phone"
                />

                <p
                    v-if="orderError"
                    role="alert"
                    class="text-sm"
                    :style="{ color: 'var(--color-danger)' }"
                >
                    {{ orderError }}
                </p>

                <div
                    v-if="form.processing && form.progress"
                    role="progressbar"
                    aria-label="تقدم الرفع"
                    :aria-valuenow="form.progress.percentage"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    class="h-2 w-full overflow-hidden"
                    :style="{
                        backgroundColor: 'var(--color-border)',
                        borderRadius: 'var(--radius-md)',
                    }"
                >
                    <div
                        class="h-full"
                        :style="{
                            width: `${form.progress.percentage}%`,
                            backgroundColor: 'var(--color-primary)',
                        }"
                    />
                </div>

                <SubmitButton :processing="form.processing">
                    إرسال الإيصال
                </SubmitButton>
                <p
                    class="text-xs"
                    :style="{ color: 'var(--color-text-muted)' }"
                >
                    المحاولات المتبقية: {{ payment.attemptsLeft }} من
                    {{ payment.maxAttempts }}
                </p>
            </form>
        </template>

        <p v-if="payment.receiptUrl" class="text-sm">
            <a
                :href="payment.receiptUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex min-h-11 items-center underline"
                data-receipt-link
                >عرض الإيصال الذي أرسلتِه</a
            >
        </p>
    </section>
</template>
