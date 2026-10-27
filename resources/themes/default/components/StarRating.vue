<script setup lang="ts">
defineProps<{ label: string; modelValue: number }>();
const emit = defineEmits<{ 'update:modelValue': [value: number] }>();
</script>

<template>
    <fieldset class="space-y-2">
        <legend class="text-sm font-medium">{{ label }}</legend>
        <div class="flex gap-1" dir="ltr" role="radiogroup" :aria-label="label">
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                role="radio"
                :aria-checked="modelValue === star"
                :aria-label="`${star} / 5`"
                class="flex min-h-11 min-w-11 items-center justify-center text-3xl leading-none"
                :style="{
                    color:
                        star <= modelValue
                            ? 'var(--color-primary)'
                            : 'var(--color-border, #d1d5db)',
                }"
                @click="emit('update:modelValue', star)"
                @keydown.right.prevent="
                    emit('update:modelValue', Math.min(5, modelValue + 1))
                "
                @keydown.left.prevent="
                    emit('update:modelValue', Math.max(1, modelValue - 1))
                "
            >
                ★
            </button>
        </div>
    </fieldset>
</template>
