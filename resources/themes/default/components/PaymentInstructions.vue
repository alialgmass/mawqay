<script lang="ts">
export type PaymentData = {
    state:
        | 'awaiting_receipt'
        | 'awaiting_online'
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
    online: { available: boolean; url: string | null };
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

const dragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function accept(file: File | null) {
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

function choose(event: Event) {
    accept((event.target as HTMLInputElement).files?.[0] ?? null);
}

function drop(event: DragEvent) {
    dragging.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;

    if (file && fileInput.value) {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        fileInput.value.files = transfer.files;
    }

    accept(file);
}

function clearFile() {
    if (fileInput.value) {
        fileInput.value.value = '';
    }

    accept(null);
}

const fileSize = computed(() =>
    form.receipt ? `${(form.receipt.size / 1024 / 1024).toFixed(1)} MB` : '',
);

const onlineForm = useForm({});
const onlineError = computed(
    () => (onlineForm.errors as Record<string, string | undefined>).online,
);

function payOnline() {
    if (props.payment.online.url) {
        onlineForm.post(props.payment.online.url);
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
        class="space-y-5 p-5 shadow-sm"
        :style="{
            border: '1px solid var(--color-border)',
            borderRadius: 'var(--radius-lg)',
            backgroundColor: 'var(--color-bg)',
        }"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3
                id="payment-instructions-heading"
                class="text-lg font-bold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                تعليمات الدفع
            </h3>
            <p
                v-if="
                    remaining &&
                    payment.state !== 'under_review' &&
                    payment.state !== 'paid' &&
                    payment.state !== 'closed'
                "
                class="px-3 py-1 text-xs font-semibold"
                :style="{
                    backgroundColor: '#FEF3C7',
                    color: '#92400E',
                    borderRadius: '9999px',
                }"
            >
                ⏱ الوقت المتبقي لحجز الطلب:
                <strong dir="ltr" data-remaining>{{ remaining }}</strong>
            </p>
        </div>

        <p
            v-if="payment.state === 'paid'"
            class="p-3 text-sm font-semibold"
            data-payment-paid
            :style="{
                backgroundColor: '#DCFCE7',
                color: '#166534',
                borderRadius: 'var(--radius-sm)',
            }"
        >
            ✓ تم قبول الدفع. شكرًا لكِ!
        </p>
        <p
            v-else-if="payment.state === 'closed'"
            class="p-3 text-sm"
            :style="{
                backgroundColor: 'var(--color-surface)',
                borderRadius: 'var(--radius-sm)',
            }"
        >
            تم إغلاق هذا الطلب ولا يمكن الدفع له.
        </p>
        <p
            v-else-if="payment.state === 'under_review'"
            class="p-3 text-sm font-semibold"
            role="status"
            data-payment-review
            :style="{
                backgroundColor: '#DBEAFE',
                color: '#1E40AF',
                borderRadius: 'var(--radius-sm)',
            }"
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
                class="p-3 text-sm font-semibold"
                data-payment-rejected
                :style="{
                    backgroundColor: '#FEE2E2',
                    color: '#991B1B',
                    borderRadius: 'var(--radius-sm)',
                }"
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

            <div
                v-if="payment.state === 'awaiting_online'"
                class="space-y-3 text-sm"
                data-online-payment
            >
                <p>
                    المبلغ المطلوب:
                    <strong class="text-lg" dir="ltr">{{
                        payment.amount
                    }}</strong>
                </p>
                <p
                    v-if="onlineError"
                    role="alert"
                    :style="{ color: 'var(--color-danger)' }"
                >
                    {{ onlineError }}
                </p>
                <button
                    v-if="payment.online.available"
                    type="button"
                    class="min-h-12 w-full px-4 text-base font-semibold transition-all duration-200 hover:shadow-lg disabled:opacity-60"
                    :disabled="onlineForm.processing"
                    :style="{
                        backgroundColor: 'var(--color-primary)',
                        color: 'var(--color-on-primary)',
                        borderRadius: 'var(--radius-sm)',
                    }"
                    data-pay-online
                    @click="payOnline"
                >
                    ادفعي أونلاين (كارت / محفظة)
                </button>
                <p v-else data-account-missing>
                    الدفع أونلاين غير متاح حاليًا. تواصلي مع المنصة.
                </p>
            </div>

            <div v-else-if="payment.account" class="space-y-3 text-sm">
                <p class="flex items-center gap-2 font-semibold">
                    <span
                        class="flex h-6 w-6 items-center justify-center text-xs font-bold"
                        :style="{
                            backgroundColor: 'var(--color-primary)',
                            color: 'var(--color-on-primary)',
                            borderRadius: '9999px',
                        }"
                        >1</span
                    >
                    حوّلي عبر <strong>{{ methodLabel }}</strong> إلى:
                </p>
                <ul
                    class="divide-y"
                    :style="{
                        border: '1px solid var(--color-border)',
                        borderRadius: 'var(--radius-lg)',
                        backgroundColor: 'var(--color-surface)',
                    }"
                >
                    <li
                        class="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                    >
                        <span :style="{ color: 'var(--color-text-muted)' }"
                            >الاسم</span
                        >
                        <strong data-account-name>{{
                            payment.account.name
                        }}</strong>
                    </li>
                    <li
                        class="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                    >
                        <span :style="{ color: 'var(--color-text-muted)' }"
                            >الرقم</span
                        >
                        <span class="flex items-center gap-2">
                            <strong dir="ltr" data-account-number>{{
                                payment.account.number
                            }}</strong>
                            <button
                                type="button"
                                class="min-h-9 px-3 text-xs font-semibold"
                                :style="{
                                    backgroundColor: 'var(--color-secondary)',
                                    color: 'var(--color-primary)',
                                    borderRadius: '9999px',
                                }"
                                @click="copy('number', payment.account.number)"
                            >
                                {{ copied === 'number' ? '✓ تم النسخ' : 'نسخ' }}
                            </button>
                        </span>
                    </li>
                    <li
                        class="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                    >
                        <span :style="{ color: 'var(--color-text-muted)' }"
                            >المبلغ بالضبط</span
                        >
                        <span class="flex items-center gap-2">
                            <strong class="text-base" dir="ltr" data-amount>{{
                                payment.amount
                            }}</strong>
                            <button
                                type="button"
                                class="min-h-9 px-3 text-xs font-semibold"
                                :style="{
                                    backgroundColor: 'var(--color-secondary)',
                                    color: 'var(--color-primary)',
                                    borderRadius: '9999px',
                                }"
                                @click="copy('amount', payment.amount)"
                            >
                                {{ copied === 'amount' ? '✓ تم النسخ' : 'نسخ' }}
                            </button>
                        </span>
                    </li>
                    <li
                        class="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                    >
                        <span :style="{ color: 'var(--color-text-muted)' }"
                            >اكتبي رقم الطلب كمرجع</span
                        >
                        <span class="flex items-center gap-2">
                            <strong dir="ltr" data-reference>{{
                                payment.reference
                            }}</strong>
                            <button
                                type="button"
                                class="min-h-9 px-3 text-xs font-semibold"
                                :style="{
                                    backgroundColor: 'var(--color-secondary)',
                                    color: 'var(--color-primary)',
                                    borderRadius: '9999px',
                                }"
                                @click="copy('reference', payment.reference)"
                            >
                                {{
                                    copied === 'reference'
                                        ? '✓ تم النسخ'
                                        : 'نسخ'
                                }}
                            </button>
                        </span>
                    </li>
                </ul>
                <p
                    v-if="payment.account.instructions"
                    class="whitespace-pre-line"
                    data-account-instructions
                    :style="{ color: 'var(--color-text-muted)' }"
                >
                    {{ payment.account.instructions }}
                </p>
            </div>
            <p v-else class="text-sm" data-account-missing>
                بيانات الدفع غير متاحة حاليًا. تواصلي مع المنصة لإتمام الدفع.
            </p>

            <form
                v-if="payment.canSubmit"
                class="space-y-4"
                data-receipt-form
                novalidate
                @submit.prevent="submit"
            >
                <p class="flex items-center gap-2 text-sm font-semibold">
                    <span
                        class="flex h-6 w-6 items-center justify-center text-xs font-bold"
                        :style="{
                            backgroundColor: 'var(--color-primary)',
                            color: 'var(--color-on-primary)',
                            borderRadius: '9999px',
                        }"
                        >2</span
                    >
                    <label for="receipt">صورة أو ملف إيصال التحويل</label>
                </p>

                <div
                    class="relative flex flex-col items-center justify-center gap-2 px-4 py-8 text-center transition-colors duration-200"
                    :style="{
                        border: `2px dashed ${dragging || form.receipt ? 'var(--color-primary)' : 'var(--color-border)'}`,
                        borderRadius: 'var(--radius-lg)',
                        backgroundColor:
                            dragging || form.receipt
                                ? 'var(--color-secondary)'
                                : 'var(--color-surface)',
                    }"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="drop"
                >
                    <template v-if="!form.receipt">
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            class="h-10 w-10"
                            fill="none"
                            stroke="var(--color-primary)"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M12 16V4m0 0 4 4m-4-4L8 8" />
                            <path d="M4 14v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4" />
                        </svg>
                        <p class="text-sm font-semibold">
                            اضغطي لاختيار الإيصال أو اسحبيه هنا
                        </p>
                        <p
                            id="receipt-hint"
                            class="text-xs"
                            :style="{ color: 'var(--color-text-muted)' }"
                        >
                            JPG أو PNG أو WebP أو PDF، حتى
                            {{ maxMegabytes }} ميجابايت.
                        </p>
                    </template>

                    <template v-else>
                        <img
                            v-if="previewUrl"
                            :src="previewUrl"
                            alt="معاينة الإيصال"
                            class="max-h-56 w-auto shadow-md"
                            :style="{ borderRadius: 'var(--radius-sm)' }"
                            data-receipt-preview
                        />
                        <p v-else class="text-3xl" aria-hidden="true">📄</p>
                        <p
                            class="max-w-full truncate text-sm font-semibold"
                            :data-receipt-preview="previewUrl ? undefined : ''"
                            dir="ltr"
                        >
                            {{ form.receipt.name }}
                            <span
                                class="font-normal"
                                :style="{ color: 'var(--color-text-muted)' }"
                                >· {{ fileSize }}</span
                            >
                        </p>
                    </template>

                    <input
                        id="receipt"
                        ref="fileInput"
                        name="receipt"
                        type="file"
                        :accept="payment.acceptedTypes.join(',')"
                        :aria-invalid="receiptError ? 'true' : undefined"
                        :aria-describedby="
                            receiptError ? 'receipt-error' : 'receipt-hint'
                        "
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        @change="choose"
                    />
                </div>

                <button
                    v-if="form.receipt"
                    type="button"
                    class="min-h-11 text-sm font-semibold"
                    :style="{ color: 'var(--color-danger)' }"
                    @click="clearFile"
                >
                    حذف واختيار ملف آخر
                </button>

                <p
                    v-if="receiptError"
                    id="receipt-error"
                    role="alert"
                    class="text-sm"
                    :style="{ color: 'var(--color-danger)' }"
                >
                    {{ receiptError }}
                </p>

                <details>
                    <summary
                        class="min-h-11 cursor-pointer py-2 text-sm font-semibold text-[var(--color-primary)]"
                    >
                        تفاصيل إضافية (اختياري)
                    </summary>
                    <div class="mt-2 space-y-3">
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
                    </div>
                </details>

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
                        borderRadius: '9999px',
                    }"
                >
                    <div
                        class="h-full transition-all duration-200"
                        :style="{
                            width: `${form.progress.percentage}%`,
                            backgroundColor: 'var(--color-primary)',
                        }"
                    />
                </div>

                <SubmitButton :processing="form.processing" class="w-full">
                    إرسال الإيصال
                </SubmitButton>
                <p
                    class="text-center text-xs"
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
                class="inline-flex min-h-11 items-center font-semibold text-[var(--color-primary)] hover:underline"
                data-receipt-link
                >عرض الإيصال الذي أرسلتِه</a
            >
        </p>
    </section>
</template>
