<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels\Panels;

use Entelechy\Architect\Panels\ArchitectPanelDefinition;
use Entelechy\Architect\Panels\Contracts\Panel;

/**
 * Panel that renders an Alpine.js-powered image carousel.
 *
 * Usage:
 *   ImageCarouselPanel::make()
 *       ->images([
 *           ['src' => '/img/hero.jpg', 'caption' => 'Welcome', 'href' => '/news/1'],
 *       ])
 *       ->autoAdvance(5)
 *       ->showDots()
 */
class ImageCarouselPanel implements Panel
{
    protected ?string $title = null;

    /**
     * Each entry: ['src' => string, 'caption' => ?string, 'href' => ?string]
     *
     * @var array<int, array{src: string, caption?: string, href?: string}>
     */
    protected array $images = [];

    protected ?int $autoAdvanceSeconds = null;

    protected bool $showDots = true;

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    public function title(string $title): static
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    /** @param array<int, array{src: string, caption?: string, href?: string}> $images */
    public function images(array $images): static
    {
        $clone = clone $this;
        $clone->images = $images;

        return $clone;
    }

    public function autoAdvance(int $seconds): static
    {
        $clone = clone $this;
        $clone->autoAdvanceSeconds = $seconds;

        return $clone;
    }

    public function showDots(bool $show = true): static
    {
        $clone = clone $this;
        $clone->showDots = $show;

        return $clone;
    }

    public function getType(): string
    {
        return 'image-carousel';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /** @return array<int, array{src: string, caption?: string, href?: string}> */
    public function getImages(): array
    {
        return $this->images;
    }

    public function getAutoAdvanceSeconds(): ?int
    {
        return $this->autoAdvanceSeconds;
    }

    public function getShowDots(): bool
    {
        return $this->showDots;
    }

    public function build(): ArchitectPanelDefinition
    {
        return new ArchitectPanelDefinition(
            type: $this->getType(),
            title: $this->title,
            config: [
                'images' => $this->images,
                'autoAdvanceSeconds' => $this->autoAdvanceSeconds,
                'showDots' => $this->showDots,
            ],
            panel: $this,
        );
    }
}
