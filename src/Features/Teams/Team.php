<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property bool $personal
 */
final class Team extends Model
{
    public const OWNER_KEY = 'owner_id';

    public const MEMBERSHIP_TABLE = 'team_user';

    protected $table = 'teams';

    protected $casts = ['personal' => 'boolean'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), self::OWNER_KEY);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany($this->userModel(), self::MEMBERSHIP_TABLE, 'team_id', 'user_id')->withTimestamps();
    }

    /**
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return (string) $this->owner_id === (string) $user->getKey();
    }

    /**
     * @return class-string<User>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
