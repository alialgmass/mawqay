<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { login } from '@/routes';
import { index as blogIndex } from '@/routes/blog';

const { t } = useI18n();

const year = new Date().getFullYear();

const links = computed(() => [
    { href: '#services', label: t('landing.nav.services') },
    { href: '#how', label: t('landing.nav.how') },
    { href: '#about', label: t('landing.nav.about') },
    { href: '#contact', label: t('landing.nav.contact') },
    { href: '#faq', label: t('landing.nav.faq') },
    { href: blogIndex().url, label: t('landing.footer.blog') },
    { href: '/demo-store', label: t('landing.footer.demo') },
    { href: login().url, label: t('landing.cta.login') },
]);
</script>

<template>
    <footer class="border-t border-border bg-muted">
        <div class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
                <div class="flex items-start gap-3">
                    <AppLogoIcon class="h-9 w-9 text-primary" />
                    <div>
                        <p class="text-lg font-semibold tracking-tight">
                            {{ t('landing.brand') }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ t('landing.tagline') }}
                        </p>
                    </div>
                </div>

                <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground" aria-label="Footer">
                    <Link v-for="item in links" :key="item.href" :href="item.href" class="hover:text-foreground">
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-3">
                    <LocaleSwitcher />
                    <Link
                        href="/admin"
                        class="text-sm text-muted-foreground hover:text-foreground"
                    >
                        {{ t('landing.cta.adminPanel') }}
                    </Link>
                </div>
            </div>

            <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-border pt-6 text-xs text-muted-foreground sm:flex-row">
                <p>© {{ year }} {{ t('landing.brand') }}. {{ t('landing.footer.rights') }}</p>
                <p>{{ t('landing.footer.madeFor') }}</p>
            </div>
        </div>
    </footer>
</template>
