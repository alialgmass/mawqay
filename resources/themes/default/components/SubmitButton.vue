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
        class="min-h-11 px-5 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-60"
        :style="
            variant === 'primary'
                ? {
                      backgroundColor: 'var(--color-primary)',
                      color: 'var(--color-on-primary)',
                      borderRadius: 'var(--radius-md)',
                  }
                : {
                      border: '1px solid var(--color-border)',
                      borderRadius: 'var(--radius-md)',
                  }
        "
    >
        <slot />
    </button>
</template>
