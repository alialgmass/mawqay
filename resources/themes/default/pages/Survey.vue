<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import StarRating from '../components/StarRating.vue';
import SubmitButton from '../components/SubmitButton.vue';
import StoreLayout from '../layouts/StoreLayout.vue';
import type { Seo } from '../layouts/StoreLayout.vue';

type SurveyData = {
    state: 'form' | 'answered' | 'thanks' | 'expired' | 'invalid';
    orderNumber: string | null;
    submitUrl: string | null;
};

const props = defineProps<{ seo: Seo; survey: SurveyData }>();

const form = useForm({
    overall_rating: 0,
    product_match_rating: 0,
    delivery_speed_rating: 0,
    comment: '',
});

function submit() {
    if (props.survey.submitUrl) {
        form.post(props.survey.submitUrl);
    }
}

const messages: Record<string, string> = {
    thanks: 'شكرًا لك! تم استلام رأيك.',
    answered: 'لقد أجبتِ على هذا الاستبيان من قبل. شكرًا لك.',
    expired: 'انتهت صلاحية رابط الاستبيان.',
    invalid: 'رابط الاستبيان غير صالح.',
};
</script>

<template>
    <StoreLayout :seo="seo">
        <div class="mx-auto max-w-md space-y-6">
            <h2
                class="text-xl font-semibold"
                :style="{ fontFamily: 'var(--font-heading)' }"
            >
                كيف كانت تجربتك؟
            </h2>

            <p v-if="survey.state !== 'form'" role="status" class="text-sm">
                {{ messages[survey.state] }}
            </p>

            <form v-else class="space-y-6" @submit.prevent="submit">
                <p v-if="survey.orderNumber" class="text-sm">
                    الطلب {{ survey.orderNumber }}
                </p>

                <StarRating
                    v-model="form.overall_rating"
                    label="التقييم العام"
                />
                <p
                    v-if="form.errors.overall_rating"
                    class="text-sm text-red-600"
                >
                    {{ form.errors.overall_rating }}
                </p>

                <StarRating
                    v-model="form.product_match_rating"
                    label="مطابقة المنتج للوصف"
                />
                <StarRating
                    v-model="form.delivery_speed_rating"
                    label="سرعة التوصيل"
                />

                <label class="block space-y-1 text-sm">
                    <span class="font-medium">تعليق (اختياري)</span>
                    <textarea
                        v-model="form.comment"
                        rows="4"
                        maxlength="1000"
                        class="w-full border p-2"
                        :style="{ borderRadius: 'var(--radius-md)' }"
                    />
                </label>

                <SubmitButton :processing="form.processing">
                    إرسال
                </SubmitButton>
            </form>
        </div>
    </StoreLayout>
</template>
