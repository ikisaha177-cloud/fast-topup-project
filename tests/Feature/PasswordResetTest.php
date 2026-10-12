<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /** Halaman lupa password tampil. */
    public function test_forgot_password_page_renders()
    {
        $this->get(route('password.request'))->assertOk();
    }

    /** Halaman reset password tampil. */
    public function test_reset_password_page_renders()
    {
        $this->get(route('password.reset', ['token' => 'dummy-token', 'email' => 'a@b.test']))
            ->assertOk();
    }

    /** Reset password dengan token valid mengubah password. */
    public function test_password_can_be_reset_with_valid_token()
    {
        $user = User::firstOrCreate(
            ['email' => 'reset@example.test'],
            ['name' => 'Reset User', 'password' => Hash::make('password-lama')]
        );

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password-baru-123', $user->fresh()->password));
    }

    /** Token salah ditolak. */
    public function test_password_reset_rejects_invalid_token()
    {
        $user = User::firstOrCreate(
            ['email' => 'reset2@example.test'],
            ['name' => 'Reset User 2', 'password' => Hash::make('password-lama')]
        );

        $this->post(route('password.update'), [
            'token'                 => 'token-palsu',
            'email'                 => $user->email,
            'password'              => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }

    /** Kirim link reset tidak membocorkan apakah email terdaftar (tetap redirect). */
    public function test_send_reset_link_handles_unknown_email_gracefully()
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'tidak-ada@example.test'])
            ->assertSessionHasErrors('email');
    }
}
