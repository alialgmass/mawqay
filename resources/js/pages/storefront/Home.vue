<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    store: { name: string; slug: string };
    tokens: Record<string, string>;
}>();

const style = computed(() => props.tokens);
</script>

<template>
    <Head :title="store.name" />

    <div
        dir="rtl"
        class="min-h-screen"
        :style="{
            ...style,
            backgroundColor: 'var(--color-bg)',
            color: 'var(--color-text)',
            fontFamily: 'var(--font-body)',
        }"
    >
        <header
            class="border-b px-4 py-4"
            :style="{ borderColor: 'var(--color-border)', backgroundColor: 'var(--color-surface)' }"
        >
            <h1
                class="text-xl font-semibold"
                :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-primary)' }"
            >
                {{ store.name }}
            </h1>
        </header>
    </div>
</template>
