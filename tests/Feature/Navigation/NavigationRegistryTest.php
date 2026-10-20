<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Workbench\App\Models\User;

beforeEach(function () {
    Route::get('home', fn () => '')->name('home');
    Route::get('reports', fn () => '')->name('reports');
});

it('builds sections for the user and hides items without permission', function () {
    Gate::define('reports.view', fn () => false);
    $registry = new NavigationRegistry;
    $registry->add(new NavigationItem('home', 'headless::navigation.dashboard', 'home', 'layout-dashboard', NavigationGroup::Main));
    $registry->add(new NavigationItem('reports', 'Reports', 'reports', 'chart-bar', NavigationGroup::Account, permission: 'reports.view'));

    $sections = $registry->forUser(User::factory()->create(), 'home');

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['group'])->toBe('main')
        ->and($sections[0]['label'])->toBe('Main')
        ->and($sections[0]['items'][0])->toMatchArray(['key' => 'home', 'label' => 'Dashboard', 'url' => route('home'), 'active' => true]);
});

it('returns no navigation for guests', function () {
    $registry = new NavigationRegistry;
    $registry->add(new NavigationItem('home', 'Home', 'home', 'house', NavigationGroup::Main));

    expect($registry->forUser(null, 'home'))->toBe([]);
});

it('orders items by order and then by registration', function () {
    $registry = new NavigationRegistry;
    $registry->add(new NavigationItem('reports', 'Reports', 'reports', 'chart-bar', NavigationGroup::Main, order: 20));
    $registry->add(new NavigationItem('home', 'Home', 'home', 'house', NavigationGroup::Main, order: 10));

    $keys = array_column($registry->forUser(User::factory()->create(), null)[0]['items'], 'key');

    expect($keys)->toBe(['home', 'reports']);
});

it('marks parent items active on child routes', function () {
    $registry = new NavigationRegistry;
    $registry->add(new NavigationItem('reports', 'Reports', 'reports', 'chart-bar', NavigationGroup::Main));

    expect($registry->forUser(User::factory()->create(), 'reports.show')[0]['items'][0]['active'])->toBeTrue();
});
