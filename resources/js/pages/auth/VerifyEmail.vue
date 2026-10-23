<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verify email',
        description:
            'Please verify your email address by clicking on the link we just emailed to you.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Email verification" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-sm font-medium text-emerald-700 dark:text-emerald-400"
    >
        <span class="material-symbols-outlined text-base">check_circle</span>
        A new verification link has been sent to the email address you provided during registration.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary" class="w-full">
            <Spinner v-if="processing" class="mr-2" />
            <span class="material-symbols-outlined text-sm mr-1">forward_to_inbox</span>
            Resend verification email
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Log out
        </TextLink>
    </Form>
</template>
