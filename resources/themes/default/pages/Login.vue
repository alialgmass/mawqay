<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import FormField from '../components/FormField.vue';
import SubmitButton from '../components/SubmitButton.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

const props = defineProps<{ seo: Seo; redirect: string | null }>();

const shell = useShell();
const form = useForm({
    identifier: '',
    password: '',
    redirect: props.redirect,
});

function submit() {
    form.post(shell.value.actions.login);
}
</script>

<template>
    <StoreLayout :seo="seo">
        <div
            class="mx-auto max-w-md space-y-5 p-6 shadow-md sm:p-8"
            :style="{
                border: '1px solid var(--color-border)',
                borderRadius: 'var(--radius-lg)',
                backgroundColor: 'var(--color-bg)',
            }"
        >
            <h2
                class="text-xl font-semibold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                تسجيل الدخول
            </h2>

            <form class="space-y-4" novalidate @submit.prevent="submit">
                <FormField
                    id="identifier"
                    v-model="form.identifier"
                    label="البريد الإلكتروني أو رقم الموبايل"
                    autocomplete="username"
                    dir="ltr"
                    required
                    :error="form.errors.identifier"
                />
                <FormField
                    id="password"
                    v-model="form.password"
                    type="password"
                    label="كلمة المرور"
                    autocomplete="current-password"
                    required
                    :error="form.errors.password"
                />
                <SubmitButton :processing="form.processing">دخول</SubmitButton>
            </form>

            <p class="flex flex-wrap gap-4 text-sm">
                <Link
                    :href="shell.urls.forgotPassword"
                    class="inline-flex min-h-11 items-center font-semibold text-[var(--color-primary)] hover:underline"
                    >نسيت كلمة المرور؟</Link
                >
                <Link
                    :href="shell.urls.register"
                    class="inline-flex min-h-11 items-center font-semibold text-[var(--color-primary)] hover:underline"
                    >إنشاء حساب جديد</Link
                >
            </p>
        </div>
    </StoreLayout>
</template>
