<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import BlogPostCard from '@/components/landing/BlogPostCard.vue';
import type { BlogPostCard as PostCard } from '@/components/landing/BlogPostCard.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDirection } from '@/composables/useDirection';
import { index as blogIndex } from '@/routes/blog';

type Detail = PostCard & {
    author: string | null;
    paragraphs: string[];
};

type Props = {
    post: Detail;
    related: PostCard[];
    seo: { title: string; description: string };
};

const props = defineProps<Props>();

const { t, locale } = useI18n();
const { direction } = useDirection();

const BackIcon = computed(() => ArrowLeft);

const categoryLabel = computed(() => t(`landing.blog.categories.${props.post.category}`));

const formattedDate = computed(() => {
    if (props.post.date === null) {
        return '';
    }

    return new Intl.DateTimeFormat(`${locale.value}-u-nu-latn`, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(props.post.date));
});
</script>

<template>
    <Head :title="seo.title">
        <meta name="description" :content="seo.description" />
    </Head>

    <div class="public-site flex min-h-screen flex-col bg-background">
        <header class="sticky top-0 z-40 border-b border-border/70 bg-background/80 backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-lg font-semibold tracking-tight">
                    {{ t('landing.brand') }}
                </Link>
                <div class="flex items-center gap-2">
                    <LocaleSwitcher />
                    <Button variant="link" as-child>
                        <Link :href="blogIndex()">
                            {{ t('landing.blog.back') }}
                        </Link>
                    </Button>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-12 sm:px-6 lg:px-8">
            <Button variant="ghost" size="sm" as-child class="-ms-2 text-muted-foreground">
                <Link :href="blogIndex()">
                    <BackIcon class="h-4 w-4 rtl:rotate-180" />
                    {{ t('landing.blog.back') }}
                </Link>
            </Button>

            <div class="mt-6 flex flex-col items-start gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <Badge variant="secondary">{{ categoryLabel }}</Badge>
                    <time v-if="formattedDate" :datetime="post.date ?? undefined" class="text-sm text-muted-foreground">
                        {{ formattedDate }}
                    </time>
                    <span v-if="post.author" class="text-sm text-muted-foreground">
                        · {{ t('landing.blog.by', { author: post.author }) }}
                    </span>
                </div>

                <h1 class="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                    {{ post.title }}
                </h1>

                <div
                    class="flex aspect-[16/7] w-full items-center justify-center overflow-hidden rounded-2xl border border-border"
                    :class="'bg-muted'"
                >
                    <img loading="lazy" decoding="async"
                        v-if="post.coverUrl"
                        :src="post.coverUrl"
                        :alt="post.title"
                        class="h-full w-full object-cover"
                    />
                    <svg
                        v-else
                        viewBox="0 0 240 105"
                        class="h-full w-full text-foreground/10"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <circle cx="70" cy="52" r="26" fill-opacity="0.6" />
                        <circle cx="120" cy="52" r="40" fill-opacity="0.4" />
                        <circle cx="170" cy="52" r="26" fill-opacity="0.6" />
                        <rect x="18" y="18" width="16" height="16" rx="8" fill-opacity="0.3" />
                        <rect x="206" y="70" width="16" height="16" rx="8" fill-opacity="0.3" />
                    </svg>
                </div>
            </div>

            <article class="mt-10 flex flex-col gap-6">
                <p v-for="(paragraph, index) in post.paragraphs" :key="index" class="text-base leading-8 sm:text-lg">
                    {{ paragraph }}
                </p>
            </article>

            <section v-if="related.length > 0" class="mt-16 border-t border-border pt-10">
                <h2 class="mb-6 text-xl font-semibold">
                    {{ t('landing.blog.related') }}
                </h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <BlogPostCard v-for="relatedPost in related" :key="relatedPost.slug" :post="relatedPost" />
                </div>
            </section>
        </main>

        <LandingFooter />
    </div>
</template>
