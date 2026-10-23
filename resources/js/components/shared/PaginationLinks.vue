<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        currentPage: number;
        lastPage: number;
        total?: number;
        perPage?: number;
        hrefFor: (page: number) => string;
    }>(),
    {
        total: undefined,
        perPage: undefined,
    },
);

const { t } = useI18n();

type PageItem = number | 'ellipsis-start' | 'ellipsis-end';

const items = computed<PageItem[]>(() => {
    const current = props.currentPage;
    const last = props.lastPage;

    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const window: PageItem[] = [1];
    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    if (start > 2) {
        window.push('ellipsis-start');
    }

    for (let page = start; page <= end; page++) {
        window.push(page);
    }

    if (end < last - 1) {
        window.push('ellipsis-end');
    }

    window.push(last);

    return window;
});

const summary = computed(() => {
    const { total, perPage, currentPage } = props;

    if (!total || !perPage) {
        return '';
    }

    const from = (currentPage - 1) * perPage + 1;
    const to = Math.min(currentPage * perPage, total);

    return t('pagination.summary', { from, to, total });
});
</script>

<template>
    <nav class="flex flex-wrap items-center justify-center gap-2" aria-label="Pagination">
        <p v-if="summary" class="me-auto hidden text-sm text-muted-foreground sm:block">
            {{ summary }}
        </p>

        <div class="flex items-center gap-1">
            <Link
                v-if="currentPage > 1"
                :href="hrefFor(currentPage - 1)"
                preserve-scroll
                class="inline-flex size-9 items-center justify-center rounded-md border text-sm hover:bg-muted"
            >
                <ChevronLeft class="size-4 rtl:rotate-180" />
                <span class="sr-only">{{ t('pagination.previous') }}</span>
            </Link>

            <template v-for="item in items" :key="item">
                <span
                    v-if="typeof item === 'string'"
                    class="inline-flex size-9 items-center justify-center text-muted-foreground"
                >
                    …
                </span>
                <Link
                    v-else
                    :href="hrefFor(item)"
                    preserve-scroll
                    class="inline-flex size-9 items-center justify-center rounded-md border text-sm hover:bg-muted"
                    :class="
                        item === currentPage ? 'bg-primary text-primary-foreground' : undefined
                    "
                    :aria-current="item === currentPage ? 'page' : undefined"
                >
                    {{ item }}
                </Link>
            </template>

            <Link
                v-if="currentPage < lastPage"
                :href="hrefFor(currentPage + 1)"
                preserve-scroll
                class="inline-flex size-9 items-center justify-center rounded-md border text-sm hover:bg-muted"
            >
                <ChevronRight class="size-4 rtl:rotate-180" />
                <span class="sr-only">{{ t('pagination.next') }}</span>
            </Link>
        </div>
    </nav>
</template>
