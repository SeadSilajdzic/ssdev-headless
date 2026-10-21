<?php

namespace Ssdev\Headless\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ssdev\Headless\Account\PersonalAccessTokenService;

class CreateApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * A token never gets a permission its owner does not have.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['present', 'array'],
            'abilities.*' => ['string', 'distinct', Rule::in(app(PersonalAccessTokenService::class)->grantableAbilities($this->user()))],
        ];
    }
}
