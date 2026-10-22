<?php

namespace Ssdev\Headless\Features\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TransferTeamRequest extends FormRequest
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
        return ['user_id' => ['required', 'integer']];
    }

    public function userId(): int
    {
        return (int) $this->validated('user_id');
    }
}
