<?php

namespace Ssdev\Headless\Features\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SwitchTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['team_id' => ['required', 'integer']];
    }

    public function teamId(): int
    {
        return (int) $this->validated('team_id');
    }
}
