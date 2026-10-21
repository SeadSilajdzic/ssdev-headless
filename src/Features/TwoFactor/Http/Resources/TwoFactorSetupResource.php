<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ssdev\Headless\Features\TwoFactor\TwoFactorSetup;

/**
 * Only ever sent while the setup is pending; the QR code is a data URI so the page needs no v-html.
 *
 * @property TwoFactorSetup $resource
 */
final class TwoFactorSetupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'secret' => $this->resource->secret,
            'otpauth_url' => $this->resource->otpauthUrl,
            'qr_code' => 'data:image/svg+xml;base64,'.base64_encode($this->resource->qrCodeSvg),
        ];
    }
}
