<script setup lang="ts">
import EmptyState from '../components/EmptyState.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{
    seo: Seo;
    ready: boolean;
}>();
</script>

<template>
    <StoreLayout :seo="seo">
        <h2
            class="mb-4 text-lg font-semibold"
            :style="{ fontFamily: 'var(--font-heading)' }"
        >
            إتمام الطلب
        </h2>
        <EmptyState
            :message="
                ready ? 'جاهز لإتمام الطلب' : 'إتمام الطلب غير متاح حاليًا'
            "
        />
    </StoreLayout>
</template>
