<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="store_name">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-muted-foreground">store</span>
                        Store name
                    </span>
                </Label>
                <Input
                    id="store_name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="organization"
                    name="store_name"
                    placeholder="Downtown Market"
                />
                <InputError :message="errors.store_name" />
            </div>

            <div class="grid gap-2">
                <Label for="owner_name">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-muted-foreground">person</span>
                        Owner name
                    </span>
                </Label>
                <Input
                    id="owner_name"
                    type="text"
                    required
                    :tabindex="2"
                    autocomplete="name"
                    name="owner_name"
                    placeholder="Full name"
                />
                <InputError :message="errors.owner_name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-muted-foreground">mail</span>
                        Email address
                    </span>
                </Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="3"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-muted-foreground">lock</span>
                        Password
                    </span>
                </Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-muted-foreground">lock</span>
                        Confirm password
                    </span>
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" class="mr-2" />
                <span class="material-symbols-outlined text-sm mr-1">person_add</span>
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="7"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
