<?php

use App\Models\User;

test('root redirects guests to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('root redirects authenticated users to dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/dashboard');
});
