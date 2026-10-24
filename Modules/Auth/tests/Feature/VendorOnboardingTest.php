<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\CreateStoreWithOwner;
use Modules\Auth\Actions\ResendInvitation;
use Modules\Auth\Enums\UserRole;
use Modules\Auth\Mail\VendorOwnerInvitation;
use Modules\Auth\Models\Invitation;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class VendorOnboardingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function details(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Noor Hijab',
            'slug' => 'noor-hijab',
            'owner_name' => 'Noor Ali',
            'owner_email' => 'noor@example.com',
            'owner_phone' => '+201012345678',
            'commission_rate' => null,
        ], $overrides);
    }

    /**
     * Onboards a Store and returns the invitation link that was emailed.
     */
    private function onboard(array $overrides = []): string
    {
        Mail::fake();

        app(CreateStoreWithOwner::class)($this->details($overrides));

        $link = null;
        Mail::assertSent(VendorOwnerInvitation::class, function (VendorOwnerInvitation $mail) use (&$link): bool {
            $link = $mail->acceptUrl;

            return true;
        });

        return $link;
    }

    public function test_onboarding_creates_a_draft_store_its_owner_and_one_invitation(): void
    {
        Mail::fake();

        $store = app(CreateStoreWithOwner::class)($this->details(['commission_rate' => 12]));

        $this->assertSame(StoreStatus::Draft, $store->status);
        $this->assertSame('12.00', $store->commission_rate);

        $owner = User::query()->where('email', 'noor@example.com')->firstOrFail();
        $this->assertSame(UserRole::VendorOwner, $owner->role);
        $this->assertSame($store->id, $owner->store_id);
        $this->assertSame('+201012345678', $owner->phone);
        $this->assertSame(1, Invitation::query()->count());
    }

    public function test_the_invitation_email_is_arabic_and_carries_the_link(): void
    {
        Mail::fake();
        app(CreateStoreWithOwner::class)($this->details());

        Mail::assertSent(VendorOwnerInvitation::class, function (VendorOwnerInvitation $mail): bool {
            $mail->assertHasSubject(__('auth::invitations.mail_subject', [], 'ar'));
            $mail->assertSeeInHtml('dir="rtl"', false);
            $mail->assertSeeInHtml($mail->acceptUrl, false);
            $mail->assertSeeInHtml('Noor Hijab');

            return $mail->hasTo('noor@example.com');
        });
    }

    public function test_a_failure_part_way_leaves_nothing_behind(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('mail down'));

        try {
            app(CreateStoreWithOwner::class)($this->details());
            $this->fail('The exception should propagate.');
        } catch (\RuntimeException) {
            $this->assertSame(0, Store::query()->count());
            $this->assertSame(0, User::query()->count());
            $this->assertSame(0, Invitation::query()->count());
        }
    }

    public function test_onboarding_refuses_a_bad_slug_a_taken_email_and_a_bad_commission(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'noor@example.com']);

        foreach ([['slug' => 'admin'], ['owner_email' => 'noor@example.com'], ['commission_rate' => 101], ['owner_email' => 'not-an-email']] as $bad) {
            try {
                app(CreateStoreWithOwner::class)($this->details($bad));
                $this->fail('Should have been refused: '.json_encode($bad));
            } catch (ValidationException) {
                $this->assertSame(0, Store::query()->count());
            }
        }
    }

    public function test_the_owner_sets_a_password_and_lands_in_their_vendor_panel(): void
    {
        $link = $this->onboard();

        $this->get($link)->assertOk()->assertSee('dir="rtl"', false)->assertSee('Noor Hijab');

        $this->post($link, ['password' => 'a-strong-pass', 'password_confirmation' => 'a-strong-pass'])
            ->assertRedirect('/vendor/noor-hijab');

        $owner = User::query()->where('email', 'noor@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($owner);
        $this->assertTrue(password_verify('a-strong-pass', $owner->password));

        $this->get('/vendor/noor-hijab')->assertOk();
    }

    public function test_a_used_invitation_cannot_be_replayed(): void
    {
        $link = $this->onboard();
        $this->post($link, ['password' => 'a-strong-pass', 'password_confirmation' => 'a-strong-pass']);
        auth()->guard('web')->logout();

        $this->get($link)->assertStatus(410)->assertSee(__('auth::invitations.invalid_title', [], 'ar'));
        $this->post($link, ['password' => 'another-pass-1', 'password_confirmation' => 'another-pass-1'])->assertStatus(410);
        $this->assertGuest();
    }

    public function test_an_invitation_expires_after_72_hours(): void
    {
        $link = $this->onboard();

        $this->travel(71)->hours();
        $this->get($link)->assertOk();

        $this->travel(2)->hours();
        $this->get($link)->assertStatus(410);
        $this->post($link, ['password' => 'a-strong-pass', 'password_confirmation' => 'a-strong-pass'])->assertStatus(410);
        $this->assertGuest();
    }

    public function test_a_tampered_link_is_refused(): void
    {
        $this->onboard();

        $this->get('/invitations/'.str_repeat('x', 64))->assertStatus(410);
    }

    public function test_a_weak_or_unconfirmed_password_is_rejected_and_the_link_stays_usable(): void
    {
        $link = $this->onboard();

        $this->post($link, ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->post($link, ['password' => 'a-strong-pass', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');

        $this->get($link)->assertOk();
    }

    public function test_resending_replaces_the_old_link_with_a_new_one(): void
    {
        $oldLink = $this->onboard();
        $store = Store::query()->firstOrFail();

        Mail::fake();
        app(ResendInvitation::class)($store);

        $newLink = null;
        Mail::assertSent(VendorOwnerInvitation::class, function (VendorOwnerInvitation $mail) use (&$newLink): bool {
            $newLink = $mail->acceptUrl;

            return true;
        });

        $this->assertNotSame($oldLink, $newLink);
        $this->get($oldLink)->assertStatus(410);
        $this->get($newLink)->assertOk();
    }

    public function test_resending_after_acceptance_is_refused(): void
    {
        $link = $this->onboard();
        $this->post($link, ['password' => 'a-strong-pass', 'password_confirmation' => 'a-strong-pass']);
        $store = Store::query()->firstOrFail();

        Mail::fake();

        $this->expectException(ValidationException::class);

        try {
            app(ResendInvitation::class)($store);
        } finally {
            Mail::assertNothingSent();
        }
    }

    public function test_an_invitation_for_one_store_gives_no_access_to_another(): void
    {
        $link = $this->onboard();
        $other = Store::factory()->create(['slug' => 'other-shop']);

        $this->post($link, ['password' => 'a-strong-pass', 'password_confirmation' => 'a-strong-pass']);

        $this->get('/vendor/noor-hijab')->assertOk();
        $this->assertContains($this->get("/vendor/{$other->slug}")->getStatusCode(), [403, 404]);
    }
}
