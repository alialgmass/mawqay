<script setup lang="ts">
defineProps<{ methodLabel: string; total: string }>();
</script>

<template>
    <section
        aria-labelledby="payment-instructions-heading"
        data-payment-instructions
        class="space-y-2 p-4"
        :style="{
            border: '1px dashed var(--color-border)',
            borderRadius: 'var(--radius-md)',
            backgroundColor: 'var(--color-surface)',
        }"
    >
        <h3 id="payment-instructions-heading" class="font-semibold">
            تعليمات الدفع
        </h3>
        <p class="text-sm">
            طريقة الدفع المختارة: {{ methodLabel }} — المبلغ المطلوب:
            <span dir="ltr">{{ total }}</span>
        </p>
        <p class="text-sm" :style="{ color: 'var(--color-text-muted)' }">
            ستظهر هنا بيانات الحساب وخطوات رفع إيصال التحويل قريبًا.
        </p>
    </section>
</template>
