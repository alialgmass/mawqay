<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="truncate text-2xl font-bold tracking-tight">{{ title }}</h1>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
