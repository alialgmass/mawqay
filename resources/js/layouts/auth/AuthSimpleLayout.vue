<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-white dark:bg-[#0a0a0a]">
        <!-- Brand header -->
        <header class="border-b border-gray-200 dark:border-gray-800">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link :href="home()" class="flex items-center gap-2 font-bold text-xl">
                    <span class="text-primary">SmartPOS</span>
                    <span class="text-gray-900 dark:text-white">Egypt</span>
                </Link>
                <LocaleSwitcher />
            </div>
        </header>

        <!-- Auth content -->
        <div class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-sm">
                <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-[#121212]">
                    <div class="mb-6 flex flex-col items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                            <AppLogoIcon class="size-7 text-primary" />
                        </div>
                        <div class="space-y-1 text-center">
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ title }}
                            </h1>
                            <p v-if="description" class="text-sm text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <slot />
                </div>

                <!-- Footer links -->
                <p class="mt-6 text-center text-xs text-gray-400">
                    &copy; {{ new Date().getFullYear() }} SmartPOS Egypt. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>
