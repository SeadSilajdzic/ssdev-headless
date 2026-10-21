<?php

namespace Ssdev\Headless\Features\Users\Criteria;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class FilterUsersByRegistrationDate implements UserCriteria
{
    public function __construct(
        private ?CarbonImmutable $from,
        private ?CarbonImmutable $to,
    ) {}

    public function apply(Builder $query): Builder
    {
        return $query
            ->when($this->from, fn (Builder $query, CarbonImmutable $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($this->to, fn (Builder $query, CarbonImmutable $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }
}
