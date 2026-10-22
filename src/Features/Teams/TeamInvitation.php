<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
