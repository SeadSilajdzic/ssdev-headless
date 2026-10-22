<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

/**
 * @property int $id
 * @property int $team_id
 * @property string $email
 * @property int|null $role_id
 * @property int|null $invited_by
 */
final class TeamInvitation extends Model
{
    protected $table = 'team_invitations';

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function isFor(User $user): bool
    {
        return mb_strtolower((string) $user->getAttribute('email')) === $this->email;
    }
}
