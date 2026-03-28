<?php

use App\Models\User;

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('shows login page to guests', function () {
    $this->get('/login')->assertSuccessful();
});

it('redirects authenticated users away from login', function () {
    $this->actingAs(User::factory()->create())
        ->get('/login')
        ->assertRedirect();
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('correct')]);

    $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors();

    $this->assertGuest();
});

it('logs out authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect();

    $this->assertGuest();
});

it('allows changing password with correct current password', function () {
    $user = User::factory()->create(['password' => bcrypt('oldpass123')]);

    $this->actingAs($user)
        ->put('/password/change', [
            'current_password' => 'oldpass123',
            'new_password' => 'newpass456',
            'new_password_confirmation' => 'newpass456',
        ])
        ->assertRedirect();

    expect(auth()->attempt(['email' => $user->email, 'password' => 'newpass456']))->toBeTrue();
});

it('rejects password change with wrong current password', function () {
    $user = User::factory()->create(['password' => bcrypt('oldpass123')]);

    $this->actingAs($user)
        ->put('/password/change', [
            'current_password' => 'wrongpass',
            'new_password' => 'newpass456',
            'new_password_confirmation' => 'newpass456',
        ])
        ->assertSessionHasErrors('current_password');
});
