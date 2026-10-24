<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type StoreShell = {
    name: string;
    slug: string;
    logoUrl: string | null;
    faviconUrl: string | null;
    footerText: string | null;
    contact: {
        email: string | null;
        phone: string | null;
        whatsapp: string | null;
    };
    social: Record<string, string>;
};

export type Seo = {
    title: string;
    description: string | null;
    image: string | null;
};

export type NavLink = { name: string; url: string };

/** What every page of the contract receives next to its own data. */
export type Shell = {
    store: StoreShell;
    customer: { name: string } | null;
    tokens: Record<string, string>;
    urls: {
        home: string;
        search: string;
        cart: string;
        login: string;
        register: string;
        forgotPassword: string;
        account: string;
        addresses: string;
    };
    actions: {
        login: string;
        register: string;
        logout: string;
        forgotPassword: string;
        resetPassword: string;
        profile: string;
        password: string;
        addresses: string;
    };
    navigation: NavLink[];
    cartCount: number;
    status: string | null;
};

defineProps<{
    seo: Seo;
    query?: string;
}>();

const page = usePage();
const shell = computed(() => page.props as unknown as Shell);
const store = computed(() => shell.value.store);

const whatsappUrl = computed(() =>
    store.value.contact.whatsapp
        ? `https://wa.me/${store.value.contact.whatsapp.replace(/\D/g, '')}`
        : null,
);

const showSocial = computed(
    () => shell.value.tokens['--theme-show-footer-social'] !== '0',
);

const focusRing =
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current';

function logout() {
    router.post(shell.value.actions.logout);
}
</script>

