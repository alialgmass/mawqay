<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

withDefaults(
    defineProps<{
        open: boolean;
        title?: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        processing?: boolean;
    }>(),
    {
        title: undefined,
        description: undefined,
        confirmLabel: undefined,
        cancelLabel: undefined,
        processing: false,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();

const { t } = useI18n();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title ?? t('confirmDelete.title') }}</DialogTitle>
                <DialogDescription>
                    {{ description ?? t('confirmDelete.description') }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <Button variant="outline" :disabled="processing" @click="emit('update:open', false)">
                    {{ cancelLabel ?? t('confirmDelete.cancel') }}
                </Button>
                <Button variant="destructive" :disabled="processing" @click="emit('confirm')">
                    <Spinner v-if="processing" />
                    {{ confirmLabel ?? t('confirmDelete.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
