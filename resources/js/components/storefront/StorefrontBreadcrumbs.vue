<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

export type Crumb = { name: string; url: string | null };

defineProps<{ items: Crumb[] }>();
</script>

<template>
    <nav aria-label="breadcrumb" class="mb-4 text-sm">
        <ol class="flex flex-wrap items-center gap-2">
            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-2"
            >
                <Link v-if="item.url" :href="item.url" class="underline">{{
                    item.name
                }}</Link>
                <span v-else aria-current="page">{{ item.name }}</span>
                <span v-if="index < items.length - 1" aria-hidden="true"
                    >/</span
                >
            </li>
        </ol>
    </nav>
</template>
