<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';

const { t } = useI18n();

const items = computed<{ q: string; a: string }[]>(() =>
    (t('landing.faq.items') as unknown as { q: string; a: string }[]) ?? [],
);
</script>

<template>
    <section class="border-t border-border bg-muted py-16 lg:py-24" id="faq">
        <div class="mx-auto w-full max-w-3xl px-4 sm:px-6 lg:px-8">
            <h2 class="mb-10 text-center text-3xl font-bold tracking-tight sm:text-4xl">
                {{ t('landing.faq.title') }}
            </h2>

            <div class="flex flex-col gap-3">
                <Collapsible
                    v-for="(item, index) in items"
                    :key="index"
                    class="rounded-2xl border border-border bg-card"
                >
                    <CollapsibleTrigger
                        class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-start text-sm font-medium md:text-base"
                    >
                        {{ item.q }}
                        <ChevronDown
                            class="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <p class="px-5 pb-4 text-sm text-muted-foreground md:text-base">
                            {{ item.a }}
                        </p>
                    </CollapsibleContent>
                </Collapsible>
            </div>
        </div>
    </section>
</template>
