<?php

namespace Ssdev\Headless\Features\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;

/**
 * The mail links to the web page; an API client sends that link's expires and signature along instead.
 */
final class AcceptTeamInvitationRequest extends FormRequest
{
    public function authorize(UrlGenerator $urls): bool
    {
        $link = $urls->route('headless.team-invitations.show', ['invitation' => $this->route('invitation')]).'?'.http_build_query($this->only(['expires', 'signature']));

        return $urls->hasValidSignature(Request::create($link));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expires' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ];
    }
}
