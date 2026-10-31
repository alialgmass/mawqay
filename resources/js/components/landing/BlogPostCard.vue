<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

export type BlogPostCard = {
    title: string;
    slug: string;
    excerpt: string | null;
    category: string;
    date: string | null;
    coverUrl: string | null;
};

const props = defineProps<{
    post: BlogPostCard;
}>();

const { t, locale } = useI18n();

const categoryLabel = computed(() =>
    t(`landing.blog.categories.${props.post.category}`),
);

const coverHues: Record<string, string> = {
    product: 'bg-secondary',
    merchant_story: 'bg-secondary',
    fashion: 'bg-muted',
    guide: 'bg-muted',
};

const formattedDate = computed(() => {
    if (props.post.date === null) {
        return '';
    }

    return new Intl.DateTimeFormat(`${locale.value}-u-nu-latn`, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(props.post.date));
});

const postUrl = (slug: string): string => `/blog/${slug}`;
</script>

<template>
    <Card class="group flex h-full flex-col gap-0 overflow-hidden py-0 transition-shadow hover:shadow-lg hover:shadow-black/5">
        <Link :href="postUrl(post.slug)" class="block">
            <div
                class="flex aspect-[16/9] items-center justify-center overflow-hidden"
                :class="coverHues[post.category] ?? 'bg-muted'"
            >
                <img loading="lazy" decoding="async"
                    v-if="post.coverUrl"
                    :src="post.coverUrl"
                    :alt="post.title"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <svg
                    v-else
                    viewBox="0 0 120 68"
                    class="h-full w-full text-white/60"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <circle cx="30" cy="34" r="14" fill-opacity="0.5" />
                    <circle cx="60" cy="34" r="22" fill-opacity="0.35" />
                    <circle cx="90" cy="34" r="14" fill-opacity="0.5" />
                    <rect x="8" y="8" width="10" height="10" rx="5" fill-opacity="0.25" />
                    <rect x="102" y="50" width="10" height="10" rx="5" fill-opacity="0.25" />
                </svg>
            </div>
        </Link>

        <CardContent class="flex flex-1 flex-col gap-3 p-5">
            <div class="flex items-center justify-between gap-2">
                <Badge variant="secondary" class="text-xs font-medium">
                    {{ categoryLabel }}
                </Badge>
                <time
                    v-if="formattedDate"
                    :datetime="post.date ?? undefined"
                    class="text-xs text-muted-foreground"
                >
                    {{ formattedDate }}
                </time>
            </div>

            <Link :href="postUrl(post.slug)" class="block">
                <h3 class="text-base font-semibold leading-snug text-balance group-hover:underline group-hover:underline-offset-4">
                    {{ post.title }}
                </h3>
            </Link>

            <p v-if="post.excerpt" class="line-clamp-3 text-sm text-muted-foreground">
                {{ post.excerpt }}
            </p>

            <Link
                :href="postUrl(post.slug)"
                class="mt-auto inline-flex items-center gap-1.5 text-sm font-medium text-foreground hover:underline hover:underline-offset-4"
            >
                {{ t('landing.blog.readMore') }}
                <ArrowRight
                    class="h-4 w-4 rtl:rotate-180 transition-transform group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5"
                />
            </Link>
        </CardContent>
    </Card>
</template>
