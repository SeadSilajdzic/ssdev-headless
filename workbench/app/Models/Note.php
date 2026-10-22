<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Ssdev\Headless\Features\Tenancy\BelongsToTenant;

/**
 * An application model kept per tenant.
 */
class Note extends Model
{
    use BelongsToTenant;

    protected $fillable = ['title'];
}
