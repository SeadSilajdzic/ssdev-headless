<?php

namespace Ssdev\Headless\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Ssdev\Headless\Account\AvatarService;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * File::image() excludes SVG and checks the real content type; the dimensions rule reads the image itself.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxDimension = (int) config('headless.avatars.max_dimension');

        return [
            'avatar' => [
                'required',
                File::image()->types(app(AvatarService::class)->mimes())->max((int) config('headless.avatars.max_kilobytes')),
                Rule::dimensions()->maxWidth($maxDimension)->maxHeight($maxDimension),
            ],
        ];
    }
}
