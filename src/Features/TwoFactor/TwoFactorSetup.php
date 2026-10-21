<?php

namespace Ssdev\Headless\Features\TwoFactor;

final class TwoFactorSetup
{
    public function __construct(
        public readonly string $secret,
        public readonly string $otpauthUrl,
        public readonly string $qrCodeSvg,
    ) {}
}
