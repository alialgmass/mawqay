import { router } from '@inertiajs/vue3';

export type FilterValue = string | number | null | undefined;

/**
 * Navigates to `baseUrl` merging `params` into the current query string.
 * Empty/null values remove the key; pagination resets on every change.
 */
export function applyFilters(
    baseUrl: string,
    params: Record<string, FilterValue>,
): void {
    const query = new URLSearchParams(window.location.search);

    for (const [key, value] of Object.entries(params)) {
        if (value === null || value === undefined || value === '') {
            query.delete(key);
        } else {
            query.set(key, String(value));
        }
    }

    query.delete('page');

    const queryString = query.toString();
    const url = queryString ? `${baseUrl}?${queryString}` : baseUrl;

    router.get(
        url,
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

export function currentFilter(key: string): string {
    if (typeof window === 'undefined') {
        return '';
    }

    return new URLSearchParams(window.location.search).get(key) ?? '';
}
