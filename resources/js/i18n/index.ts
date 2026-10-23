import { createI18n } from 'vue-i18n';
import type { Locale } from '@/composables/useDirection';
import ar from '@/locales/ar.json';
import en from '@/locales/en.json';

type Messages = Record<string, unknown>;
type ModuleFile = { default?: Messages };

function collectModuleMessages(locale: Locale, moduleFiles: Record<string, ModuleFile>): Messages {
    const messages: Messages = {};

    for (const [path, file] of Object.entries(moduleFiles)) {
        if (!path.endsWith(`/${locale}.json`)) {
            continue;
        }

        const match = /Modules\/([^/]+)\/resources\/js\/lang\//.exec(path);

        if (!match) {
            continue;
        }

        const namespace = match[1].toLowerCase();

        messages[namespace] = {
            ...((messages[namespace] as Messages | undefined) ?? {}),
            ...(file.default ?? (file as Messages)),
        };
    }

    return messages;
}

function getStoredLocale(): Locale | null {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('locale') as Locale | null;
}

function getCookieLocale(): Locale | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(/(?:^|;\s*)locale=([^;]*)/);

    if (!match) {
        return null;
    }

    const value = decodeURIComponent(match[1]);

    return value === 'ar' ? 'ar' : value === 'en' ? 'en' : null;
}

export function resolveInitialLocale(): Locale {
    return getStoredLocale() ?? getCookieLocale() ?? 'en';
}

/**
 * Build the shared i18n instance. The eager glob over Modules lang files must be
 * declared in app.ts (entry); passing it here keeps module translations bundled.
 */
let activeI18n: ReturnType<typeof createI18n> | null = null;

export function createAppI18n(moduleLangFiles: Record<string, ModuleFile>) {
    activeI18n = createI18n({
        legacy: false,
        globalInjection: true,
        locale: resolveInitialLocale(),
        fallbackLocale: 'en',
        messages: {
            en: { ...en, ...collectModuleMessages('en', moduleLangFiles) },
            ar: { ...ar, ...collectModuleMessages('ar', moduleLangFiles) },
        },
    });

    return { i18n: activeI18n };
}

export function setI18nLocale(locale: Locale): void {
    if (!activeI18n) {
        return;
    }

    const composer = activeI18n.global as unknown as { locale: { value: Locale } };
    composer.locale.value = locale;
}
