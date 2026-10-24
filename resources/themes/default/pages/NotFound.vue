<script setup lang="ts">
import EmptyState from '../components/EmptyState.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
}>();
</script>

<template>
    <StoreLayout :seo="seo">
        <h2
            class="mb-4 text-lg font-semibold"
            :style="{ fontFamily: 'var(--font-heading)' }"
        >
            الصفحة غير موجودة
        </h2>
        <EmptyState message="تعذّر العثور على الصفحة التي تبحثين عنها" />
    </StoreLayout>
</template>
