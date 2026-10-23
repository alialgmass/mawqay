import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { switchMethod } from '@/routes/locale';
import { setI18nLocale } from '@/i18n';

export type Direction = 'ltr' | 'rtl';
export type Locale = 'en' | 'ar';

export type UseDirectionReturn = {
    locale: Ref<Locale>;
    direction: ComputedRef<Direction>;
    setLocale: (value: Locale) => void;
    availableLocales: string[];
};

function getCookie(name: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(`(?:^|;\\s*)${name}=([^;]*)`);

    return match ? decodeURIComponent(match[1]) : null;
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

function updateDirection(locale: Locale): void {
    if (typeof document === 'undefined') {
        return;
    }

    const dir: Direction = locale === 'ar' ? 'rtl' : 'ltr';

    document.documentElement.setAttribute('dir', dir);
    document.documentElement.setAttribute('lang', locale);

    setI18nLocale(locale);
}

function getStoredLocale(): Locale | null {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('locale') as Locale | null;
}

export function initializeDirection(): void {
    if (typeof document === 'undefined') {
        return;
    }

    const savedLocale = getStoredLocale() ?? getCookie('locale') as Locale | null;

    if (savedLocale) {
        updateDirection(savedLocale);
    }
}

const locale = ref<Locale>('en');

export function useDirection(): UseDirectionReturn {
    const page = usePage();

    onMounted(() => {
        const savedLocale = getStoredLocale();

        if (savedLocale) {
            locale.value = savedLocale;
        } else {
            locale.value = (page.props.locale as Locale) || 'en';
        }

        updateDirection(locale.value);
    });

    const direction = computed<Direction>(() => {
        return locale.value === 'ar' ? 'rtl' : 'ltr';
    });

    const availableLocales = (page.props.availableLocales as string[]) || ['en', 'ar'];

    function setLocale(value: Locale) {
        locale.value = value;

        localStorage.setItem('locale', value);
        setCookie('locale', value);

        updateDirection(value);

        router.post(switchMethod.post().url, { locale: value }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                const newLocale = usePage().props.locale as Locale;
                if (newLocale) {
                    locale.value = newLocale;
                    updateDirection(newLocale);
                }
            },
        });
    }

    return {
        locale,
        direction,
        setLocale,
        availableLocales,
    };
}
