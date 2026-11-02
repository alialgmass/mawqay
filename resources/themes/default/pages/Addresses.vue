<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmptyState from '../components/EmptyState.vue';
import FormField from '../components/FormField.vue';
import SubmitButton from '../components/SubmitButton.vue';
import { useShell } from '../composables/useShell';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

type AddressData = {
    id: number;
    label: string;
    recipientName: string;
    phone: string;
    governorate: string;
    governorateName: string;
    city: string;
    addressLine: string;
    notes: string | null;
    isDefault: boolean;
};

const props = defineProps<{
    seo: Seo;
    addresses: AddressData[];
    governorates: { value: string; name: string }[];
    maxAddresses: number;
}>();

const shell = useShell();
const editing = ref<number | null>(null);

const form = useForm({
    label: '',
    recipient_name: '',
    phone: '',
    governorate: props.governorates[0]?.value ?? '',
    city: '',
    address_line: '',
    notes: '',
    is_default: false,
});

function startEdit(address: AddressData) {
    editing.value = address.id;
    form.defaults({
        label: address.label,
        recipient_name: address.recipientName,
        phone: address.phone,
        governorate: address.governorate,
        city: address.city,
        address_line: address.addressLine,
        notes: address.notes ?? '',
        is_default: address.isDefault,
    });
    form.reset();
}

function cancelEdit() {
    editing.value = null;
    form.defaults({
        label: '',
        recipient_name: '',
        phone: '',
        governorate: props.governorates[0]?.value ?? '',
        city: '',
        address_line: '',
        notes: '',
        is_default: false,
    });
    form.reset();
}

function submit() {
    const options = { onSuccess: () => cancelEdit() };

    if (editing.value === null) {
        form.post(shell.value.actions.addresses, options);
    } else {
        form.put(`${shell.value.actions.addresses}/${editing.value}`, options);
    }
}

function remove(address: AddressData) {
    router.delete(`${shell.value.actions.addresses}/${address.id}`);
}

function makeDefault(address: AddressData) {
    router.post(`${shell.value.actions.addresses}/${address.id}/default`);
}
</script>

<template>
    <StoreLayout :seo="seo">
        <div class="mx-auto max-w-2xl space-y-8">
            <section aria-labelledby="addresses-heading" class="space-y-4">
                <h2
                    id="addresses-heading"
                    class="text-xl font-semibold"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                >
                    عناويني
                </h2>

                <EmptyState
                    v-if="addresses.length === 0"
                    message="لم تضيفي أي عنوان بعد"
                />

                <ul v-else class="space-y-3">
                    <li
                        v-for="address in addresses"
                        :key="address.id"
                        class="space-y-1 p-4 shadow-sm"
                        :style="{
                            border: '1px solid var(--color-border)',
                            borderRadius: 'var(--radius-lg)',
                            backgroundColor: 'var(--color-surface)',
                        }"
                    >
                        <p class="font-medium">
                            {{ address.label }}
                            <span
                                v-if="address.isDefault"
                                class="text-xs"
                                :style="{ color: 'var(--color-success)' }"
                            >
                                (الافتراضي)
                            </span>
                        </p>
                        <p class="text-sm">
                            {{ address.recipientName }} —
                            <span dir="ltr">{{ address.phone }}</span>
                        </p>
                        <p class="text-sm">
                            {{ address.governorateName }}، {{ address.city }}،
                            {{ address.addressLine }}
                        </p>
                        <p
                            v-if="address.notes"
                            class="text-sm"
                            :style="{ color: 'var(--color-text-muted)' }"
                        >
                            {{ address.notes }}
                        </p>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <button
                                type="button"
                                class="min-h-11 px-3 text-sm underline"
                                @click="startEdit(address)"
                            >
                                تعديل
                            </button>
                            <button
                                v-if="!address.isDefault"
                                type="button"
                                class="min-h-11 px-3 text-sm underline"
                                @click="makeDefault(address)"
                            >
                                تعيين كافتراضي
                            </button>
                            <button
                                type="button"
                                class="min-h-11 px-3 text-sm underline"
                                @click="remove(address)"
                            >
                                حذف
                            </button>
                        </div>
                    </li>
                </ul>
            </section>

            <section
                v-if="editing !== null || addresses.length < maxAddresses"
                aria-labelledby="address-form-heading"
                class="space-y-4"
            >
                <h2
                    id="address-form-heading"
                    class="text-lg font-semibold"
                    :style="{ fontFamily: 'var(--font-heading)' }"
                >
                    {{ editing === null ? 'إضافة عنوان' : 'تعديل العنوان' }}
                </h2>

                <form class="space-y-4" novalidate @submit.prevent="submit">
                    <FormField
                        id="label"
                        v-model="form.label"
                        label="اسم العنوان (مثل: المنزل)"
                        required
                        :error="form.errors.label"
                    />
                    <FormField
                        id="recipient_name"
                        v-model="form.recipient_name"
                        label="اسم المستلمة"
                        required
                        :error="form.errors.recipient_name"
                    />
                    <FormField
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        label="رقم الموبايل"
                        dir="ltr"
                        required
                        :error="form.errors.phone"
                    />

                    <div class="space-y-1">
                        <label
                            for="governorate"
                            class="block text-sm font-medium"
                            >المحافظة</label
                        >
                        <select
                            id="governorate"
                            v-model="form.governorate"
                            class="min-h-11 w-full px-3"
                            :style="{
                                border: '1px solid var(--color-border)',
                                borderRadius: 'var(--radius-sm)',
                                backgroundColor: 'var(--color-bg)',
                            }"
                        >
                            <option
                                v-for="g in governorates"
                                :key="g.value"
                                :value="g.value"
                            >
                                {{ g.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.governorate"
                            role="alert"
                            class="text-sm"
                            :style="{ color: 'var(--color-danger)' }"
                        >
                            {{ form.errors.governorate }}
                        </p>
                    </div>

                    <FormField
                        id="city"
                        v-model="form.city"
                        label="المدينة / الحي"
                        required
                        :error="form.errors.city"
                    />
                    <FormField
                        id="address_line"
                        v-model="form.address_line"
                        label="العنوان بالتفصيل"
                        required
                        :error="form.errors.address_line"
                    />
                    <FormField
                        id="notes"
                        v-model="form.notes"
                        label="ملاحظات (اختياري)"
                        :error="form.errors.notes"
                    />

                    <label class="flex min-h-11 items-center gap-2 text-sm">
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            class="h-5 w-5"
                        />
                        تعيين كعنوان افتراضي
                    </label>

                    <div class="flex gap-3">
                        <SubmitButton :processing="form.processing"
                            >حفظ</SubmitButton
                        >
                        <SubmitButton
                            v-if="editing !== null"
                            variant="plain"
                            type="button"
                            @click.prevent="cancelEdit"
                            >إلغاء</SubmitButton
                        >
                    </div>
                </form>
            </section>
        </div>
    </StoreLayout>
</template>
