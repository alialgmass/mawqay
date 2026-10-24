<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        id: string;
        label: string;
        modelValue: string | null;
        type?: string;
        error?: string;
        autocomplete?: string;
        required?: boolean;
        dir?: 'ltr' | 'rtl';
        hint?: string;
    }>(),
    { type: 'text', dir: undefined },
);

defineEmits<{ 'update:modelValue': [value: string] }>();

const describedBy = computed(() =>
    props.error
        ? `${props.id}-error`
        : props.hint
          ? `${props.id}-hint`
          : undefined,
);
</script>

<template>
    <div class="space-y-1">
        <label :for="id" class="block text-sm font-medium">{{ label }}</label>
        <input
            :id="id"
            :name="id"
            :type="type"
            :value="modelValue ?? ''"
            :autocomplete="autocomplete"
            :required="required"
            :dir="dir"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy"
            class="min-h-11 w-full px-3 focus-visible:outline-2 focus-visible:outline-offset-2"
            :style="{
                border: `1px solid ${error ? 'var(--color-danger)' : 'var(--color-border)'}`,
                borderRadius: 'var(--radius-md)',
                backgroundColor: 'var(--color-bg)',
                color: 'var(--color-text)',
            }"
            @input="
                $emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
        />
        <p
            v-if="hint && !error"
            :id="`${id}-hint`"
            class="text-xs"
            :style="{ color: 'var(--color-text-muted)' }"
        >
            {{ hint }}
        </p>
        <p
            v-if="error"
            :id="`${id}-error`"
            role="alert"
            class="text-sm"
            :style="{ color: 'var(--color-danger)' }"
        >
            {{ error }}
        </p>
    </div>
</template>
