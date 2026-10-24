<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import FormField from '../components/FormField.vue';
import SubmitButton from '../components/SubmitButton.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

defineProps<{ seo: Seo }>();

const shell = useShell();
const form = useForm({ email: '' });

function submit() {
    form.post(shell.value.actions.forgotPassword);
}
</script>

<template>
    <StoreLayout :seo="seo">
        <div class="mx-auto max-w-md space-y-4">
            <h2
                class="text-xl font-semibold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                نسيت كلمة المرور
            </h2>
            <p class="text-sm">
                أدخلي بريدك الإلكتروني وسنرسل لك رابط إعادة التعيين.
            </p>

            <form class="space-y-4" novalidate @submit.prevent="submit">
                <FormField
                    id="email"
                    v-model="form.email"
                    type="email"
                    label="البريد الإلكتروني"
                    autocomplete="email"
                    dir="ltr"
                    required
                    :error="form.errors.email"
                />
                <SubmitButton :processing="form.processing"
                    >إرسال الرابط</SubmitButton
                >
            </form>

            <Link
                :href="shell.urls.login"
                class="inline-flex min-h-11 items-center text-sm underline"
                >العودة لتسجيل الدخول</Link
            >
        </div>
    </StoreLayout>
</template>
