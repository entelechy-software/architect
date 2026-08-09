<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Livewire;

use Entelechy\Architect\Content\ArchitectContentDefinition;
use Entelechy\Architect\Content\Contracts\ProvidesContentDefinition;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Standalone read-only content (record-detail) renderer.
 *
 * Mounted with the FQCN of a host-app class exposing a static
 * `definition(): ArchitectContentDefinition` method — mirrors
 * Forms\Livewire\FormEngine's definitionClass convention. The record to
 * display is resolved inside that static method (e.g. via route model
 * binding) and baked into the definition via ContentBuilder::record(),
 * so nothing record-specific needs to survive Livewire's wire snapshot.
 *
 * Blade usage:
 *   {{ '<livewire:architect-content-engine :definition-class="\App\Content\MemberDetail::class" />' }}
 */
class ContentEngine extends Component
{
    public string $definitionClass;

    public function mount(string $definitionClass): void
    {
        $this->definitionClass = $definitionClass;
    }

    private function resolveDefinition(): ArchitectContentDefinition
    {
        $class = $this->definitionClass;

        if (! class_exists($class) || ! is_subclass_of($class, ProvidesContentDefinition::class)) {
            throw new \LogicException(
                "ContentEngine: '{$class}' must implement ".ProvidesContentDefinition::class
            );
        }

        /** @var ArchitectContentDefinition $def */
        $def = $class::definition();

        return $def;
    }

    public function render(): View
    {
        return view('architect::content.engine', [
            'definition' => $this->resolveDefinition(),
        ]);
    }
}
