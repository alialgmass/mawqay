<script setup lang="ts">
import { PackageOpen } from 'lucide-vue-next';
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        icon?: Component;
        title: string;
        description?: string;
    }>(),
    {
        icon: undefined,
        description: undefined,
    },
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed px-6 py-16 text-center"
    >
        <div class="flex size-12 items-center justify-center rounded-full bg-muted">
            <component :is="icon ?? PackageOpen" class="size-6 text-muted-foreground" />
        </div>
        <div>
            <p class="font-medium">{{ title }}</p>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <slot />
    </div>
</template>
