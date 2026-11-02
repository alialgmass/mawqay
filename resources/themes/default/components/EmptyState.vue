<script setup lang="ts">
defineProps<{ message: string }>();
</script>

<template>
    <div
        class="p-10 text-center"
        :style="{
            border: '1px dashed var(--color-border)',
            borderRadius: 'var(--radius-lg)',
            backgroundColor: 'var(--color-surface)',
            color: 'var(--color-text-muted)',
        }"
    >
        {{ message }}
    </div>
</template>
