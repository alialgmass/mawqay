<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

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

/** Known colour names (Arabic and English) shown as a swatch; others stay text-only. */
const SWATCHES: Record<string, string> = {
    'أسود': '#111827', 'اسود': '#111827', black: '#111827',
    'أبيض': '#FFFFFF', 'ابيض': '#FFFFFF', white: '#FFFFFF',
    'أحمر': '#EF4444', 'احمر': '#EF4444', red: '#EF4444',
    'أزرق': '#3B82F6', 'ازرق': '#3B82F6', blue: '#3B82F6',
    'كحلي': '#1E3A8A', navy: '#1E3A8A',
    'سماوي': '#38BDF8', 'تركواز': '#14B8A6',
    'أخضر': '#22C55E', 'اخضر': '#22C55E', green: '#22C55E',
    'زيتي': '#65752B', 'أصفر': '#FACC15', 'اصفر': '#FACC15', yellow: '#FACC15',
    'برتقالي': '#F97316', orange: '#F97316',
    'وردي': '#F9A8D4', 'بينك': '#F472B6', pink: '#F472B6',
    'بنفسجي': '#8B5CF6', 'موف': '#A78BFA', purple: '#8B5CF6',
    'بني': '#92400E', brown: '#92400E', 'بيج': '#D6C3A3', beige: '#D6C3A3',
    'رمادي': '#9CA3AF', gray: '#9CA3AF', grey: '#9CA3AF',
    'ذهبي': '#D4AF37', gold: '#D4AF37', 'فضي': '#C0C0C0', silver: '#C0C0C0',
    'عنابي': '#7F1D1D', 'خمري': '#7F1D1D', 'كريمي': '#FFF5DC',
};
const swatch = (name: string) => SWATCHES[name.trim().toLowerCase()] ?? null;

function pick(kind: 'size' | 'color', value: string) {
    const target = kind === 'size' ? size : color;
    target.value = target.value === value ? null : value;
    emit('select', selected.value?.available ? selected.value : null);
}

/** The first purchasable variant is selected by default. */
onMounted(() => {
    const first = props.variants.find((v) => v.available);

    if (first) {
        size.value = first.size;
        color.value = first.color;
    }

    emit('select', selected.value?.available ? selected.value : null);
});
</script>

<template>
    <div class="space-y-5">
        <fieldset>
            <legend class="mb-2 text-sm font-semibold">
                المقاس
                <span v-if="size" class="font-normal opacity-70">: {{ size }}</span>
            </legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="s in sizes"
                    :key="s"
                    type="button"
                    :disabled="sizeDisabled(s)"
                    :aria-pressed="size === s"
                    class="min-h-11 min-w-12 px-4 text-sm font-semibold transition-all duration-200 ease-out hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:line-through disabled:opacity-40 disabled:hover:shadow-none"
                    :style="{
                        border: `1.5px solid ${size === s ? 'var(--color-primary)' : 'var(--color-border)'}`,
                        borderRadius: 'var(--radius-sm)',
                        backgroundColor: size === s ? 'var(--color-primary)' : 'var(--color-bg)',
                        color: size === s ? 'var(--color-on-primary)' : 'var(--color-text)',
                    }"
                    @click="pick('size', s)"
                >
                    {{ s }}
                </button>
            </div>
        </fieldset>

        <fieldset>
            <legend class="mb-2 text-sm font-semibold">
                اللون
                <span v-if="color" class="font-normal opacity-70">: {{ color }}</span>
            </legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="c in colors"
                    :key="c"
                    type="button"
                    :disabled="colorDisabled(c)"
                    :aria-pressed="color === c"
                    class="flex min-h-11 items-center gap-2 px-3 text-sm font-semibold transition-all duration-200 ease-out hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:line-through disabled:opacity-40 disabled:hover:shadow-none"
                    :style="{
                        border: `1.5px solid ${color === c ? 'var(--color-primary)' : 'var(--color-border)'}`,
                        borderRadius: 'var(--radius-sm)',
                        backgroundColor: color === c ? 'var(--color-secondary)' : 'var(--color-bg)',
                        color: 'var(--color-text)',
                    }"
                    @click="pick('color', c)"
                >
                    <span
                        v-if="swatch(c)"
                        aria-hidden="true"
                        class="inline-block h-5 w-5 shrink-0 rounded-full"
                        :style="{
                            backgroundColor: swatch(c) ?? undefined,
                            border: '1px solid rgba(0,0,0,0.15)',
                            boxShadow: color === c ? '0 0 0 2px var(--color-primary)' : 'none',
                        }"
                    />
                    {{ c }}
                </button>
            </div>
        </fieldset>

        <p
            v-if="!selected && (size || color)"
            class="text-sm"
            :style="{ color: 'var(--color-text-muted)' }"
        >
            {{ !size ? 'اختاري المقاس' : 'اختاري اللون' }} لإكمال الطلب
        </p>
        <p v-if="selected" class="text-sm" dir="ltr">
            <span class="font-semibold">{{ selected.price }}</span>
            <span v-if="!selected.available"> — غير متوفر</span>
        </p>
    </div>
</template>
