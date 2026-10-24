<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Mail\CustomerPasswordReset;
use Modules\Auth\Mail\CustomerVerifyEmail;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class CustomerAccountFlowsTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create(['slug' => 'store-a', 'name' => 'Store A']);
        $this->storeB = Store::factory()->create(['slug' => 'store-b', 'name' => 'Store B']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function register(string $slug, array $overrides = [])
    {
        return $this->postJson("/{$slug}/customer/register", [
            'name' => 'Mona',
            'email' => 'mona@example.com',
            'password' => 'a-strong-password',
            ...$overrides,
        ]);
    }

    private function customer(Store $store, array $attributes = []): Customer
    {
        return Customer::factory()->create(['store_id' => $store->id, 'password' => 'a-strong-password', ...$attributes]);
    }

    private function signedInAs(string $slug): ?string
    {
        $name = null;

        // A real request starts with no guard remembering the previous one.
        app('auth')->forgetGuards();

        $this->get("/{$slug}")->assertInertia(function (Assert $page) use (&$name) {
            $name = $page->toArray()['props']['customer']['name'] ?? null;
        });

        return $name;
    }

    // ----- registration -----

    public function test_registration_accepts_email_only_phone_only_or_both(): void
    {
        $this->register('store-a', ['email' => 'a@example.com', 'phone' => null])->assertCreated();
        $this->register('store-a', ['name' => 'B', 'email' => null, 'phone' => '01012345678'])->assertCreated();
        $this->register('store-a', ['name' => 'C', 'email' => 'c@example.com', 'phone' => '01112345678'])->assertCreated();

        $this->assertSame(3, Customer::query()->withoutGlobalScopes()->count());
    }

    public function test_registration_needs_an_identifier_and_a_valid_phone(): void
    {
        $this->register('store-a', ['email' => null, 'phone' => null])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
        $this->register('store-a', ['email' => null, 'phone' => '12345'])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
        $this->register('store-a', ['email' => null, 'phone' => 'call me'])->assertUnprocessable();
    }

    public function test_every_phone_spelling_reaches_the_same_stored_number(): void
    {
        $this->register('store-a', ['email' => null, 'phone' => '010 1234 5678'])->assertCreated();

        $this->assertSame('+201012345678', Customer::query()->withoutGlobalScopes()->firstOrFail()->phone);

        foreach (['01012345678', '+201012345678', '00201012345678', '+20 10 1234 5678'] as $spelling) {
            $this->register('store-a', ['name' => 'Dup', 'email' => null, 'phone' => $spelling])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['phone']);
        }
    }

    public function test_the_same_identifiers_are_independent_accounts_in_two_stores(): void
    {
        $this->register('store-a', ['email' => 'same@example.com', 'phone' => '01012345678', 'password' => 'password-for-a'])->assertCreated();
        $this->register('store-b', ['email' => 'same@example.com', 'phone' => '01012345678', 'password' => 'password-for-b'])->assertCreated();

        $this->assertSame(2, Customer::query()->withoutGlobalScopes()->where('email', 'same@example.com')->count());

        $this->postJson('/store-a/customer/login', ['identifier' => 'same@example.com', 'password' => 'password-for-b'])->assertUnprocessable();
        $this->postJson('/store-a/customer/login', ['identifier' => 'same@example.com', 'password' => 'password-for-a'])->assertOk();
    }

    public function test_a_duplicate_within_one_store_is_refused(): void
    {
        $this->register('store-a')->assertCreated();

        $this->register('store-a')->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_validation_messages_are_in_arabic(): void
    {
        $this->register('store-a', ['email' => null, 'phone' => null])
            ->assertJsonPath('errors.email.0', __('auth::customers.identifier_required', [], 'ar'));
    }

    public function test_a_browser_registration_redirects_to_the_account_page(): void
    {
        $this->post('/store-a/customer/register', ['name' => 'Mona', 'email' => 'm@example.com', 'password' => 'a-strong-password'])
            ->assertRedirect('/store-a/account');

        $this->assertSame('Mona', $this->signedInAs('store-a'));
    }

    // ----- login -----

    public function test_login_by_email_or_by_any_phone_spelling(): void
    {
        $this->customer($this->storeA, ['email' => 'e@example.com', 'phone' => '01012345678']);

        foreach (['e@example.com', '01012345678', '+201012345678', '+20 10 1234 5678'] as $identifier) {
            $this->postJson('/store-a/customer/logout');
            $this->postJson('/store-a/customer/login', ['identifier' => $identifier, 'password' => 'a-strong-password'])->assertOk();
            $this->assertNotNull($this->signedInAs('store-a'), $identifier);
        }
    }

    public function test_a_failed_login_gives_one_generic_message(): void
    {
        $this->customer($this->storeA, ['email' => 'e@example.com']);

        $wrongPassword = $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'nope-nope-nope']);
        $unknown = $this->postJson('/store-a/customer/login', ['identifier' => 'nobody@example.com', 'password' => 'nope-nope-nope']);

        $wrongPassword->assertUnprocessable();
        $this->assertSame($wrongPassword->json('errors.identifier'), $unknown->json('errors.identifier'));
    }

    public function test_login_is_rate_limited_with_the_wait_time(): void
    {
        foreach (range(1, 5) as $unused) {
            $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'wrong-wrong'])->assertUnprocessable();
        }

        $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'wrong-wrong'])
            ->assertStatus(429)
            ->assertJsonPath('message', fn (string $message) => preg_match('/\d+/', $message) === 1);

        $this->post('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'wrong-wrong'])
            ->assertSessionHasErrors('identifier');
    }

    public function test_registration_and_reset_requests_are_rate_limited_too(): void
    {
        foreach (range(1, 5) as $unused) {
            $this->postJson('/store-a/customer/forgot-password', ['email' => 'x@example.com'])->assertOk();
        }
        $this->postJson('/store-a/customer/forgot-password', ['email' => 'x@example.com'])->assertStatus(429);

        foreach (range(1, 5) as $unused) {
            $this->register('store-b', ['email' => 'dup@example.com']);
        }
        $this->register('store-b', ['email' => 'dup@example.com'])->assertStatus(429);
    }

    public function test_rotating_identifiers_from_one_address_is_limited_too(): void
    {
        foreach (range(1, 30) as $number) {
            $this->postJson('/store-a/customer/login', ['identifier' => "user{$number}@example.com", 'password' => 'wrong-wrong'])->assertUnprocessable();
        }

        $this->postJson('/store-a/customer/login', ['identifier' => 'user31@example.com', 'password' => 'wrong-wrong'])->assertStatus(429);
    }

    public function test_reset_submissions_are_rate_limited(): void
    {
        foreach (range(1, 5) as $unused) {
            $this->postJson('/store-a/customer/reset-password', ['email' => 'e@example.com', 'token' => 'x', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])->assertUnprocessable();
        }

        $this->postJson('/store-a/customer/reset-password', ['email' => 'e@example.com', 'token' => 'x', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])->assertStatus(429);
    }

    public function test_a_throttled_forgot_password_form_shows_the_wait_on_the_email_field(): void
    {
        foreach (range(1, 5) as $unused) {
            $this->post('/store-a/customer/forgot-password', ['email' => 'x@example.com']);
        }

        $this->post('/store-a/customer/forgot-password', ['email' => 'x@example.com'])->assertSessionHasErrors('email');
    }

    public function test_a_deactivated_customer_cannot_log_in_and_a_session_ends_on_the_next_request(): void
    {
        $customer = $this->customer($this->storeA, ['email' => 'e@example.com']);

        $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'a-strong-password'])->assertOk();
        $this->assertNotNull($this->signedInAs('store-a'));

        $customer->update(['is_active' => false]);
        $this->assertNull($this->signedInAs('store-a'));

        $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'a-strong-password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.identifier.0', __('auth::customers.deactivated'));

        $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'wrong-wrong'])
            ->assertJsonPath('errors.identifier.0', __('auth::customers.failed'));
    }

    public function test_a_session_from_one_store_is_not_valid_in_another_but_survives_in_its_own(): void
    {
        $this->customer($this->storeA, ['email' => 'e@example.com']);
        $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'a-strong-password']);

        $this->assertNull($this->signedInAs('store-b'));
        $this->assertNotNull($this->signedInAs('store-a'));
    }

    public function test_logout_forgets_the_remembered_return_address(): void
    {
        $this->customer($this->storeA, ['email' => 'e@example.com']);
        $this->get('/store-a/account');
        $this->assertSame('/store-a/account', session('customer.intended'));

        $this->post('/store-a/customer/logout');

        $this->assertNull(session('customer.intended'));
    }

    public function test_logout_signs_the_customer_out(): void
    {
        $this->customer($this->storeA, ['email' => 'e@example.com']);
        $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'a-strong-password']);

        $this->post('/store-a/customer/logout')->assertRedirect('/store-a');

        $this->assertNull($this->signedInAs('store-a'));
    }

    // ----- password reset -----

    private function requestReset(string $slug, string $email): ?CustomerPasswordReset
    {
        $this->postJson("/{$slug}/customer/forgot-password", ['email' => $email])->assertOk();

        $sent = Mail::sent(CustomerPasswordReset::class)->filter(fn ($mail) => $mail->hasTo($email) && $mail->store->slug === $slug)->last();

        return $sent;
    }

    private function tokenFrom(CustomerPasswordReset $mail): string
    {
        preg_match('#/reset-password/([^?]+)#', $mail->resetUrl, $matches);

        return $matches[1];
    }

    public function test_the_reset_response_is_the_same_whether_or_not_an_account_exists(): void
    {
        Mail::fake();
        $this->customer($this->storeA, ['email' => 'real@example.com']);

        $real = $this->postJson('/store-a/customer/forgot-password', ['email' => 'real@example.com']);
        $unknown = $this->postJson('/store-a/customer/forgot-password', ['email' => 'ghost@example.com']);

        $this->assertSame($real->getContent(), $unknown->getContent());
        Mail::assertSent(CustomerPasswordReset::class, 1);
    }

    public function test_a_reset_link_changes_the_password_once(): void
    {
        Mail::fake();
        $customer = $this->customer($this->storeA, ['email' => 'e@example.com']);
        $token = $this->tokenFrom($this->requestReset('store-a', 'e@example.com'));

        $payload = ['email' => 'e@example.com', 'token' => $token, 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'];

        $this->postJson('/store-a/customer/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check('brand-new-password', $customer->fresh()->password));

        $this->postJson('/store-a/customer/reset-password', [...$payload, 'password' => 'another-password-1', 'password_confirmation' => 'another-password-1'])
            ->assertUnprocessable();
    }

    public function test_a_reset_link_expires(): void
    {
        Mail::fake();
        $this->customer($this->storeA, ['email' => 'e@example.com']);
        $token = $this->tokenFrom($this->requestReset('store-a', 'e@example.com'));

        $this->travel(61)->minutes();

        $this->postJson('/store-a/customer/reset-password', ['email' => 'e@example.com', 'token' => $token, 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])
            ->assertUnprocessable();
    }

    public function test_a_wrong_or_missing_token_is_refused(): void
    {
        Mail::fake();
        $this->customer($this->storeA, ['email' => 'e@example.com']);
        $this->requestReset('store-a', 'e@example.com');

        $this->postJson('/store-a/customer/reset-password', ['email' => 'e@example.com', 'token' => 'wrong', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])
            ->assertUnprocessable();
    }

    public function test_a_link_from_one_store_cannot_reset_another_stores_account(): void
    {
        Mail::fake();
        $a = $this->customer($this->storeA, ['email' => 'same@example.com']);
        $b = $this->customer($this->storeB, ['email' => 'same@example.com']);
        $tokenA = $this->tokenFrom($this->requestReset('store-a', 'same@example.com'));

        $this->postJson('/store-b/customer/reset-password', ['email' => 'same@example.com', 'token' => $tokenA, 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])
            ->assertUnprocessable();

        $this->assertTrue(Hash::check('a-strong-password', $b->fresh()->password));
        $this->assertTrue(Hash::check('a-strong-password', $a->fresh()->password));
    }

    public function test_pending_resets_for_the_same_email_in_two_stores_do_not_interfere(): void
    {
        Mail::fake();
        $this->customer($this->storeA, ['email' => 'same@example.com']);
        $b = $this->customer($this->storeB, ['email' => 'same@example.com']);
        $tokenA = $this->tokenFrom($this->requestReset('store-a', 'same@example.com'));
        $tokenB = $this->tokenFrom($this->requestReset('store-b', 'same@example.com'));

        $this->postJson('/store-a/customer/reset-password', ['email' => 'same@example.com', 'token' => $tokenA, 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])->assertOk();
        $this->postJson('/store-b/customer/reset-password', ['email' => 'same@example.com', 'token' => $tokenB, 'password' => 'second-new-password', 'password_confirmation' => 'second-new-password'])->assertOk();

        $this->assertTrue(Hash::check('second-new-password', $b->fresh()->password));
    }

    public function test_a_new_request_replaces_the_previous_link(): void
    {
        Mail::fake();
        $this->customer($this->storeA, ['email' => 'e@example.com']);
        $first = $this->tokenFrom($this->requestReset('store-a', 'e@example.com'));
        $second = $this->tokenFrom($this->requestReset('store-a', 'e@example.com'));

        $body = ['email' => 'e@example.com', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'];
        $this->postJson('/store-a/customer/reset-password', [...$body, 'token' => $first])->assertUnprocessable();
        $this->postJson('/store-a/customer/reset-password', [...$body, 'token' => $second])->assertOk();
    }

    public function test_a_deactivated_customer_gets_no_reset_link(): void
    {
        Mail::fake();
        $this->customer($this->storeA, ['email' => 'e@example.com', 'is_active' => false]);

        $this->postJson('/store-a/customer/forgot-password', ['email' => 'e@example.com'])->assertOk();

        Mail::assertNothingSent();
    }

    public function test_a_phone_only_customer_gets_nothing_until_they_add_an_email(): void
    {
        Mail::fake();
        $this->customer($this->storeA, ['email' => null, 'phone' => '01012345678']);

        $this->postJson('/store-a/customer/forgot-password', ['email' => 'later@example.com'])->assertOk();
        Mail::assertNothingSent();

        $this->postJson('/store-a/customer/login', ['identifier' => '01012345678', 'password' => 'a-strong-password'])->assertOk();
        $this->putJson('/store-a/customer/profile', ['name' => 'Mona', 'email' => 'later@example.com', 'phone' => '01012345678'])->assertOk();
        $this->postJson('/store-a/customer/logout');

        $this->postJson('/store-a/customer/forgot-password', ['email' => 'later@example.com'])->assertOk();
        Mail::assertSent(CustomerPasswordReset::class, 1);
    }

    public function test_the_reset_email_is_arabic_and_names_the_store(): void
    {
        Mail::fake();
        $this->customer($this->storeA, ['email' => 'e@example.com']);
        $mail = $this->requestReset('store-a', 'e@example.com');

        $mail->assertSeeInHtml('Store A');
        $this->assertStringContainsString('Store A', $mail->envelope()->subject);
        $this->assertStringContainsString('/store-a/reset-password/', $mail->resetUrl);
    }

    public function test_a_reset_ends_a_signed_in_session_and_old_sessions_with_the_old_password(): void
    {
        Mail::fake();
        $customer = $this->customer($this->storeA, ['email' => 'e@example.com']);
        $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'a-strong-password']);
        $token = $this->tokenFrom($this->requestReset('store-a', 'e@example.com'));

        $this->postJson('/store-a/customer/reset-password', ['email' => 'e@example.com', 'token' => $token, 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])->assertOk();

        $this->assertNull($this->signedInAs('store-a'));
        $this->assertNotSame('', $customer->fresh()->password);
    }

    // ----- profile and password -----

    public function test_the_customer_edits_their_profile_keeping_identifiers_unique(): void
    {
        $this->customer($this->storeA, ['email' => 'taken@example.com', 'name' => 'Other']);
        $customer = $this->customer($this->storeA, ['email' => 'me@example.com']);
        $this->postJson('/store-a/customer/login', ['identifier' => 'me@example.com', 'password' => 'a-strong-password']);

        $this->putJson('/store-a/customer/profile', ['name' => 'New Name', 'email' => 'taken@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->putJson('/store-a/customer/profile', ['name' => 'New Name', 'email' => 'me@example.com', 'phone' => '010-1234-5678'])->assertOk();

        $this->assertSame('New Name', $customer->fresh()->name);
        $this->assertSame('+201012345678', $customer->fresh()->phone);
    }

    public function test_a_customer_must_keep_one_identifier(): void
    {
        $this->customer($this->storeA, ['email' => 'me@example.com', 'phone' => null]);
        $this->postJson('/store-a/customer/login', ['identifier' => 'me@example.com', 'password' => 'a-strong-password']);

        $this->putJson('/store-a/customer/profile', ['name' => 'Mona', 'email' => null, 'phone' => null])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', __('auth::customers.keep_one_identifier'));
    }

    public function test_changing_the_password_needs_the_current_one_and_keeps_this_session(): void
    {
        $customer = $this->customer($this->storeA, ['email' => 'me@example.com']);
        $this->postJson('/store-a/customer/login', ['identifier' => 'me@example.com', 'password' => 'a-strong-password']);

        $this->putJson('/store-a/customer/password', ['current_password' => 'wrong-wrong', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])
            ->assertUnprocessable()->assertJsonValidationErrors(['current_password']);

        $this->putJson('/store-a/customer/password', ['current_password' => 'a-strong-password', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertUnprocessable();

        $this->putJson('/store-a/customer/password', ['current_password' => 'a-strong-password', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])->assertOk();

        $this->assertTrue(Hash::check('brand-new-password', $customer->fresh()->password));
        $this->assertNotNull($this->signedInAs('store-a'));
    }

    public function test_other_sessions_end_when_the_password_changes(): void
    {
        $customer = $this->customer($this->storeA, ['email' => 'me@example.com']);
        $this->postJson('/store-a/customer/login', ['identifier' => 'me@example.com', 'password' => 'a-strong-password']);

        // Another device changes the password: this session no longer matches it.
        $customer->forceFill(['password' => 'changed-elsewhere-1'])->save();

        $this->assertNull($this->signedInAs('store-a'));
    }

    public function test_profile_endpoints_need_a_signed_in_customer(): void
    {
        $this->putJson('/store-a/customer/profile', ['name' => 'X', 'email' => 'x@example.com'])->assertUnauthorized();
        $this->put('/store-a/customer/profile', ['name' => 'X', 'email' => 'x@example.com'])->assertRedirect('/store-a/login');
    }

    // ----- email verification -----

    public function test_verification_is_off_by_default(): void
    {
        $this->assertFalse(config('customers.verify_email'));
        Mail::fake();

        $this->register('store-a')->assertCreated();

        Mail::assertNothingSent();
        $this->assertNotNull($this->signedInAs('store-a'));
    }

    public function test_with_verification_on_login_waits_for_the_emailed_link(): void
    {
        config(['customers.verify_email' => true]);
        Mail::fake();

        $this->register('store-a')->assertStatus(202);
        $this->assertNull($this->signedInAs('store-a'));

        $this->postJson('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.identifier.0', __('auth::customers.unverified'));

        $url = Mail::sent(CustomerVerifyEmail::class)->first()->verifyUrl;
        $this->get($url)->assertRedirect('/store-a/login');

        $this->postJson('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password'])->assertOk();
    }

    public function test_a_tampered_or_foreign_verification_link_does_nothing(): void
    {
        config(['customers.verify_email' => true]);
        Mail::fake();
        $this->register('store-a')->assertStatus(202);
        $url = Mail::sent(CustomerVerifyEmail::class)->first()->verifyUrl;

        $this->get($url.'x')->assertForbidden();
        $this->get(str_replace('/store-a/', '/store-b/', $url))->assertForbidden();
        $this->assertNull(Customer::query()->withoutGlobalScopes()->first()->email_verified_at);
    }

    public function test_a_customer_with_a_phone_can_still_log_in_by_phone_while_the_email_is_unverified(): void
    {
        config(['customers.verify_email' => true]);
        Mail::fake();
        $this->customer($this->storeA, ['email' => 'e@example.com', 'phone' => '01012345678']);

        $this->postJson('/store-a/customer/login', ['identifier' => 'e@example.com', 'password' => 'a-strong-password'])->assertUnprocessable();
        $this->postJson('/store-a/customer/login', ['identifier' => '01012345678', 'password' => 'a-strong-password'])->assertOk();
    }

    public function test_adding_an_email_later_does_not_lock_out_a_phone_only_customer(): void
    {
        config(['customers.verify_email' => true]);
        Mail::fake();
        $this->customer($this->storeA, ['email' => null, 'phone' => '01012345678']);
        $this->postJson('/store-a/customer/login', ['identifier' => '01012345678', 'password' => 'a-strong-password'])->assertOk();

        $this->putJson('/store-a/customer/profile', ['name' => 'Mona', 'email' => 'new@example.com', 'phone' => '01012345678'])->assertOk();
        Mail::assertSent(CustomerVerifyEmail::class, 1);

        $this->postJson('/store-a/customer/logout');
        $this->postJson('/store-a/customer/login', ['identifier' => '01012345678', 'password' => 'a-strong-password'])->assertOk();
    }

    public function test_verification_can_be_resent_and_does_not_affect_phone_only_customers(): void
    {
        config(['customers.verify_email' => true]);
        Mail::fake();

        $this->register('store-a')->assertStatus(202);
        $this->postJson('/store-a/customer/verify-email/resend', ['email' => 'mona@example.com'])->assertOk();
        Mail::assertSent(CustomerVerifyEmail::class, 2);

        $this->register('store-a', ['name' => 'P', 'email' => null, 'phone' => '01012345678'])->assertCreated();
        $this->postJson('/store-a/customer/login', ['identifier' => '01012345678', 'password' => 'a-strong-password'])->assertOk();
    }
}
