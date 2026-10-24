<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { home, search } from '@/routes/storefront';

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

const props = defineProps<{
    store: StoreShell;
    tokens: Record<string, string>;
    seo: Seo;
    query?: string;
}>();

const whatsappUrl = computed(() =>
    props.store.contact.whatsapp
        ? `https://wa.me/${props.store.contact.whatsapp.replace(/\D/g, '')}`
        : null,
);
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
            ...tokens,
            backgroundColor: 'var(--color-bg)',
            color: 'var(--color-text)',
            fontFamily: 'var(--font-body)',
        }"
    >
        <header
            class="flex flex-wrap items-center gap-3 px-4 py-4"
            :style="{
                backgroundColor: 'var(--color-primary)',
                color: 'var(--color-on-primary)',
            }"
        >
            <Link
                :href="home({ store: store.slug })"
                class="flex items-center gap-3"
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
                :action="search.url({ store: store.slug })"
                method="get"
                role="search"
                class="ms-auto flex w-full max-w-sm gap-2 sm:w-auto"
            >
                <input
                    type="search"
                    name="q"
                    :value="query ?? ''"
                    placeholder="ابحث عن منتج"
                    class="min-w-0 flex-1 rounded-md px-3 py-2 text-sm"
                    :style="{
                        backgroundColor: 'var(--color-bg)',
                        color: 'var(--color-text)',
                    }"
                />
                <button
                    type="submit"
                    class="rounded-md px-3 py-2 text-sm font-medium"
                    :style="{
                        backgroundColor: 'var(--color-secondary)',
                        color: 'var(--color-on-secondary)',
                    }"
                >
                    بحث
                </button>
            </form>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
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
                    <a :href="`tel:${store.contact.phone}`">{{
                        store.contact.phone
                    }}</a>
                </li>
                <li v-if="store.contact.email">
                    <a :href="`mailto:${store.contact.email}`">{{
                        store.contact.email
                    }}</a>
                </li>
                <li v-if="whatsappUrl">
                    <a :href="whatsappUrl" rel="noopener">WhatsApp</a>
                </li>
                <li v-for="(url, network) in store.social" :key="network">
                    <a :href="url" rel="noopener" target="_blank">{{
                        network
                    }}</a>
                </li>
            </ul>
        </footer>
    </div>
</template>
