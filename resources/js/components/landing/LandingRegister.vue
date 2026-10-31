<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ListChecks, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useDirection } from '@/composables/useDirection';
import { store } from '@/routes/register';

const { t, locale } = useI18n();
const { direction } = useDirection();

const page = usePage();

// The slug is Latin-only: force LTR input direction in both locales.
const fieldDir = computed(() => 'ltr');
const isRtl = computed(() => direction.value === 'rtl');

const storeHost = typeof window !== 'undefined' ? window.location.host : 'mawqaay.test';

const slugValue = ref('');

const slugPreview = computed(() => `${storeHost}/${slugValue.value.trim() || 'your-store'}`);

const status = computed<string | undefined>(() => page.props.status as string | undefined);
</script>

<template>
    <section class="border-t border-border bg-muted py-16 lg:py-24" id="register">
        <div class="mx-auto grid w-full max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-start lg:px-8">
            <div>
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ t('landing.register.title') }}
                </h2>
                <p class="mt-3 max-w-xl text-muted-foreground">
                    {{ t('landing.register.subtitle') }}
                </p>

                <ul class="mt-8 flex flex-col gap-4 text-sm">
                    <li class="flex items-start gap-3">
                        <Sparkles class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                        {{ t('landing.about.points.craft.description') }}
                    </li>
                    <li class="flex items-start gap-3">
                        <ListChecks class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                        {{ t('landing.how.items.two.description') }}
                    </li>
                </ul>
            </div>

            <Card class="py-6">
                <CardContent class="px-6">
                    <div
                        v-if="status"
                        class="mb-6 flex items-start gap-2 rounded-lg bg-[var(--brand-success)]/10 p-3 text-sm font-medium text-[var(--brand-success)]"
                        data-test="registration-status"
                    >
                        <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>
                            <span class="block font-semibold">
                                {{ t('landing.register.successTitle') }}
                            </span>
                            {{ status }}
                        </span>
                    </div>

                    <Form v-bind="store.form()" v-slot="{ errors, processing }">
                        <div class="flex flex-col gap-5">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="store_name">
                                        {{ t('landing.register.storeName') }}
                                    </Label>
                                    <Input
                                        id="store_name"
                                        name="store_name"
                                        required
                                        autocomplete="organization"
                                        :dir="isRtl ? 'rtl' : 'ltr'"
                                    />
                                    <InputError :message="errors.store_name" />
                                </div>

                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="store_slug">
                                        {{ t('landing.register.storeSlug') }}
                                    </Label>
                                    <div
                                        dir="ltr"
                                        class="flex overflow-hidden rounded-md border border-input bg-transparent shadow-xs"
                                    >
                                        <span
                                            class="flex select-none items-center bg-muted px-3 text-sm text-muted-foreground"
                                            aria-hidden="true"
                                        >
                                            /{{ ' ' }}
                                        </span>
                                        <Input
                                            id="store_slug"
                                            name="store_slug"
                                            required
                                            class="rounded-none border-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0"
                                            :dir="fieldDir"
                                            :placeholder="`your-wardrobe.${storeHost}`"
                                            v-model="slugValue"
                                            :aria-describedby="`store-slug-hint-${locale === 'ar' ? 'ar' : 'en'}`"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground" dir="ltr" data-test="slug-preview">
                                        {{ slugPreview }}
                                    </p>
                                    <InputError :message="errors.store_slug" />
                                    <span :id="`store-slug-hint-${locale === 'ar' ? 'ar' : 'en'}`" class="text-xs text-muted-foreground">
                                        {{ t('landing.register.slugHint') }}
                                    </span>
                                </div>

                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="owner_name">
                                        {{ t('landing.register.ownerName') }}
                                    </Label>
                                    <Input
                                        id="owner_name"
                                        name="owner_name"
                                        required
                                        autocomplete="name"
                                    />
                                    <InputError :message="errors.owner_name" />
                                </div>

                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="owner_email">
                                        {{ t('landing.register.email') }}
                                    </Label>
                                    <Input
                                        id="owner_email"
                                        name="owner_email"
                                        type="email"
                                        required
                                        autocomplete="email"
                                        dir="ltr"
                                    />
                                    <InputError :message="errors.owner_email" />
                                </div>

                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="owner_phone">
                                        {{ t('landing.register.phone') }}
                                    </Label>
                                    <Input
                                        id="owner_phone"
                                        name="owner_phone"
                                        type="tel"
                                        autocomplete="tel"
                                        dir="ltr"
                                    />
                                    <InputError :message="errors.owner_phone" />
                                </div>
                            </div>

                            <!-- Honeypot: real customers never see or fill it -->
                            <div class="absolute size-0 overflow-hidden" aria-hidden="true">
                                <label>
                                    Website
                                    <input
                                        type="text"
                                        name="website"
                                        tabindex="-1"
                                        autocomplete="off"
                                    />
                                </label>
                            </div>

                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="processing"
                                data-test="register-submit"
                            >
                                <Spinner v-if="processing" class="me-2" />
                                {{ processing ? t('landing.register.submitting') : t('landing.register.submit') }}
                            </Button>

                            <p class="text-center text-xs text-muted-foreground">
                                {{ t('landing.register.privacy') }}
                            </p>
                        </div>
                    </Form>
                </CardContent>
            </Card>
        </div>
    </section>
</template>
