import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { initializeDirection } from '@/composables/useDirection';
import { createAppI18n } from '@/i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Eager glob must live in this entry file; nested files are not matched by rolldown...
const moduleLangFiles = import.meta.glob(
    '../../Modules/*/resources/js/lang/*.json',
    { eager: true },
) as Record<string, { default?: Record<string, unknown> }>;

const { i18n } = createAppI18n(moduleLangFiles);

createInertiaApp({
    resolve: async (name) => {
        const appPages = import.meta.glob('./pages/**/*.vue');
        const modulePages = import.meta.glob(
            '../../Modules/*/resources/js/pages/**/*.vue',
        );

        const appPage = appPages[`./pages/${name}.vue`];

        let modulePage;

        if (name.includes('::')) {
            const [moduleNs, pagePath] = name.split('::');
            const moduleName = moduleNs.charAt(0).toUpperCase() + moduleNs.slice(1);
            modulePage = modulePages[
                `../../Modules/${moduleName}/resources/js/pages/${pagePath}.vue`
            ];
        } else {
            const [firstSegment, ...rest] = name.split('/');

            modulePage = modulePages[
                `../../Modules/${firstSegment}/resources/js/pages/${rest.join('/')}.vue`
            ];

            if (!modulePage) {
                for (const [key, loader] of Object.entries(modulePages)) {
                    const pagePath = key.replace(/^.*\/pages\//, '').replace(/\.vue$/, '');

                    if (pagePath === name) {
                        modulePage = loader;
                        break;
                    }
                }
            }
        }

        const page = appPage ?? modulePage;

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        const module = (await page()) as { default?: DefineComponent };

        return module.default ?? (module as DefineComponent);
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('Tenancy/'):
                return AuthLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    withApp: (app) => {
        app.use(i18n);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will set the direction (LTR / RTL) on page load...
initializeDirection();

// This will listen for flash toast data from the server...
initializeFlashToast();
