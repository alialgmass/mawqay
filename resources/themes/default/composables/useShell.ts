import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Shell } from '../layouts/StoreLayout.vue';

/** The shell data every page of the contract carries. */
export function useShell() {
    const page = usePage();

    return computed(() => page.props as unknown as Shell);
}