<template>
    <Head :title="seo.title">
        <link v-if="store.faviconUrl" rel="icon" :href="store.faviconUrl" />
        <meta
            v-if="seo.description"
            head-key="description"
            name="description"
            :content="seo.description"
        />
        <meta head-key="og:title" property="og:title" :content="seo.title" />
        <meta
            v-if="seo.description"
            head-key="og:description"
            property="og:description"
            :content="seo.description"
        />
        <meta
            v-if="seo.image"
            head-key="og:image"
            property="og:image"
            :content="seo.image"
        />
    </Head>

    <div
        dir="rtl"
        class="flex min-h-screen flex-col"
        :style="{
            ...shell.tokens,
            backgroundColor: 'var(--color-bg)',
            color: 'var(--color-text)',
            fontFamily: 'var(--font-body)',
            lineHeight: 1.7,
        }"
    >
        <header
            :style="{
                backgroundColor: 'var(--color-primary)',
                color: 'var(--color-on-primary)',
            }"
        >
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-4 py-3"
            >
                <Link
                    :href="shell.urls.home"
                    :class="['flex min-h-11 items-center gap-3', focusRing]"
                    :aria-label="`${store.name} - الصفحة الرئيسية`"
                >
                    <img
                        v-if="store.logoUrl"
                        :src="store.logoUrl"
                        :alt="store.name"
                        class="h-10 w-auto"
                    />
                    <span
                        class="text-xl font-semibold"
                        :style="{ fontFamily: 'var(--font-heading)' }"
                    >
                        {{ store.name }}
                    </span>
                </Link>

                <form
                    :action="shell.urls.search"
                    method="get"
                    role="search"
                    aria-label="البحث في المتجر"
                    class="ms-auto flex w-full max-w-sm gap-2 sm:w-auto"
                >
                    <input
                        type="search"
                        name="q"
                        :value="query ?? ''"
                        aria-label="ابحث عن منتج"
                        placeholder="ابحث عن منتج"
                        :class="[
                            'min-h-11 min-w-0 flex-1 px-3 text-sm',
                            focusRing,
                        ]"
                        :style="{
                            backgroundColor: 'var(--color-bg)',
                            color: 'var(--color-text)',
                            borderRadius: 'var(--radius-md)',
                        }"
                    />
                    <button
                        type="submit"
                        :class="[
                            'min-h-11 px-4 text-sm font-medium',
                            focusRing,
                        ]"
                        :style="{
                            backgroundColor: 'var(--color-secondary)',
                            color: 'var(--color-on-secondary)',
                            borderRadius: 'var(--radius-md)',
                        }"
                    >
                        بحث
                    </button>
                </form>

                <nav
                    aria-label="الحساب"
                    class="flex items-center gap-3 text-sm"
                    data-account-nav
                >
                    <template v-if="shell.customer">
                        <Link
                            :href="shell.urls.account"
                            :class="['flex min-h-11 items-center', focusRing]"
                            data-account-link
                            >{{ shell.customer.name }}</Link
                        >
                        <button
                            type="button"
                            :class="['flex min-h-11 items-center', focusRing]"
                            @click="logout"
                        >
                            تسجيل الخروج
                        </button>
                    </template>
                    <template v-else>
                        <Link
                            :href="shell.urls.login"
                            :class="['flex min-h-11 items-center', focusRing]"
                            data-login-link
                            >تسجيل الدخول</Link
                        >
                        <Link
                            :href="shell.urls.register"
                            :class="['flex min-h-11 items-center', focusRing]"
                            >إنشاء حساب</Link
                        >
                    </template>
                </nav>

                <a
                    :href="shell.urls.cart"
                    :class="[
                        'flex min-h-11 min-w-11 items-center gap-1',
                        focusRing,
                    ]"
                    :aria-label="`سلة التسوق، ${shell.cartCount} منتج`"
                >
                    <span aria-hidden="true">🛒</span>
                    <span data-cart-count>{{ shell.cartCount }}</span>
                </a>
            </div>

            <nav
                v-if="shell.navigation.length"
                aria-label="التصنيفات"
                class="mx-auto max-w-6xl overflow-x-auto px-4 pb-2"
            >
                <ul class="flex gap-4 text-sm">
                    <li v-for="link in shell.navigation" :key="link.url">
                        <Link
                            :href="link.url"
                            :class="['flex min-h-11 items-center', focusRing]"
                            >{{ link.name }}</Link
                        >
                    </li>
                </ul>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
            <p
                v-if="shell.status"
                role="status"
                class="mb-4 p-3 text-sm"
                :style="{
                    backgroundColor: 'var(--color-secondary)',
                    color: 'var(--color-on-secondary)',
                    borderRadius: 'var(--radius-md)',
                }"
            >
                {{ shell.status }}
            </p>
            <slot />
        </main>

        <footer
            class="space-y-2 px-4 py-6 text-sm"
            :style="{
                backgroundColor: 'var(--color-surface)',
                borderTop: '1px solid var(--color-border)',
            }"
        >
            <p v-if="store.footerText">{{ store.footerText }}</p>
            <ul class="flex flex-wrap gap-x-4 gap-y-2">
                <li v-if="store.contact.phone">
                    <a
                        :href="`tel:${store.contact.phone}`"
                        :class="[
                            'inline-flex min-h-11 items-center',
                            focusRing,
                        ]"
                        >{{ store.contact.phone }}</a
                    >
                </li>
                <li v-if="store.contact.email">
                    <a
                        :href="`mailto:${store.contact.email}`"
                        :class="[
                            'inline-flex min-h-11 items-center',
                            focusRing,
                        ]"
                        >{{ store.contact.email }}</a
                    >
                </li>
                <li v-if="whatsappUrl">
                    <a
                        :href="whatsappUrl"
                        rel="noopener"
                        :class="[
                            'inline-flex min-h-11 items-center',
                            focusRing,
                        ]"
                        >WhatsApp</a
                    >
                </li>
                <li
                    v-for="(url, network) in showSocial ? store.social : {}"
                    :key="network"
                >
                    <a
                        :href="url"
                        rel="noopener"
                        target="_blank"
                        :class="[
                            'inline-flex min-h-11 items-center',
                            focusRing,
                        ]"
                        >{{ network }}</a
                    >
                </li>
            </ul>
        </footer>
    </div>
</template>
