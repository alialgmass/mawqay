<script setup lang="ts">
import { Star } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const keys = ['one', 'two', 'three'] as const;

const { t } = useI18n();

const items = computed(() =>
    keys.map((key) => ({
        key,
        quote: t(`landing.testimonials.items.${key}.quote`),
        name: t(`landing.testimonials.items.${key}.name`),
        meta: t(`landing.testimonials.items.${key}.meta`),
    })),
);

const initials = (name: string): string => name.slice(0, 1).toUpperCase();
</script>

<template>
    <section class="bg-muted py-16 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="mb-12 text-center text-3xl font-bold tracking-tight sm:text-4xl">
                {{ t('landing.testimonials.title') }}
            </h2>

            <figure
                v-for="item in items"
                :key="item.key"
                class="reveal mx-auto mb-4 max-w-2xl rounded-2xl border border-border bg-card px-6 py-6 text-center last:mb-0 md:px-10"
            >
                <div class="mb-3 flex justify-center gap-0.5" aria-hidden="true">
                    <Star v-for="star in 5" :key="star" class="h-4 w-4 fill-[var(--brand-warning)] text-[var(--brand-warning)]" />
                </div>
                <blockquote class="text-base text-balance md:text-lg">
                    “{{ item.quote }}”
                </blockquote>
                <figcaption class="mt-4 flex items-center justify-center gap-3 text-sm">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary text-primary"
                        aria-hidden="true"
                    >
                        {{ initials(item.name) }}
                    </span>
                    <span class="text-start">
                        <span class="block font-medium">{{ item.name }}</span>
                        <span class="block text-muted-foreground">{{ item.meta }}</span>
                    </span>
                </figcaption>
            </figure>
        </div>
    </section>
</template>
