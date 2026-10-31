<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_replace(['name' => 'Khách kiểm thử', 'phone' => '0912345678', 'password' => 'Password@123', 'password_confirmation' => 'Password@123'], $extra);
    }

    public function test_customer_registers_without_email_and_logs_in_with_normalized_phone(): void
    {
        $this->post(route('register.submit'), $this->payload(['phone' => '+84 912 345 678', 'role' => 'admin']))->assertSessionHasNoErrors()->assertRedirect(route('shop.home'));
        $user = User::firstOrFail();
        $this->assertNull($user->email);
        $this->assertSame('0912345678', $user->login_phone);
        $this->assertSame('customer', $user->role);
        $this->post(route('logout'));
        $this->post(route('login.submit'), ['email' => '0912 345 678', 'password' => 'Password@123'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $user->update(['phone' => '0987654321']);
        $this->assertSame('0912345678', $user->fresh()->login_phone);
    }

    public function test_duplicate_phone_and_invalid_phone_are_rejected(): void
    {
        $this->post(route('register.submit'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('logout'));
        $this->post(route('register.submit'), $this->payload(['phone' => '+84912345678']))->assertSessionHasErrors('phone');
        $this->post(route('register.submit'), $this->payload(['phone' => 'not-a-phone']))->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_optional_email_and_inactive_phone_login_are_rejected(): void
    {
        $this->post(route('register.submit'), $this->payload(['email' => 'invalid']))->assertSessionHasErrors('email');
        $this->post(route('register.submit'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('logout'));
        User::firstOrFail()->forceFill(['is_active' => false])->save();
        $this->post(route('login.submit'), ['email' => '0912345678', 'password' => 'Password@123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
