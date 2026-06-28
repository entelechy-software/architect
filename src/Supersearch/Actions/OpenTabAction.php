<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Open a record in a Workspace SPA tab.
 *
 * The `$propsResolver` callable receives the record model instance and must
 * return an array of tab props (e.g. ['id' => $record->id]).
 */
final class OpenTabAction implements SearchAction
{
    /** @var (callable(mixed): array<string, mixed>)|null */
    private $propsResolver = null;

    private ?string $fallbackUrl = null;

    private function __construct(private readonly string $tabType) {}

    public static function make(string $tabType): self
    {
        return new self($tabType);
    }

    /**
     * Provide a callable that maps the record to tab props.
     *
     * @param  callable(mixed): array<string, mixed>  $resolver
     */
    public function props(callable $resolver): self
    {
        $clone = clone $this;
        $clone->propsResolver = $resolver;

        return $clone;
    }

    /**
     * Fallback URL used when the SPA tab system is unavailable (e.g. mobile).
     */
    public function fallback(string $url): self
    {
        $clone = clone $this;
        $clone->fallbackUrl = $url;

        return $clone;
    }

    /**
     * Resolve props for a concrete record; used during result rendering.
     *
     * @return array<string, mixed>
     */
    public function resolveProps(mixed $record): array
    {
        if ($this->propsResolver === null) {
            return [];
        }

        return ($this->propsResolver)($record);
    }

    public function type(): string
    {
        return 'open-tab';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'tabType' => $this->tabType,
            'fallbackUrl' => $this->fallbackUrl,
        ];
    }

    /**
     * Produce the final serialised action array for a specific result record.
     * Merges resolved props into the payload.
     *
     * @return array<string, mixed>
     */
    public function toArrayForRecord(mixed $record): array
    {
        return array_merge($this->toArray(), [
            'props' => $this->resolveProps($record),
        ]);
    }
}
