<?php

namespace Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\UserRole;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Rules\StoreSlug;

/**
 * Onboards a Vendor: a Draft Store, its Vendor Owner and an invitation, all
 * or nothing.
 */
class CreateStoreWithOwner
{
    public function __construct(private readonly IssueInvitation $issueInvitation) {}

    /**
     * @param  array{name: string, slug: string, owner_name: string, owner_email: string, owner_phone?: ?string, commission_rate?: int|float|string|null}  $data
     */
    public function __invoke(array $data): Store
    {
        $data = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', new StoreSlug],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_phone' => ['nullable', 'string', 'max:30'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ])->validate();

        return DB::transaction(function () use ($data): Store {
            $store = Store::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'status' => StoreStatus::Draft,
                'commission_rate' => $data['commission_rate'] ?? null,
            ]);

            $owner = User::query()->create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'phone' => $data['owner_phone'] ?? null,
                'password' => Str::random(40),
                'role' => UserRole::VendorOwner,
                'store_id' => $store->id,
            ]);

            ($this->issueInvitation)($owner);

            return $store;
        });
    }
}
