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
        checkout: string;
        orders: string;
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
        cartItems: string;
        acceptPrices: string;
        checkout: string;
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

const navLink = [
    'flex min-h-11 items-center rounded-[var(--radius-sm)] px-3 transition-colors duration-200 hover:bg-[var(--color-secondary)]',
    focusRing,
].join(' ');

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
            class="sticky top-0 z-30"
            :style="{
                backgroundColor: 'var(--color-bg)',
                color: 'var(--color-text)',
                boxShadow: '0 1px 3px rgba(5,5,5,0.10)',
            }"
        >
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3"
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
                        class="text-xl font-bold"
                        :style="{
                            fontFamily: 'var(--font-heading)',
                            color: 'var(--color-primary)',
                        }"
                    >
                        {{ store.name }}
                    </span>
                </Link>

                <nav
                    aria-label="الحساب"
                    class="ms-auto flex items-center gap-1 text-sm font-semibold sm:order-3 sm:ms-0"
                    data-account-nav
                >
                    <template v-if="shell.customer">
                        <Link
                            :href="shell.urls.account"
                            :class="[navLink, 'max-w-28 truncate']"
                            data-account-link
                            >{{ shell.customer.name }}</Link
                        >
                        <Link
                            :href="shell.urls.orders"
                            :class="[navLink, 'hidden sm:flex']"
                            >طلباتي</Link
                        >
                        <button
                            type="button"
                            :class="[navLink, 'hidden sm:flex']"
                            @click="logout"
                        >
                            تسجيل الخروج
                        </button>
                    </template>
                    <template v-else>
                        <Link
                            :href="shell.urls.login"
                            :class="navLink"
                            data-login-link
                            >تسجيل الدخول</Link
                        >
                        <Link
                            :href="shell.urls.register"
                            :class="[navLink, 'hidden sm:flex']"
                            >إنشاء حساب</Link
                        >
                    </template>
                    <a
                        :href="shell.urls.cart"
                        :class="[
                            'relative flex min-h-11 min-w-11 items-center justify-center rounded-full transition-colors duration-200 hover:bg-[var(--color-secondary)]',
                            focusRing,
                        ]"
                        :aria-label="`سلة التسوق، ${shell.cartCount} منتج`"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6" />
                            <circle cx="9.5" cy="20" r="1.2" />
                            <circle cx="17" cy="20" r="1.2" />
                        </svg>
                        <span
                            data-cart-count
                            class="absolute -top-0.5 -end-0.5 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-xs font-bold"
                            :style="{
                                backgroundColor: 'var(--color-primary)',
                                color: 'var(--color-on-primary)',
                            }"
                            >{{ shell.cartCount }}</span
                        >
                    </a>
                </nav>

                <form
                    :action="shell.urls.search"
                    method="get"
                    role="search"
                    aria-label="البحث في المتجر"
                    class="order-last flex w-full gap-2 sm:order-2 sm:ms-auto sm:w-auto sm:max-w-sm sm:flex-1"
                >
                    <input
                        type="search"
                        name="q"
                        :value="query ?? ''"
                        aria-label="ابحث عن منتج"
                        placeholder="ابحث عن منتج"
                        :class="[
                            'min-h-11 min-w-0 flex-1 px-4 text-sm',
                            focusRing,
                        ]"
                        :style="{
                            backgroundColor: 'var(--color-surface)',
                            color: 'var(--color-text)',
                            border: '1px solid var(--color-border)',
                            borderRadius: 'var(--radius-sm)',
                        }"
                    />
                    <button
                        type="submit"
                        :class="[
                            'min-h-11 px-4 text-sm font-semibold',
                            focusRing,
                        ]"
                        :style="{
                            backgroundColor: 'var(--color-primary)',
                            color: 'var(--color-on-primary)',
                            borderRadius: 'var(--radius-sm)',
                        }"
                    >
                        بحث
                    </button>
                </form>
            </div>

            <nav
                v-if="shell.navigation.length"
                aria-label="التصنيفات"
                class="overflow-x-auto"
                :style="{ borderTop: '1px solid var(--color-border)' }"
            >
                <ul class="mx-auto flex max-w-6xl gap-1 px-4 text-sm font-semibold">
                    <li v-for="link in shell.navigation" :key="link.url">
                        <Link :href="link.url" :class="[navLink, 'whitespace-nowrap']">{{
                            link.name
                        }}</Link>
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
                    color: 'var(--color-text)',
                    borderRadius: 'var(--radius-lg)',
                }"
            >
                {{ shell.status }}
            </p>
            <slot />
        </main>

        <footer
            class="mt-8 px-4 py-8 text-sm"
            :style="{
                backgroundColor: 'var(--color-surface)',
                borderTop: '1px solid var(--color-border)',
            }"
        >
            <div class="mx-auto max-w-6xl space-y-3">
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
        </div>
        </footer>
    </div>
</template>
