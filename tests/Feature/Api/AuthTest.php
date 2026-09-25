<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('a customer can register and gets a token', function () {
    Notification::fake();

    $response = $this->postJson(route('auth.register'), [
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']])
        ->assertJsonPath('user.role', 'customer');

    Notification::assertSentTo(User::query()->sole(), VerifyEmail::class);

    $this->withToken($response->json('token'))
        ->getJson(route('auth.user'))
        ->assertOk()
        ->assertJsonPath('data.email', 'maria@example.com');
});

test('a user can log in and log out', function () {
    $user = User::factory()->create();

    $token = $this->postJson(route('auth.login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->json('token');

    $this->withToken($token)->postJson(route('auth.logout'))->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

test('a wrong password is rejected', function () {
    $user = User::factory()->create();

    $this->postJson(route('auth.login'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('deactivated users cannot log in or use old tokens', function () {
    $user = User::factory()->inactive()->create();

    $this->postJson(route('auth.login'), ['email' => $user->email, 'password' => 'password'])
        ->assertJsonValidationErrors('email');

    $this->withToken($user->createToken('old')->plainTextToken)
        ->getJson(route('auth.user'))
        ->assertForbidden();
});

test('guests get a 401 from protected routes', function () {
    $this->getJson(route('auth.user'))->assertUnauthorized();
});

test('a user can update their profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson(route('auth.user.update'), ['name' => 'New Name', 'email' => $user->email, 'phone' => '09171234567'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.phone', '09171234567');
});

test('a user can change their password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson(route('auth.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertNoContent();

    $this->postJson(route('auth.login'), ['email' => $user->email, 'password' => 'new-password'])->assertOk();
});

test('password reset emails link to the frontend and the token resets the password', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://shop.example.com']);
    $user = User::factory()->create();

    $this->postJson(route('password.email'), ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        expect($notification->toMail($user)->actionUrl)->toStartWith('https://shop.example.com/reset-password?token=');

        $this->postJson(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertOk();

        return true;
    });

    $this->postJson(route('auth.login'), ['email' => $user->email, 'password' => 'brand-new-password'])->assertOk();
});

test('the verification link verifies the email and redirects to the frontend', function () {
    config(['app.frontend_url' => 'https://shop.example.com']);
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertRedirect('https://shop.example.com/?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('a user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->deleteJson(route('auth.user.destroy'), ['password' => 'password'])
        ->assertNoContent();

    expect($user->fresh())->toBeNull();
});
