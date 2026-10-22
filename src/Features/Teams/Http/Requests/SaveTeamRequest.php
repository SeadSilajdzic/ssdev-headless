<?php

namespace Ssdev\Headless\Features\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveTeamRequest extends FormRequest
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
        return ['name' => ['required', 'string', 'max:255']];
    }

    public function name(): string
    {
        return trim((string) $this->validated('name'));
    }
}
