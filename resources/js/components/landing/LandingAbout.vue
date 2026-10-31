<script setup lang="ts">
import { HeartHandshake, Sparkles, Users  } from 'lucide-vue-next';
import type {LucideIcon} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type Point = {
    key: string;
    icon: LucideIcon;
};

const points: Point[] = [
    { key: 'craft', icon: Sparkles },
    { key: 'values', icon: HeartHandshake },
    { key: 'support', icon: Users },
];

const { t } = useI18n();

const items = computed(() =>
    points.map((point) => ({
        ...point,
        title: t(`landing.about.points.${point.key}.title`),
        description: t(`landing.about.points.${point.key}.description`),
    })),
);

const paragraphs = computed<string[]>(() =>
    t('landing.about.body', 1) === 'landing.about.body'
        ? []
        : (t('landing.about.body') as unknown as string[]),
);
</script>

<template>
    <section class="py-16 lg:py-24" id="about">
        <div class="mx-auto grid w-full max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ t('landing.about.title') }}
                </h2>
                <p class="mt-4 text-lg font-medium text-pretty">
                    {{ t('landing.about.lead') }}
                </p>
                <div class="mt-6 flex max-w-xl flex-col gap-4 text-muted-foreground">
                    <p v-for="(paragraph, index) in paragraphs" :key="index">
                        {{ paragraph }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div
                    v-for="item in items"
                    :key="item.key"
                    class="flex gap-4 reveal lift wiggle rounded-2xl border border-border bg-card p-6"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary text-primary"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="font-medium">{{ item.title }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ item.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
