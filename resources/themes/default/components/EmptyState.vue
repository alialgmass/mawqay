<script setup lang="ts">
defineProps<{ message: string }>();
</script>

<template>
    <div
        class="rounded-md p-10 text-center"
        :style="{
            border: '1px dashed var(--color-border)',
            color: 'var(--color-text-muted)',
        }"
    >
        {{ message }}
    </div>
</template>
