<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ssdev\Headless\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');
