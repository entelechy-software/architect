<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Contracts;

use Entelechy\Architect\Supersearch\SupersearchHook;

/**
 * Implement this interface on an Architect table or navigator definition class
 * to inject contextual search sets into the Supersearch overlay whenever that
 * engine is mounted on the page.
 *
 * The method is called statically by SupersearchEngine at query time, so it may
 * safely instantiate closures and database queries — nothing is serialised.
 *
 * Example:
 * ```php
 * final class CasesTableDefinition implements HasSupersearchHook
 * {
 *     public static function supersearchHook(): SupersearchHook
 *     {
 *         return SupersearchHook::make()
 *             ->searchSets(AdviceSearchSet::all());
 *     }
 *
 *     public static function build(): ArchitectTableDefinition { ... }
 * }
 * ```
 */
interface HasSupersearchHook
{
    public static function supersearchHook(): SupersearchHook;
}
