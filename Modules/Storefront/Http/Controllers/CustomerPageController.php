<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Auth\Support\IntendedUrl;
use Modules\Storefront\Pages\AccountPageData;
use Modules\Storefront\Pages\AddressesPageData;
use Modules\Storefront\Pages\ForgotPasswordPageData;
use Modules\Storefront\Pages\LoginPageData;
use Modules\Storefront\Pages\RegisterPageData;
use Modules\Storefront\Pages\ResetPasswordPageData;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Customer-facing pages: login, register, password recovery, account and
 * addresses. The forms post to the Auth module's endpoints.
 */
class CustomerPageController
{
    public function __construct(
        private readonly StorefrontPresenter $presenter,
        private readonly ThemeRenderer $renderer,
    ) {}

    public function login(Request $request): Response|RedirectResponse
    {
        return $this->guestOnly() ?? $this->renderer->render(new LoginPageData(
            $this->presenter->shell(),
            $this->seo('login'),
            redirect: IntendedUrl::sanitize($request->query('redirect'), Store::current()),
        ));
    }

    public function register(Request $request): Response|RedirectResponse
    {
        return $this->guestOnly() ?? $this->renderer->render(new RegisterPageData(
            $this->presenter->shell(),
            $this->seo('register'),
            redirect: IntendedUrl::sanitize($request->query('redirect'), Store::current()),
        ));
    }

    public function forgotPassword(): Response|RedirectResponse
    {
        return $this->guestOnly() ?? $this->renderer->render(new ForgotPasswordPageData(
            $this->presenter->shell(),
            $this->seo('forgot_password'),
        ));
    }

    public function resetPassword(Request $request, string $store, string $token): Response|RedirectResponse
    {
        return $this->guestOnly() ?? $this->renderer->render(new ResetPasswordPageData(
            $this->presenter->shell(),
            $this->seo('reset_password'),
            token: $token,
            email: (string) $request->query('email', ''),
        ));
    }

    public function account(): Response
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return $this->renderer->render(new AccountPageData(
            $this->presenter->shell(),
            $this->seo('account'),
            customer: [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'emailVerified' => $customer->hasVerifiedEmail(),
            ],
        ));
    }

    public function addresses(): Response
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return $this->renderer->render(new AddressesPageData(
            $this->presenter->shell(),
            $this->seo('addresses'),
            addresses: $customer->addresses()->orderByDesc('is_default')->orderBy('id')->get()->map(fn (Address $address): array => [
                'id' => $address->id,
                'label' => $address->label,
                'recipientName' => $address->recipient_name,
                'phone' => $address->phone,
                'governorate' => $address->governorate,
                'governorateName' => $address->governorateName(),
                'city' => $address->city,
                'addressLine' => $address->address_line,
                'notes' => $address->notes,
                'isDefault' => $address->is_default,
            ])->all(),
            governorates: collect(config('customers.governorates'))->map(fn (string $name, string $value): array => ['value' => $value, 'name' => $name])->values()->all(),
            maxAddresses: config('customers.max_addresses'),
        ));
    }

    /**
     * A signed-in Customer has no use for these pages.
     */
    private function guestOnly(): ?RedirectResponse
    {
        return Auth::guard('customer')->check() ? redirect('/'.Store::current()->slug.'/account') : null;
    }

    /**
     * @return array{title: string, description: string|null, image: string|null}
     */
    private function seo(string $page): array
    {
        return $this->presenter->seo(__("storefront::pages.{$page}").' | '.Store::current()->name);
    }
}
