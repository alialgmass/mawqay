<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    stats: {
        stores: number;
        products: number;
    };
}>();

const { t } = useI18n();

// Latin digits per the design tokens; zero counts are never shown as "0 stores".
const formatter = new Intl.NumberFormat('en-US');

const items = computed(() =>
    [
        { key: 'stores', value: props.stats.stores, label: t('landing.stats.stores') },
        { key: 'products', value: props.stats.products, label: t('landing.stats.products') },
    ].filter((item) => item.value > 0),
);
</script>

<template>
    <section class="border-y border-border bg-muted">
        <div
            class="mx-auto flex w-full max-w-7xl flex-col items-start justify-between gap-6 px-4 py-10 sm:flex-row sm:items-center sm:px-6 lg:px-8"
        >
            <dl v-if="items.length > 0" class="flex flex-wrap items-center gap-x-10 gap-y-4">
                <div v-for="item in items" :key="item.key" class="flex flex-col">
                    <dd class="order-1 text-3xl font-bold tabular-nums text-primary sm:text-4xl" dir="ltr">
                        {{ formatter.format(item.value) }}+
                    </dd>
                    <dt class="order-2 text-sm text-muted-foreground">{{ item.label }}</dt>
                </div>
            </dl>

            <p class="max-w-md text-sm text-muted-foreground">
                {{ t('landing.stats.lead') }}
            </p>
        </div>
    </section>
</template>
