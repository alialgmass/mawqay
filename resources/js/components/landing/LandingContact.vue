<script setup lang="ts">
import { Clock, Mail } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
</script>

<template>
    <section class="py-16 lg:py-24" id="contact">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ $t('landing.contact.title') }}
                </h2>
                <p class="mt-3 text-muted-foreground">
                    {{ $t('landing.contact.subtitle') }}
                </p>
            </div>

            <Card class="mx-auto max-w-xl py-8">
                <CardContent class="flex flex-col items-center gap-5 px-6 text-center">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-primary"
                    >
                        <Mail class="h-5 w-5" />
                    </div>

                    <div class="w-full rounded-xl border border-border bg-muted px-6 py-4">
                        <span class="block text-sm text-muted-foreground">
                            {{ $t('landing.contact.emailLabel') }}
                        </span>
                        <a
                            :href="`mailto:${$t('landing.contact.email')}`"
                            dir="ltr"
                            class="inline-block text-base font-medium underline-offset-4 hover:underline"
                        >
                            {{ $t('landing.contact.email') }}
                        </a>
                    </div>

                    <p class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Clock class="h-4 w-4 shrink-0" />
                        {{ $t('landing.contact.hours') }}
                    </p>

                    <Button variant="secondary" as-child class="text-sm">
                        <a :href="`mailto:${$t('landing.contact.email')}`">
                            {{ $t('landing.contact.emailLabel') }}
                        </a>
                    </Button>

                    <p class="text-xs text-muted-foreground">
                        {{ $t('landing.contact.response') }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </section>
</template>
