<?php

namespace Modules\Auth\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Auth\Support\PhoneNumber;
use Modules\Tenancy\Models\Store;

/**
 * Shared email / mobile number rules: at least one, each unique within the
 * Store, the mobile number stored in its standard form.
 */
trait ValidatesCustomerIdentifiers
{
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        $email = $this->input('email');

        $this->merge([
            'phone' => filled($phone) ? (PhoneNumber::normalize((string) $phone) ?? 'invalid') : null,
            'email' => filled($email) ? trim((string) $email) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function identifierRules(?int $ignoreCustomerId = null): array
    {
        $storeId = Store::current()->id;

        return [
            'email' => [
                'nullable', 'required_without:phone', 'email', 'max:255',
                Rule::unique('customers', 'email')->where('store_id', $storeId)->ignore($ignoreCustomerId),
            ],
            'phone' => [
                'nullable', 'required_without:email', 'regex:/^\+\d{8,15}$/', 'max:32',
                Rule::unique('customers', 'phone')->where('store_id', $storeId)->ignore($ignoreCustomerId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function identifierMessages(): array
    {
        return [
            'email.required_without' => __('auth::customers.identifier_required'),
            'phone.required_without' => __('auth::customers.identifier_required'),
            'email.unique' => __('auth::customers.email_taken'),
            'phone.unique' => __('auth::customers.phone_taken'),
            'phone.regex' => __('auth::customers.phone_invalid'),
        ];
    }
}
