<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BlogPostCard from '@/components/landing/BlogPostCard.vue';
import type { BlogPostCard as PostCard } from '@/components/landing/BlogPostCard.vue';
import LandingAbout from '@/components/landing/LandingAbout.vue';
import LandingContact from '@/components/landing/LandingContact.vue';
import LandingFaq from '@/components/landing/LandingFaq.vue';
import LandingHero from '@/components/landing/LandingHero.vue';
import LandingHowItWorks from '@/components/landing/LandingHowItWorks.vue';
import LandingMoneyFlow from '@/components/landing/LandingMoneyFlow.vue';
import LandingRegister from '@/components/landing/LandingRegister.vue';
import LandingServices from '@/components/landing/LandingServices.vue';
import LandingStats from '@/components/landing/LandingStats.vue';
import LandingTestimonials from '@/components/landing/LandingTestimonials.vue';
import { Button } from '@/components/ui/button';
import { index as blogIndex } from '@/routes/blog';

type Seo = { title: string; description: string; canonical?: string; type?: string; image?: string };

type Props = {
    featuredPosts: PostCard[];
    hasFeaturedPosts: boolean;
    stats: { stores: number; products: number };
    seo: Seo;
};

defineProps<Props>();

const { t } = useI18n();
</script>

<template>
    <Head :title="seo.title">
        <meta name="description" :content="seo.description" />
        <link v-if="seo.canonical" rel="canonical" :href="seo.canonical" />
        <meta property="og:title" :content="seo.title" />
        <meta property="og:description" :content="seo.description" />
        <meta property="og:type" :content="seo.type ?? 'website'" />
        <meta v-if="seo.canonical" property="og:url" :content="seo.canonical" />
        <meta v-if="seo.image" property="og:image" :content="seo.image" />
        <meta name="twitter:card" content="summary_large_image" />
    </Head>

    <LandingHero />
    <LandingStats :stats="stats" />
    <LandingServices />
    <LandingHowItWorks />
    <LandingMoneyFlow />
    <LandingAbout />
    <LandingTestimonials />

    <section class="py-16 lg:py-24" id="blog">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {{ t('landing.blog.title') }}
                    </h2>
                    <p class="mt-2 text-muted-foreground">
                        {{ t('landing.blog.subtitle') }}
                    </p>
                </div>

                <Button variant="link" as-child class="px-0 text-foreground">
                    <Link :href="blogIndex()">
                        {{ t('landing.blog.all') }}
                    </Link>
                </Button>
            </div>

            <p v-if="!hasFeaturedPosts || featuredPosts.length === 0" class="text-sm text-muted-foreground">
                {{ t('landing.blog.empty') }}
            </p>

            <div v-else class="grid gap-6 md:grid-cols-3">
                <BlogPostCard v-for="post in featuredPosts" :key="post.slug" :post="post" />
            </div>
        </div>
    </section>

    <LandingFaq />
    <LandingContact />
    <LandingRegister />
</template>
