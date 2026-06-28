<?php

declare(strict_types=1);

namespace Entelechy\Architect\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Class-backed counterpart to resources/views/components/button.blade.php.
 *
 * Registered via Blade::componentNamespace() so dot/double-colon resolution
 * (<x-architect::button>) finds this class first; render() reuses the exact
 * same Blade view the anonymous registration also points at, so there is
 * only one markup source of truth.
 */
class Button extends Component
{
    public ?string $resolvedIcon;

    public ?string $resolvedTrailingIcon;

    public string $resolvedVariant;

    public string $resolvedSize;

    /**
     * Heroicon names left over from the Filament era — translated to Font
     * Awesome equivalents so callers don't need to be updated one-by-one.
     *
     * @var array<string, string>
     */
    private const HERO_TO_FA = [
        'heroicon-m-plus' => 'fas fa-plus',
        'heroicon-m-funnel' => 'fas fa-filter',
        'heroicon-m-x-mark' => 'fas fa-times',
        'heroicon-m-arrow-up-tray' => 'fas fa-upload',
        'heroicon-m-arrow-down-tray' => 'fas fa-download',
        'heroicon-m-view-columns' => 'fas fa-table-columns',
        'heroicon-m-pencil-square' => 'fas fa-pen-to-square',
        'heroicon-m-arrow-uturn-left' => 'fas fa-rotate-left',
        'heroicon-m-archive-box' => 'fas fa-box-archive',
        'heroicon-m-trash' => 'fas fa-trash',
        'heroicon-m-chevron-down' => 'fas fa-chevron-down',
        'heroicon-m-chevron-up' => 'fas fa-chevron-up',
        'heroicon-m-chevron-right' => 'fas fa-chevron-right',
        'heroicon-m-chevron-left' => 'fas fa-chevron-left',
        'heroicon-m-magnifying-glass' => 'fas fa-search',
        'heroicon-m-check' => 'fas fa-check',
        'heroicon-m-x-circle' => 'fas fa-circle-xmark',
        'heroicon-m-ellipsis-vertical' => 'fas fa-ellipsis-vertical',
        'heroicon-m-cog-6-tooth' => 'fas fa-gear',
        'heroicon-m-eye' => 'fas fa-eye',
        'heroicon-m-eye-slash' => 'fas fa-eye-slash',
        'heroicon-m-clipboard' => 'fas fa-clipboard',
        'heroicon-m-document-duplicate' => 'fas fa-copy',
    ];

    public function __construct(
        public string $color = 'primary',
        public ?string $variant = null,
        public bool $outlined = false,
        public string $size = 'md',
        public string $type = 'button',
        public bool $loading = false,
        public bool $disabled = false,
        public ?string $icon = null,
        public ?string $trailingIcon = null,
        public ?string $href = null,
    ) {
        $this->resolvedVariant = $variant ?? ($outlined ? 'outline' : 'solid');
        $this->resolvedSize = $size === '' ? 'md' : $size;
        $this->resolvedIcon = $icon !== null ? (self::HERO_TO_FA[$icon] ?? $icon) : null;
        $this->resolvedTrailingIcon = $trailingIcon !== null ? (self::HERO_TO_FA[$trailingIcon] ?? $trailingIcon) : null;
    }

    public function render(): View
    {
        return view('architect::components.button');
    }
}
