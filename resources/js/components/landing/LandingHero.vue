<script setup lang="ts">
import { ArrowRight, BadgeCheck, ShoppingBag } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const scrollTo = (id: string): void => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
};

const products = [
    { key: 'abaya', price: '1,260', tone: 'bg-secondary' },
    { key: 'scarf', price: '240', tone: 'bg-muted' },
    { key: 'set', price: '890', tone: 'bg-secondary/60' },
    { key: 'dress', price: '1,050', tone: 'bg-muted' },
];
</script>

<template>
    <section class="relative overflow-hidden" id="hero">
        <div
            class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(60rem_40rem_at_85%_-10%,var(--brand-secondary),transparent),radial-gradient(40rem_30rem_at_5%_0%,var(--brand-surface),transparent)]"
            aria-hidden="true"
        />

        <div
            class="mx-auto grid w-full max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-24"
        >
            <div class="flex flex-col items-start gap-6">
                <Badge variant="secondary" class="rise gap-1.5 px-3 py-1 text-xs font-medium">
                    <span class="h-1.5 w-1.5 rounded-full bg-[var(--brand-success)]" aria-hidden="true" />
                    {{ $t('landing.hero.badge') }}
                </Badge>

                <h1 style="--i: 1" class="rise text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                    <span class="shimmer-text">{{ $t('landing.hero.title') }}</span>
                </h1>

                <p style="--i: 2" class="rise max-w-xl text-lg text-muted-foreground text-pretty">
                    {{ $t('landing.hero.subtitle') }}
                </p>

                <div style="--i: 3" class="rise flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                    <Button size="lg" class="h-12 px-6 text-base" @click="scrollTo('register')">
                        {{ $t('landing.hero.ctaPrimary') }}
                        <ArrowRight class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                    </Button>
                    <Button size="lg" variant="outline" class="h-12 px-6 text-base" @click="scrollTo('how')">
                        {{ $t('landing.hero.ctaSecondary') }}
                    </Button>
                </div>

                <p style="--i: 4" class="rise flex items-center gap-2 text-sm text-muted-foreground">
                    <BadgeCheck class="h-4 w-4 text-[var(--brand-success)]" aria-hidden="true" />
                    {{ $t('landing.hero.note') }}
                </p>
            </div>

            <!-- Storefront preview: decorative, token-driven, mirrors in RTL -->
            <div style="--i: 2" class="rise float-slow relative mx-auto w-full max-w-md" aria-hidden="true">
                <div class="absolute -top-6 -start-6 h-40 w-40 rounded-3xl bg-secondary" />
                <div class="absolute -bottom-8 -end-4 h-32 w-32 rounded-full bg-primary/10" />

                <div class="relative rounded-3xl border border-border bg-card p-4 shadow-xl shadow-primary/10">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <ShoppingBag class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold leading-none">{{ $t('landing.hero.mock.store') }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ $t('landing.hero.mock.tagline') }}</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-secondary px-2.5 py-1 text-xs font-medium text-secondary-foreground">
                            {{ $t('landing.hero.mock.cart') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 rounded-2xl bg-muted p-3">
                        <div
                            v-for="(product, index) in products"
                            :key="product.key"
                            :style="`--i: ${index}`"
                            class="pop-in rounded-xl bg-card shadow-sm"
                        >
                            <div class="aspect-[4/5] rounded-t-xl" :class="product.tone" />
                            <div class="p-2.5">
                                <p class="truncate text-xs font-medium">
                                    {{ $t(`landing.hero.mock.products.${product.key}`) }}
                                </p>
                                <p class="mt-0.5 text-xs tabular-nums text-primary">
                                    <span dir="ltr">{{ product.price }}</span>
                                    {{ $t('landing.hero.mock.currency') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex h-11 items-center justify-center rounded-xl bg-primary text-sm font-medium text-primary-foreground">
                        {{ $t('landing.hero.mock.checkout') }}
                    </div>
                </div>

                <div class="pop-in float-slow-alt absolute -start-4 top-24 rounded-2xl border border-border bg-card px-3 py-2 text-xs shadow-lg shadow-primary/10">
                    <span class="flex items-center gap-2 font-medium">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[var(--brand-success)]/15">
                            <span class="h-2 w-2 rounded-full bg-[var(--brand-success)]" />
                        </span>
                        {{ $t('landing.hero.mock.paid') }}
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>
