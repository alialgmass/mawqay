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
    name: '',
    email: '',
    phone: '',
    password: '',
    redirect: props.redirect,
});

function submit() {
    form.post(shell.value.actions.register);
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
                إنشاء حساب
            </h2>

            <form class="space-y-4" novalidate @submit.prevent="submit">
                <FormField
                    id="name"
                    v-model="form.name"
                    label="الاسم"
                    autocomplete="name"
                    required
                    :error="form.errors.name"
                />
                <FormField
                    id="email"
                    v-model="form.email"
                    type="email"
                    label="البريد الإلكتروني"
                    autocomplete="email"
                    dir="ltr"
                    hint="أدخلي بريدًا إلكترونيًا أو رقم موبايل (واحد على الأقل)"
                    :error="form.errors.email"
                />
                <FormField
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    label="رقم الموبايل"
                    autocomplete="tel"
                    dir="ltr"
                    :error="form.errors.phone"
                />
                <FormField
                    id="password"
                    v-model="form.password"
                    type="password"
                    label="كلمة المرور"
                    autocomplete="new-password"
                    required
                    hint="8 أحرف على الأقل"
                    :error="form.errors.password"
                />
                <SubmitButton :processing="form.processing"
                    >إنشاء الحساب</SubmitButton
                >
            </form>

            <p class="text-sm">
                لديك حساب؟
                <Link
                    :href="shell.urls.login"
                    class="inline-flex min-h-11 items-center font-semibold text-[var(--color-primary)] hover:underline"
                    >تسجيل الدخول</Link
                >
            </p>
        </div>
    </StoreLayout>
</template>
