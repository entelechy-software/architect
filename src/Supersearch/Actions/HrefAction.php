<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Navigate to a URL (same-tab or new tab).
 *
 * When a callable is provided the URL is resolved against a record at render
 * time (see HrefAction::toArrayForRecord). Use static make(string) for URLs
 * that are known up-front and HrefAction::dynamic(callable) for record-
 * dependent URLs.
 */
final class HrefAction implements SearchAction
{
    private bool $newTab = false;

    /** @var string|\Closure(mixed): string */
    private string|\Closure $url;

    private function __construct(string|\Closure $url)
    {
        $this->url = $url;
    }

    public static function make(string $url): self
    {
        return new self($url);
    }

    /**
     * Create a HrefAction with a URL that is computed per-record.
     *
     * @param  callable(mixed): string  $resolver
     */
    public static function dynamic(callable $resolver): self
    {
        return new self(\Closure::fromCallable($resolver));
    }

    public function newTab(): self
    {
        $clone = clone $this;
        $clone->newTab = true;

        return $clone;
    }

    public function resolveUrl(mixed $record): string
    {
        return $this->url instanceof \Closure
            ? ($this->url)($record)
            : $this->url;
    }

    public function type(): string
    {
        return 'href';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'url' => $this->url instanceof \Closure ? '#dynamic' : $this->url,
            'newTab' => $this->newTab,
        ];
    }

    /** @return array<string, mixed> */
    public function toArrayForRecord(mixed $record): array
    {
        return [
            'type' => $this->type(),
            'url' => $this->resolveUrl($record),
            'newTab' => $this->newTab,
        ];
    }
}
