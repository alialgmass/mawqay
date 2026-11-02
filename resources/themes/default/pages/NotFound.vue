<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
}>();

const shell = useShell();
</script>

<template>
    <StoreLayout :seo="seo">
        <div class="mx-auto max-w-md space-y-4 py-10 text-center">
            <p
                class="text-6xl font-bold"
                :style="{ color: 'var(--color-primary)', fontFamily: 'var(--font-heading)' }"
            >
                404
            </p>
            <h2
                class="text-xl font-semibold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                الصفحة غير موجودة
            </h2>
            <p :style="{ color: 'var(--color-text-muted)' }">
                تعذّر العثور على الصفحة التي تبحثين عنها
            </p>
            <Link
                :href="shell.urls.home"
                class="inline-flex min-h-11 items-center px-6 text-sm font-semibold transition-all duration-200 hover:shadow-md"
                :style="{
                    backgroundColor: 'var(--color-primary)',
                    color: 'var(--color-on-primary)',
                    borderRadius: 'var(--radius-sm)',
                }"
            >
                العودة للرئيسية
            </Link>
        </div>
    </StoreLayout>
</template>
