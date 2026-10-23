<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { RotateCcw, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = withDefaults(
    defineProps<{
        search?: string;
        placeholder?: string;
    }>(),
    {
        search: '',
        placeholder: undefined,
    },
);

const emit = defineEmits<{
    'update:search': [value: string];
    reset: [];
}>();

const { t } = useI18n();

const term = ref(props.search);

watch(
    () => props.search,
    (value) => {
        term.value = value ?? '';
    },
);

const onInput = useDebounceFn(() => {
    emit('update:search', term.value);
}, 350);
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative min-w-0 flex-1 sm:max-w-xs">
            <Search
                class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="term"
                :placeholder="placeholder ?? t('searchFilter.placeholder')"
                class="ps-9"
                @update:model-value="onInput"
                @keyup.enter="emit('update:search', term)"
            />
        </div>

        <slot />

        <Button variant="ghost" size="sm" @click="emit('reset')">
            <RotateCcw class="size-4" />
            {{ t('searchFilter.reset') }}
        </Button>
    </div>
</template>
