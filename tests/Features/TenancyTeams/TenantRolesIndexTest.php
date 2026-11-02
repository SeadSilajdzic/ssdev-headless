<?php

use Illuminate\Support\Facades\Schema;

it('keeps the tenant and the team in the roles unique index when tenancy arrives after teams', function () {
    $unique = collect(Schema::getIndexes('roles'))->first(fn (array $index) => $index['unique'] && ! $index['primary'] && in_array('name', $index['columns'], true));

    expect($unique['columns'] ?? [])->toBe(['tenant_id', 'team_id', 'name', 'guard_name']);
});
