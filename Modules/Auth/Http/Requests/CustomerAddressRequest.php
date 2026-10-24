<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Support\PhoneNumber;

class CustomerAddressRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        $this->merge(['phone' => filled($phone) ? (PhoneNumber::normalize((string) $phone) ?? 'invalid') : null]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'governorate' => ['required', 'string', Rule::in(array_keys(config('customers.governorates')))],
            'city' => ['required', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('auth::customers.phone_invalid'),
            'governorate.in' => __('auth::addresses.governorate_invalid'),
        ];
    }
}
