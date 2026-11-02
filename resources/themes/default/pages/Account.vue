<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import FormField from '../components/FormField.vue';
import SubmitButton from '../components/SubmitButton.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

const props = defineProps<{
    seo: Seo;
    customer: {
        name: string;
        email: string | null;
        phone: string | null;
        emailVerified: boolean;
    } | null;
}>();

const shell = useShell();

const profile = useForm({
    name: props.customer?.name ?? '',
    email: props.customer?.email ?? '',
    phone: props.customer?.phone ?? '',
});

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function saveProfile() {
    profile.put(shell.value.actions.profile);
}

function changePassword() {
    password.put(shell.value.actions.password, {
        onSuccess: () => password.reset(),
    });
}
</script>

<template>
    <StoreLayout :seo="seo">
        <div class="mx-auto max-w-md space-y-8">
            <section aria-labelledby="profile-heading" class="space-y-4">
                <h2
                    id="profile-heading"
                    class="text-xl font-semibold"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                >
                    حسابي
                </h2>

                <form
                    class="space-y-4"
                    novalidate
                    @submit.prevent="saveProfile"
                >
                    <FormField
                        id="name"
                        v-model="profile.name"
                        label="الاسم"
                        autocomplete="name"
                        required
                        :error="profile.errors.name"
                    />
                    <FormField
                        id="email"
                        v-model="profile.email"
                        type="email"
                        label="البريد الإلكتروني"
                        autocomplete="email"
                        dir="ltr"
                        :error="profile.errors.email"
                    />
                    <FormField
                        id="phone"
                        v-model="profile.phone"
                        type="tel"
                        label="رقم الموبايل"
                        autocomplete="tel"
                        dir="ltr"
                        :error="profile.errors.phone"
                    />
                    <SubmitButton :processing="profile.processing"
                        >حفظ</SubmitButton
                    >
                </form>

                <Link
                    :href="shell.urls.addresses"
                    class="inline-flex min-h-11 items-center font-semibold text-[var(--color-primary)] hover:underline text-sm"
                    >عناويني</Link
                >
            </section>

            <section aria-labelledby="password-heading" class="space-y-4">
                <h2
                    id="password-heading"
                    class="text-lg font-semibold"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                >
                    تغيير كلمة المرور
                </h2>

                <form
                    class="space-y-4"
                    novalidate
                    @submit.prevent="changePassword"
                >
                    <FormField
                        id="current_password"
                        v-model="password.current_password"
                        type="password"
                        label="كلمة المرور الحالية"
                        autocomplete="current-password"
                        required
                        :error="password.errors.current_password"
                    />
                    <FormField
                        id="new_password"
                        v-model="password.password"
                        type="password"
                        label="كلمة المرور الجديدة"
                        autocomplete="new-password"
                        required
                        :error="password.errors.password"
                    />
                    <FormField
                        id="new_password_confirmation"
                        v-model="password.password_confirmation"
                        type="password"
                        label="تأكيد كلمة المرور"
                        autocomplete="new-password"
                        required
                    />
                    <SubmitButton :processing="password.processing"
                        >تغيير</SubmitButton
                    >
                </form>
            </section>
        </div>
    </StoreLayout>
</template>
