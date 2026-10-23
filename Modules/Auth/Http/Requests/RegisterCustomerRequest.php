<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Models\Store;

class RegisterCustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $storeId = Store::current()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'required_without:phone', 'email', 'max:255',
                Rule::unique('customers', 'email')->where('store_id', $storeId),
            ],
            'phone' => [
                'nullable', 'required_without:email', 'string', 'max:32',
                Rule::unique('customers', 'phone')->where('store_id', $storeId),
            ],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
