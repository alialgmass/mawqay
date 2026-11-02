<script setup lang="ts">
withDefaults(
    defineProps<{ processing?: boolean; variant?: 'primary' | 'plain' }>(),
    { processing: false, variant: 'primary' },
);
</script>

<template>
    <button
        type="submit"
        :disabled="processing"
        class="min-h-11 px-5 text-sm font-semibold transition-all duration-200 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-60"
        :style="
            variant === 'primary'
                ? {
                      backgroundColor: 'var(--color-primary)',
                      color: 'var(--color-on-primary)',
                      borderRadius: 'var(--radius-sm)',
                  }
                : {
                      border: '1px solid var(--color-border)',
                      borderRadius: 'var(--radius-sm)',
                  }
        "
    >
        <slot />
    </button>
</template>
