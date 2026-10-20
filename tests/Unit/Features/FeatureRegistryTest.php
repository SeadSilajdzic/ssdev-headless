<?php

use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\UnknownFeatureException;
use Ssdev\Headless\Tests\Fixtures\Features\FakeModule;

it('returns enabled modules in catalog order', function () {
    $registry = new FeatureRegistry([new FakeModule('a'), new FakeModule('b'), new FakeModule('c')], ['c', 'a']);

    expect(array_map(fn (FeatureModule $module) => $module->key(), $registry->enabled()))->toBe(['a', 'c']);
});

it('rejects unknown feature keys', function () {
    new FeatureRegistry([new FakeModule('a')], ['missing']);
})->throws(UnknownFeatureException::class, 'missing');

it('reports whether a feature is enabled', function () {
    $registry = new FeatureRegistry([new FakeModule('a'), new FakeModule('b')], ['a']);

    expect($registry->isEnabled('a'))->toBeTrue()->and($registry->isEnabled('b'))->toBeFalse();
});

it('finds a module by key', function () {
    $b = new FakeModule('b');
    $registry = new FeatureRegistry([new FakeModule('a'), $b], []);

    expect($registry->find('b'))->toBe($b)->and($registry->find('x'))->toBeNull();
});
