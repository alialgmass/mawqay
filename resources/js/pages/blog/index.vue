<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import BlogPostCard from '@/components/landing/BlogPostCard.vue';
import type { BlogPostCard as PostCard } from '@/components/landing/BlogPostCard.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { Button } from '@/components/ui/button';
import { useDirection } from '@/composables/useDirection';

type Props = {
    posts: {
        data: PostCard[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    seo: { title: string; description: string };
};

const props = defineProps<Props>();

const { t } = useI18n();
const { direction } = useDirection();

// `Laravel pagination` handles part 1; the band mirrors the store header.
const PrevIcon = computed(() => (direction.value === 'rtl' ? ChevronRight : ChevronLeft));
const NextIcon = computed(() => (direction.value === 'rtl' ? ChevronLeft : ChevronRight));

const empty = computed(() => props.posts.data.length === 0);
</script>

<template>
    <Head :title="seo.title">
        <meta name="description" :content="seo.description" />
    </Head>

    <div class="public-site flex min-h-screen flex-col bg-background">
        <header class="border-b border-border/70 bg-background/80 backdrop-blur sticky top-0 z-40">
            <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-lg font-semibold tracking-tight">
                    {{ t('landing.brand') }}
                </Link>
                <div class="flex items-center gap-2">
                    <LocaleSwitcher />
                    <Button variant="link" as-child>
                        <Link href="/">
                            {{ t('landing.nav.home') }}
                        </Link>
                    </Button>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-12 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                {{ t('landing.blog.title') }}
            </h1>
            <p class="mt-2 text-muted-foreground">
                {{ t('landing.blog.subtitle') }}
            </p>

            <p v-if="empty" class="mt-12 text-sm text-muted-foreground">
                {{ t('landing.blog.empty') }}
            </p>

            <div v-else class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <BlogPostCard v-for="post in posts.data" :key="post.slug" :post="post" />
            </div>

            <nav
                v-if="!empty && posts.last_page > 1"
                class="mt-10 flex items-center justify-center gap-3"
                :aria-label="t('pagination.summary', { from: posts.current_page, to: posts.last_page, total: posts.total })"
            >
                <Button variant="outline" size="sm" :disabled="posts.prev_page_url === null" as-child>
                    <Link v-if="posts.prev_page_url !== null" :href="posts.prev_page_url">
                        <PrevIcon class="h-4 w-4" />
                        <span>{{ t('pagination.previous') }}</span>
                    </Link>
                </Button>

                <span class="text-sm tabular-nums text-muted-foreground">
                    {{ posts.current_page }} / {{ posts.last_page }}
                </span>

                <Button variant="outline" size="sm" :disabled="posts.next_page_url === null" as-child>
                    <Link v-if="posts.next_page_url !== null" :href="posts.next_page_url">
                        <span>{{ t('pagination.next') }}</span>
                        <NextIcon class="h-4 w-4" />
                    </Link>
                </Button>
            </nav>
        </main>

        <LandingFooter />
    </div>
</template>
