<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Download a file.
 *
 * The `$urlResolver` callable receives the record and returns a download URL.
 */
final class DownloadAction implements SearchAction
{
    /** @var callable(mixed): string */
    private $urlResolver;

    private function __construct(callable $urlResolver)
    {
        $this->urlResolver = $urlResolver;
    }

    public static function make(callable $urlResolver): self
    {
        return new self($urlResolver);
    }

    public function resolveUrl(mixed $record): string
    {
        return (string) ($this->urlResolver)($record);
    }

    public function type(): string
    {
        return 'download';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['type' => $this->type()];
    }

    /** @return array<string, mixed> */
    public function toArrayForRecord(mixed $record): array
    {
        return array_merge($this->toArray(), ['url' => $this->resolveUrl($record)]);
    }
}
