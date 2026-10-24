<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Http\Requests\Concerns\ValidatesCustomerIdentifiers;

class UpdateCustomerProfileRequest extends FormRequest
{
    use ValidatesCustomerIdentifiers;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            ...$this->identifierRules($this->user('customer')?->getKey()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->identifierMessages(),
            'email.required_without' => __('auth::customers.keep_one_identifier'),
            'phone.required_without' => __('auth::customers.keep_one_identifier'),
        ];
    }
}
