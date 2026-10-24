<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import FormField from '../components/FormField.vue';
import SubmitButton from '../components/SubmitButton.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

const props = defineProps<{ seo: Seo; token: string; email: string }>();

const shell = useShell();
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(shell.value.actions.resetPassword);
}
</script>

<template>
    <StoreLayout :seo="seo">
        <div class="mx-auto max-w-md space-y-4">
            <h2
                class="text-xl font-semibold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                إعادة تعيين كلمة المرور
            </h2>

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
                <FormField
                    id="password"
                    v-model="form.password"
                    type="password"
                    label="كلمة المرور الجديدة"
                    autocomplete="new-password"
                    required
                    :error="form.errors.password"
                />
                <FormField
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    label="تأكيد كلمة المرور"
                    autocomplete="new-password"
                    required
                />
                <SubmitButton :processing="form.processing"
                    >تغيير كلمة المرور</SubmitButton
                >
            </form>
        </div>
    </StoreLayout>
</template>
