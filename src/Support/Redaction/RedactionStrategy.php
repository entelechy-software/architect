<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support\Redaction;

use Closure;

/**
 * Describes how a redacted value is masked for display.
 *
 * Value object consumed by {@see Redactable}. Deliberately does not
 * scale the mask run to the source value's length — a mask like
 * "••••1234" that always uses a fixed-length run avoids leaking the
 * true length of the underlying secret (e.g. distinguishing a 9-digit
 * SSN from a 16-digit card number).
 */
final readonly class RedactionStrategy
{
    private const MASK_RUN = 4;

    private function __construct(
        private string $mode,
        private int $show = 4,
        private string $side = 'end',
        private string $mask = '•',
        private ?Closure $callback = null,
    ) {}

    /** Replace the entire value with a fixed-length run of $mask. */
    public static function full(string $mask = '•', int $length = 8): self
    {
        return new self(mode: 'full', mask: $mask, show: $length);
    }

    /**
     * Keep the first/last $show characters visible, mask the rest.
     *
     * @param  string  $side  'end' keeps the trailing characters visible
     *                        (e.g. card numbers); 'start' keeps the
     *                        leading characters visible.
     */
    public static function partial(int $show = 4, string $side = 'end', string $mask = '•'): self
    {
        return new self(mode: 'partial', show: $show, side: $side === 'start' ? 'start' : 'end', mask: $mask);
    }

    /** Fully custom masking logic: fn (mixed $value): string. */
    public static function custom(Closure $callback): self
    {
        return new self(mode: 'custom', callback: $callback);
    }

    /**
     * Resolve a named preset (used by `->redact('full')` / `->redact('partial')`
     * shorthand) into a strategy instance with default parameters.
     */
    public static function preset(string $name): self
    {
        return match ($name) {
            'full' => self::full(),
            'partial' => self::partial(),
            default => throw new \InvalidArgumentException(
                "Unknown redaction preset '{$name}'. Use 'full', 'partial', or pass a RedactionStrategy instance."
            ),
        };
    }

    public function apply(mixed $value): string
    {
        if ($this->mode === 'custom') {
            /** @var Closure $callback */
            $callback = $this->callback;

            return (string) $callback($value);
        }

        $value = (string) $value;

        if ($this->mode === 'full') {
            return str_repeat($this->mask, max($this->show, self::MASK_RUN));
        }

        // Partial: never reveal more of the value than it actually contains,
        // and always mask with a fixed-length run regardless of the
        // original value's length.
        $show = min($this->show, strlen($value));
        $visible = $show > 0
            ? ($this->side === 'start' ? substr($value, 0, $show) : substr($value, -$show))
            : '';
        $maskRun = str_repeat($this->mask, self::MASK_RUN);

        return $this->side === 'start' ? $visible.$maskRun : $maskRun.$visible;
    }
}
