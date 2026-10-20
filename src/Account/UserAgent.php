<?php

namespace Ssdev\Headless\Account;

final class UserAgent
{
    // Order matters: iPhones claim "Mac OS X", Android claims "Linux".
    private const PLATFORMS = [
        'iOS' => '/iPhone|iPad|iPod/',
        'Android' => '/Android/',
        'Windows' => '/Windows/',
        'macOS' => '/Macintosh|Mac OS X/',
        'Linux' => '/Linux|X11/',
    ];

    // Order matters: Edge and Opera claim "Chrome", Chrome claims "Safari".
    private const BROWSERS = [
        'Edge' => '/Edg(e|A|iOS)?\//',
        'Opera' => '/OPR\/|Opera/',
        'Chrome' => '/Chrome\/|CriOS\//',
        'Firefox' => '/Firefox\/|FxiOS\//',
        'Safari' => '/Safari\//',
    ];

    public function __construct(private ?string $userAgent) {}

    public function platform(): ?string
    {
        return $this->match(self::PLATFORMS);
    }

    public function browser(): ?string
    {
        return $this->match(self::BROWSERS);
    }

    /**
     * @param  array<string, string>  $patterns
     */
    private function match(array $patterns): ?string
    {
        if ($this->userAgent === null) {
            return null;
        }

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $this->userAgent) === 1) {
                return $name;
            }
        }

        return null;
    }
}
