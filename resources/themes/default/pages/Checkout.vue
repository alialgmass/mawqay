<script setup lang="ts">
import EmptyState from '../components/EmptyState.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type {
    NavLink,
    Seo,
    ShellUrls,
    StoreShell,
} from '../layouts/StoreLayout.vue';

defineProps<{
    store: StoreShell;
    tokens: Record<string, string>;
    seo: Seo;
    urls: ShellUrls;
    navigation: NavLink[];
    cartCount: number;
    ready: boolean;
}>();
</script>

<template>
    <StoreLayout
        :store="store"
        :tokens="tokens"
        :seo="seo"
        :urls="urls"
        :navigation="navigation"
        :cart-count="cartCount"
    >
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
