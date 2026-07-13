<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Entelechy\Architect\Supersearch\Actions\HrefAction;
use Entelechy\Architect\Supersearch\SearchSets\NavigationSearchSet;

/**
 * Adapts a form/wizard definition that called ->exposeToSupersearch() into
 * a NavigationSearchSet entry — the same building block Table/Navigator
 * definitions already use via HasSupersearchHook (FORMS_FEATURE_PLAN.md
 * Phase 5, "Supersearch integration").
 *
 * A bare ->exposeToSupersearch('Create Quick Note') call only records a
 * label of intent — Supersearch entries always need a URL/action to run,
 * which the builder cannot know on its own. Wire it into your own
 * HasSupersearchHook implementation together with that URL:
 *
 *   final class NotesTableDefinition implements HasSupersearchHook
 *   {
 *       public static function supersearchHook(): SupersearchHook
 *       {
 *           return SupersearchHook::make()
 *               ->searchSet(FormSearchSet::for(QuickNoteForm::class, url: '/notes/create'));
 *       }
 *
 *       public static function build(): ArchitectTableDefinition { ... }
 *   }
 *
 * Throws if the target definition never called ->exposeToSupersearch().
 */
final class FormSearchSet
{
    /**
     * @param  class-string  $definitionClass  A class exposing a static definition(): ArchitectFormDefinition|ArchitectWizardDefinition method.
     */
    public static function for(
        string $definitionClass,
        string $url,
        string $icon = 'fas fa-plus',
        ?string $permission = null,
    ): NavigationSearchSet {
        $definition = $definitionClass::definition();

        if ($definition->supersearchLabel === null) {
            throw new \InvalidArgumentException(
                "{$definitionClass}::definition() never called ->exposeToSupersearch() — nothing to expose."
            );
        }

        return NavigationSearchSet::make()
            ->groupLabel('Forms')
            ->add(
                key: $definitionClass,
                label: $definition->supersearchLabel,
                icon: $icon,
                action: HrefAction::make($url),
                permission: $permission,
            );
    }
}
