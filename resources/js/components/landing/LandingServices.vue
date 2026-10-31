<script setup lang="ts">
import {
    Banknote,
    LayoutGrid,
    MessageSquareHeart,
    NotebookText,
    Paintbrush,
    Truck
    
} from 'lucide-vue-next';
import type {LucideIcon} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Service = {
    icon: LucideIcon;
    key: string;
};

const services: Service[] = [
    { icon: LayoutGrid, key: 'storefront' },
    { icon: Paintbrush, key: 'themes' },
    { icon: Banknote, key: 'payments' },
    { icon: Truck, key: 'shipping' },
    { icon: NotebookText, key: 'ledger' },
    { icon: MessageSquareHeart, key: 'feedback' },
];

const { t } = useI18n();

const items = computed(() =>
    services.map((service) => ({
        ...service,
        title: t(`landing.services.items.${service.key}.title`),
        description: t(`landing.services.items.${service.key}.description`),
    })),
);
</script>

<template>
    <section class="py-16 lg:py-24" id="services">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ t('landing.services.title') }}
                </h2>
                <p class="mt-3 text-muted-foreground">
                    {{ t('landing.services.subtitle') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="item in items"
                    :key="item.key"
                    class="reveal lift wiggle gap-3 py-6 hover:shadow-lg hover:shadow-primary/10"
                >
                    <CardHeader class="px-6">
                        <div
                            class="mb-2 flex h-10 w-10 items-center justify-center rounded-xl bg-secondary text-primary"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                        </div>
                        <CardTitle class="text-base font-medium">
                            {{ item.title }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-6">
                        <p class="text-sm text-muted-foreground">
                            {{ item.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </section>
</template>
