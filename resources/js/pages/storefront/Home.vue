<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    store: {
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
    tokens: Record<string, string>;
}>();

const style = computed(() => props.tokens);
const whatsappUrl = computed(() =>
    props.store.contact.whatsapp
        ? `https://wa.me/${props.store.contact.whatsapp.replace(/\D/g, '')}`
        : null,
);
</script>

<template>
    <Head :title="store.name">
        <link v-if="store.faviconUrl" rel="icon" :href="store.faviconUrl" />
    </Head>

    <div
        dir="rtl"
        class="flex min-h-screen flex-col"
        :style="{
            ...style,
            backgroundColor: 'var(--color-bg)',
            color: 'var(--color-text)',
            fontFamily: 'var(--font-body)',
        }"
    >
        <header
            class="flex items-center gap-3 px-4 py-4"
            :style="{
                backgroundColor: 'var(--color-primary)',
                color: 'var(--color-on-primary)',
            }"
        >
            <img
                v-if="store.logoUrl"
                :src="store.logoUrl"
                :alt="store.name"
                class="h-10 w-auto"
            />
            <h1
                class="text-xl font-semibold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                {{ store.name }}
            </h1>
        </header>

        <main class="flex-1" />

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
