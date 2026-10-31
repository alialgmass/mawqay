<script setup lang="ts">
import { ArrowRight, Banknote, Landmark, Store  } from 'lucide-vue-next';
import type {LucideIcon} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const steps: { key: string; icon: LucideIcon }[] = [
    { key: 'pay', icon: Banknote },
    { key: 'commission', icon: Landmark },
    { key: 'payout', icon: Store },
];

const { t } = useI18n();

const items = computed(() =>
    steps.map((step) => ({
        ...step,
        title: t(`landing.money.items.${step.key}.title`),
        description: t(`landing.money.items.${step.key}.description`),
    })),
);
</script>

<template>
    <section class="py-16 lg:py-24" id="money">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ t('landing.money.title') }}
                </h2>
                <p class="mt-3 text-muted-foreground">
                    {{ t('landing.money.subtitle') }}
                </p>
            </div>

            <ol class="mx-auto grid max-w-5xl items-stretch gap-4 md:grid-cols-[1fr_auto_1fr_auto_1fr]">
                <template v-for="(item, index) in items" :key="item.key">
                    <li class="reveal lift wiggle flex flex-col gap-3 rounded-2xl border border-border bg-card p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-secondary text-primary">
                            <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <h3 class="text-lg font-semibold">{{ item.title }}</h3>
                        <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                    </li>
                    <li
                        v-if="index < items.length - 1"
                        class="hidden items-center justify-center text-muted-foreground md:flex"
                        aria-hidden="true"
                    >
                        <ArrowRight class="h-5 w-5 rtl:rotate-180" />
                    </li>
                </template>
            </ol>
        </div>
    </section>
</template>
