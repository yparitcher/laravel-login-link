<?php

use function Pest\Laravel\post;

use Spatie\LoginLink\Tests\TestSupport\Models\Admin;
use Spatie\LoginLink\Tests\TestSupport\Models\User;

it('will create and login a user', function () {
    post(route('loginLinkLogin'))->assertRedirect();

    expectUserToBeLoggedIn();
    expect(User::count())->toBe(1);
});

it('will create and login a user on a specific guard', function () {
    config()->set('auth.guards.admin', [
        'driver' => 'session',
        'provider' => 'admins',
    ]);
    config()->set('auth.providers.admins', [
        'driver' => 'eloquent',
        'model' => Admin::class,
    ]);
    config()->set('login-link.user_model', Admin::class);

    $data = ['guard' => 'admin'];

    post(route('loginLinkLogin', $data))->assertRedirect();

    expectUserToBeLoggedIn([], 'admin');
    expect(Admin::count())->toBe(1);
});

it('will not create a new user if one already exists', function () {
    post(route('loginLinkLogin'))->assertRedirect();
    post(route('loginLinkLogin'))->assertRedirect();

    expectUserToBeLoggedIn();
    expect(User::count())->toBe(1);
});

it('can create a user with specific email', function () {
    $data = ['email' => 'freek@spatie.be'];

    post(route('loginLinkLogin'), $data)->assertRedirect();

    expectUserToBeLoggedIn(['email' => 'freek@spatie.be']);
});

it('can will not create a user if automatic creation is turned off', function () {
    config()->set('login-link.automatically_create_missing_users', false);

    $data = ['email' => 'freek@spatie.be'];

    post(route('loginLinkLogin'), $data)->assertStatus(500);

    expect(User::count())->toBe(0);

    expectNotLoggedIn();
});

it('can login an existing user with a specific email', function () {
    User::factory()->create(['email' => 'freek@spatie.be']);
    $data = ['email' => 'freek@spatie.be'];

    post(route('loginLinkLogin'), $data)->assertRedirect();

    expectUserToBeLoggedIn(['email' => 'freek@spatie.be']);
    expect(User::count())->toBe(1);
});

it('can create a user with specific id', function () {
    $data = ['key' => '123'];

    post(route('loginLinkLogin'), $data)->assertRedirect();

    expectUserToBeLoggedIn(['id' => 123]);
});

it('can login an existing user with a specific id', function () {
    User::factory()->create(['id' => 123]);
    $data = ['key' => 123];

    post(route('loginLinkLogin'), $data)->assertRedirect();

    expectUserToBeLoggedIn(['id' => 123]);
    expect(User::count())->toBe(1);
});

it('can create and login a user with specific attributes', function () {
    $data = ['user_attributes' => json_encode(['role' => 'senior admin'])];

    post(route('loginLinkLogin'), $data)->assertRedirect();

    expectUserToBeLoggedIn(['role' => 'senior admin']);
    expect(User::count())->toBe(1);
});

it('can create login an existing user with specific attributes', function () {
    User::factory()->create(['role' => 'senior admin']);
    expect(User::count())->toBe(1);

    $data = ['user_attributes' => json_encode(['role' => 'senior admin'])];

    post(route('loginLinkLogin'), $data)->assertRedirect();

    expectUserToBeLoggedIn(['role' => 'senior admin']);
    expect(User::count())->toBe(1);
});

it('can create a user with both email and custom attributes', function () {
    $data = [
        'email' => 'freek@spatie.be',
        'user_attributes' => json_encode(['role' => 'senior admin']),
    ];

    post(route('loginLinkLogin'), $data)->assertRedirect();

    expectUserToBeLoggedIn([
        'email' => 'freek@spatie.be',
        'role' => 'senior admin',
    ]);
});

it('can redirect to a specific url', function () {
    $data['redirect_url'] = 'custom-url';

    post(route('loginLinkLogin'), $data)->assertRedirect('custom-url');

    expectUserToBeLoggedIn();
});

it('will redirect to the configured route name by default', function () {
    config()->set('login-link.redirect_route_name', 'customUrlRouteName');
    $data = ['redirectUrl', route('customUrlRouteName')];

    post(route('loginLinkLogin'), $data)->assertRedirect(route('customUrlRouteName'));

    expectUserToBeLoggedIn();
});

it('will throw an exception when a user model is not configured', function () {
    config()->set('login-link.user_model', null);

    post(route('loginLinkLogin'))->assertStatus(500);
});

it('will not work in the wrong environment', function () {
    config()->set('login-link.allowed_environments', ['other-environment']);

    post(route('loginLinkLogin'))->assertStatus(500);

    expect(auth()->check())->toBeFalse();
});

it('will throw an exception when no user class can be determined', function () {
    config()->set('login-link.user_model', null);
    config()->set('auth.providers.users.model', null);

    post(route('loginLinkLogin'))->assertStatus(500);
});
