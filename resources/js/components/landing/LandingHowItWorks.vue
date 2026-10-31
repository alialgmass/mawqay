<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const steps = ['one', 'two', 'three'] as const;

const { t } = useI18n();

const items = computed(() =>
    steps.map((step, index) => ({
        step,
        index: index + 1,
        title: t(`landing.how.items.${step}.title`),
        description: t(`landing.how.items.${step}.description`),
    })),
);
</script>

<template>
    <section class="border-y border-border bg-muted py-16 lg:py-24" id="how">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="mb-12 text-center text-3xl font-bold tracking-tight sm:text-4xl">
                {{ t('landing.how.title') }}
            </h2>

            <ol class="mx-auto grid max-w-4xl gap-10 sm:grid-cols-3 sm:gap-6">
                <li v-for="item in items" :key="item.step" class="reveal lift relative flex flex-col items-center text-center">
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground shadow-md shadow-primary/20"
                        aria-hidden="true"
                    >
                        {{ item.index }}
                    </span>
                    <span
                        v-if="item.index < items.length"
                        class="absolute top-6 hidden h-px w-[calc(100%-3rem)] border-t border-dashed border-border sm:block ltr:left-[calc(50%+3rem)] rtl:right-[calc(50%+3rem)]"
                        aria-hidden="true"
                    />
                    <h3 class="mt-4 text-lg font-medium">{{ item.title }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ item.description }}
                    </p>
                </li>
            </ol>
        </div>
    </section>
</template>
