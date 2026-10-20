<?php

namespace Ssdev\Headless\Installation;

final class StepResult
{
    private function __construct(
        public readonly StepStatus $status,
        public readonly string $description,
        public readonly ?string $instructions = null,
    ) {}

    public static function changed(string $description): self
    {
        return new self(StepStatus::Changed, $description);
    }

    public static function skipped(string $description): self
    {
        return new self(StepStatus::Skipped, $description);
    }

    public static function manual(string $description, string $instructions): self
    {
        return new self(StepStatus::Manual, $description, $instructions);
    }

    /**
     * @param  list<self>  $parts
     */
    public static function combine(string $description, array $parts): self
    {
        $instructions = array_filter(array_map(fn (self $part) => $part->instructions, $parts));

        if ($instructions !== []) {
            return self::manual($description, implode(PHP_EOL, $instructions));
        }

        foreach ($parts as $part) {
            if ($part->status === StepStatus::Changed) {
                return self::changed($description);
            }
        }

        return self::skipped($description);
    }
}
