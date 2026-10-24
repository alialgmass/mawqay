<?php

namespace Modules\Storefront\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Support\ThemeContract;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerPagesTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create(['slug' => 'store-a', 'name' => 'Store A']);
        Store::factory()->create(['slug' => 'store-b']);
    }

    private function logIn(): Customer
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'mona@example.com', 'name' => 'Mona', 'password' => 'a-strong-password']);
        $this->post('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password']);

        return $customer;
    }

    public function test_the_guest_pages_render_through_the_theme(): void
    {
        foreach (['login' => 'Login', 'register' => 'Register', 'forgot-password' => 'ForgotPassword'] as $path => $component) {
            $this->get("/store-a/{$path}")->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component("storefront/default/{$component}")
                ->where('contractVersion', ThemeContract::VERSION)
                ->where('actions.login', url('/store-a/customer/login'))
                ->where('customer', null));
        }

        $this->get('/store-a/reset-password/abc?email=mona@example.com')->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/ResetPassword')
            ->where('token', 'abc')
            ->where('email', 'mona@example.com'));
    }

    public function test_the_header_data_reflects_the_signed_in_state(): void
    {
        $this->get('/store-a')->assertInertia(fn (Assert $page) => $page
            ->where('customer', null)
            ->where('urls.login', url('/store-a/login'))
            ->where('urls.register', url('/store-a/register')));

        $this->logIn();

        $this->get('/store-a')->assertInertia(fn (Assert $page) => $page
            ->where('customer.name', 'Mona')
            ->where('urls.account', url('/store-a/account'))
            ->where('actions.logout', url('/store-a/customer/logout')));
    }

    public function test_signed_in_customers_are_sent_away_from_the_guest_pages(): void
    {
        $this->logIn();

        foreach (['login', 'register', 'forgot-password', 'reset-password/abc'] as $path) {
            $this->get("/store-a/{$path}")->assertRedirect('/store-a/account');
        }
    }

    public function test_the_account_page_shows_the_customers_details(): void
    {
        $this->logIn();

        $this->get('/store-a/account')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/Account')
            ->where('customer.name', 'Mona')
            ->where('customer.email', 'mona@example.com')
            ->where('customer.emailVerified', false));
    }

    public function test_the_addresses_page_lists_only_the_customers_own_with_the_governorates(): void
    {
        $mona = $this->logIn();
        $mine = Address::factory()->forCustomer($mona)->default()->create(['label' => 'Home']);
        $other = Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'other@example.com']);
        Address::factory()->forCustomer($other)->create(['label' => 'Not mine']);

        $this->get('/store-a/account/addresses')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/Addresses')
            ->has('addresses', 1)
            ->where('addresses.0.id', $mine->id)
            ->where('addresses.0.isDefault', true)
            ->where('addresses.0.governorateName', 'القاهرة')
            ->has('governorates', 27)
            ->where('maxAddresses', 10));
    }

    public function test_a_guest_opening_an_account_page_is_sent_to_login_and_back_afterwards(): void
    {
        Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'mona@example.com', 'password' => 'a-strong-password']);

        $this->get('/store-a/account/addresses')->assertRedirect('/store-a/login');

        $this->post('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password'])
            ->assertRedirect('/store-a/account/addresses');
    }

    public function test_the_return_address_can_also_come_from_the_page_that_sent_the_guest(): void
    {
        Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'mona@example.com', 'password' => 'a-strong-password']);

        $this->get('/store-a/login?redirect=/store-a/checkout')->assertInertia(fn (Assert $page) => $page->where('redirect', '/store-a/checkout'));

        $this->post('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password', 'redirect' => '/store-a/checkout'])
            ->assertRedirect('/store-a/checkout');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function craftedReturnAddresses(): array
    {
        return [
            'another site' => ['https://evil.example/steal'],
            'protocol relative' => ['//evil.example'],
            'another store' => ['/store-b/account'],
            'climbing out' => ['/store-a/../store-b'],
            'a store with the same prefix' => ['/store-abc'],
            'script' => ['javascript:alert(1)'],
            'encoded climbing' => ['/store-a/%2e%2e/store-b'],
            'encoded slash' => ['/store-a%2f%2fevil'],
            'backslash' => ['/store-a\evil'],
            'header injection' => ['/store-a/

Set-Cookie: x=1'],
        ];
    }

    #[DataProvider('craftedReturnAddresses')]
    public function test_only_pages_inside_the_same_store_are_accepted_as_a_return_address(string $bad): void
    {
        Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'mona@example.com', 'password' => 'a-strong-password']);

        $this->post('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password', 'redirect' => $bad])
            ->assertRedirect('/store-a/account');
    }

    public function test_a_crafted_return_address_is_dropped_from_the_login_page(): void
    {
        $this->get('/store-a/login?redirect=https://evil.example')->assertInertia(fn (Assert $page) => $page->where('redirect', null));
        $this->get('/store-a/login?redirect=/store-b/x')->assertInertia(fn (Assert $page) => $page->where('redirect', null));
    }

    public function test_pages_of_another_store_never_show_this_stores_customer(): void
    {
        $this->logIn();

        $this->get('/store-b/login')->assertOk()->assertInertia(fn (Assert $page) => $page->where('customer', null));
        $this->get('/store-b/account')->assertRedirect('/store-b/login');
    }

    public function test_flash_messages_reach_the_next_page(): void
    {
        $this->post('/store-a/customer/forgot-password', ['email' => 'ghost@example.com'])->assertRedirect('/store-a/forgot-password');

        $this->get('/store-a/forgot-password')->assertInertia(fn (Assert $page) => $page
            ->where('status', __('auth::customers.reset_sent')));
    }

    public function test_validation_errors_come_back_on_the_form_page(): void
    {
        $this->from('/store-a/register')
            ->post('/store-a/customer/register', ['name' => '', 'password' => 'short'])
            ->assertRedirect('/store-a/register')
            ->assertSessionHasErrors(['name', 'email', 'phone', 'password']);
    }

    public function test_the_new_pages_are_optional_for_a_theme_and_fall_back_to_the_default(): void
    {
        $this->assertContains('Login', ThemeContract::OPTIONAL_PAGES);
        $this->assertNotContains('Login', ThemeContract::REQUIRED_PAGES);
        $this->assertContains('Account', ThemeContract::REQUIRED_PAGES);
        $this->assertSame('1.2.0', ThemeContract::VERSION);
    }
}
