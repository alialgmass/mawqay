<script setup lang="ts">
import { Languages } from 'lucide-vue-next';
import { useDirection } from '@/composables/useDirection';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';

const { locale, setLocale, availableLocales } = useDirection();

const labels: Record<string, string> = {
    en: 'English',
    ar: 'العربية',
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-8 w-8">
                <Languages class="h-4 w-4" />
                <span class="sr-only">Switch language</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" :side-offset="4">
            <DropdownMenuItem
                v-for="loc in availableLocales"
                :key="loc"
                :class="[
                    'cursor-pointer',
                    locale === loc ? 'bg-accent font-medium' : '',
                ]"
                @click="setLocale(loc as 'en' | 'ar')"
            >
                <span class="uppercase text-xs font-mono mr-2">{{ loc }}</span>
                {{ labels[loc] || loc }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
