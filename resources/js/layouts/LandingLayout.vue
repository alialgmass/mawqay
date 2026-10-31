<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useDirection } from '@/composables/useDirection';
import { login } from '@/routes';
import { index as blogIndex } from '@/routes/blog';

const { t } = useI18n();
const { direction } = useDirection();

const mobileOpen = ref(false);

const mobileSide = computed(() => (direction.value === 'rtl' ? 'left' : 'right'));

const anchorItems = computed<{ href: string; label: string }[]>(() => [
    { href: '#services', label: t('landing.nav.services') },
    { href: '#how', label: t('landing.nav.how') },
    { href: '#money', label: t('landing.nav.money') },
    { href: '#about', label: t('landing.nav.about') },
    { href: '#contact', label: t('landing.nav.contact') },
    { href: '#faq', label: t('landing.nav.faq') },
]);

const activeSection = ref('');
let observer: IntersectionObserver | null = null;

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            const visible = entries.find((entry) => entry.isIntersecting);

            if (visible) {
                activeSection.value = visible.target.id;
            }
        },
        { rootMargin: '-40% 0px -55% 0px' },
    );

    anchorItems.value.forEach((item) => {
        const element = document.getElementById(item.href.slice(1));

        if (element) {
            observer?.observe(element);
        }
    });
});

onBeforeUnmount(() => observer?.disconnect());

const scrollToRegister = (): void => {
    mobileOpen.value = false;

    document.getElementById('register')?.scrollIntoView({ behavior: 'smooth' });
};

const closeMobile = (): void => {
    mobileOpen.value = false;
};
</script>

<template>
    <div class="public-site flex min-h-screen flex-col bg-background">
        <header class="sticky top-0 z-40 border-b border-border/70 bg-background/85 backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-3" aria-label="Mawqaay">
                    <AppLogoIcon class="h-9 w-9 text-primary" />
                    <span class="flex flex-col leading-none">
                        <span class="text-lg font-semibold tracking-tight">
                            {{ t('landing.brand') }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ t('landing.tagline') }}
                        </span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-8 text-sm lg:flex" aria-label="Main">
                    <a
                        v-for="item in anchorItems"
                        :key="item.href"
                        :href="item.href"
                        class="py-2 text-muted-foreground transition-colors hover:text-foreground"
                        :class="{ 'font-medium text-primary': activeSection === item.href.slice(1) }"
                        :aria-current="activeSection === item.href.slice(1) ? 'true' : undefined"
                    >
                        {{ item.label }}
                    </a>
                    <Link
                        :href="blogIndex()"
                        class="text-muted-foreground transition-colors hover:text-foreground"
                    >
                        {{ t('landing.nav.blog') }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <LocaleSwitcher />
                    <TextLink
                        :href="login()"
                        class="hidden text-foreground hover:text-foreground/80 sm:inline-flex sm:px-3"
                    >
                        {{ t('landing.cta.login') }}
                    </TextLink>
                    <Button size="sm" @click="scrollToRegister">
                        {{ t('landing.cta.start') }}
                    </Button>

                    <Sheet v-model:open="mobileOpen">
                        <SheetTrigger as-child>
                            <Button variant="ghost" size="icon" class="lg:hidden">
                                <Menu class="h-5 w-5" />
                                <span class="sr-only">{{ t('landing.a11y.openMenu') }}</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent :side="mobileSide" class="w-72">
                            <SheetHeader>
                                <SheetTitle>{{ t('landing.brand') }}</SheetTitle>
                                <SheetDescription>{{ t('landing.tagline') }}</SheetDescription>
                            </SheetHeader>
                            <div class="flex flex-col gap-1 px-4 pb-6">
                                <a
                                    v-for="item in anchorItems"
                                    :key="item.href"
                                    :href="item.href"
                                    class="rounded-md px-2 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                                    @click="closeMobile"
                                >
                                    {{ item.label }}
                                </a>
                                <Link
                                    :href="blogIndex()"
                                    class="rounded-md px-2 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                                    @click="closeMobile"
                                >
                                    {{ t('landing.nav.blog') }}
                                </Link>
                                <TextLink
                                    :href="login()"
                                    class="mt-2 rounded-md px-2 py-2 text-sm font-medium"
                                    @click="closeMobile"
                                >
                                    {{ t('landing.cta.login') }}
                                </TextLink>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <LandingFooter />
    </div>
</template>
