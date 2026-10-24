<script setup lang="ts">
import { computed, ref } from 'vue';

export type VariantData = {
    id: number;
    size: string;
    color: string;
    price: string;
    available: boolean;
};

const props = defineProps<{ variants: VariantData[] }>();

const emit = defineEmits<{ select: [variant: VariantData | null] }>();

const size = ref<string | null>(null);
const color = ref<string | null>(null);

const sizes = computed(() => [...new Set(props.variants.map((v) => v.size))]);
const colors = computed(() => [...new Set(props.variants.map((v) => v.color))]);

const find = (s: string | null, c: string | null) =>
    props.variants.find((v) => v.size === s && v.color === c) ?? null;

/** An option is disabled when no purchasable Variant is left with it. */
const sizeDisabled = (s: string) =>
    !props.variants.some(
        (v) =>
            v.size === s &&
            v.available &&
            (!color.value || v.color === color.value),
    );
const colorDisabled = (c: string) =>
    !props.variants.some(
        (v) =>
            v.color === c &&
            v.available &&
            (!size.value || v.size === size.value),
    );

const selected = computed(() => find(size.value, color.value));

function pick(kind: 'size' | 'color', value: string) {
    const target = kind === 'size' ? size : color;
    target.value = target.value === value ? null : value;
    emit('select', selected.value?.available ? selected.value : null);
}
</script>

<template>
    <div class="space-y-4">
        <fieldset>
            <legend class="mb-2 text-sm font-medium">المقاس</legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="s in sizes"
                    :key="s"
                    type="button"
                    :disabled="sizeDisabled(s)"
                    :aria-pressed="size === s"
                    class="min-w-12 rounded-md px-3 py-2 text-sm disabled:line-through disabled:opacity-40"
                    :style="{
                        border: `1px solid ${size === s ? 'var(--color-primary)' : 'var(--color-border)'}`,
                    }"
                    @click="pick('size', s)"
                >
                    {{ s }}
                </button>
            </div>
        </fieldset>

        <fieldset>
            <legend class="mb-2 text-sm font-medium">اللون</legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="c in colors"
                    :key="c"
                    type="button"
                    :disabled="colorDisabled(c)"
                    :aria-pressed="color === c"
                    class="rounded-md px-3 py-2 text-sm disabled:line-through disabled:opacity-40"
                    :style="{
                        border: `1px solid ${color === c ? 'var(--color-primary)' : 'var(--color-border)'}`,
                    }"
                    @click="pick('color', c)"
                >
                    {{ c }}
                </button>
            </div>
        </fieldset>

        <p v-if="selected" class="text-sm" dir="ltr">
            <span class="font-semibold">{{ selected.price }}</span>
            <span v-if="!selected.available"> — غير متوفر</span>
        </p>
    </div>
</template>
