<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;

/**
 * Immutable custom form launch configuration for table create/modify flows.
 */
final class CustomForm
{
    public function __construct(
        /** @var class-string Host-app class exposing static definition(). */
        public readonly string $definitionClass,
        /** One of: modal, slide-over, same-window-page, new-window, tabs-manager. */
        public readonly string $mode,
        /** URL used by same-window-page/new-window and as tabs fallback. */
        public readonly ?string $url = null,
        /** DynamicTabType key used when mode = tabs-manager. */
        public readonly ?string $tabType = null,
        /** Query-string key consumed on return for callback-refresh hooks. */
        public readonly ?string $callbackQueryKey = 'architect_refresh',
        /** Whether opener listens for postMessage refresh events from child windows. */
        public readonly bool $postMessageRefresh = true,
    ) {
        if (! in_array($mode, ['modal', 'slide-over', 'same-window-page', 'new-window', 'tabs-manager'], true)) {
            throw new \InvalidArgumentException(
                "customForm mode must be one of: modal, slide-over, same-window-page, new-window, tabs-manager. Got '{$mode}'"
            );
        }

        if (! class_exists($definitionClass)) {
            throw new \InvalidArgumentException("customForm definition class [{$definitionClass}] does not exist.");
        }

        if (! is_subclass_of($definitionClass, ArchitectDefinitionProvider::class)) {
            throw new \InvalidArgumentException(
                "customForm definition class [{$definitionClass}] must implement ".ArchitectDefinitionProvider::class
            );
        }

        if (in_array($mode, ['same-window-page', 'new-window', 'tabs-manager'], true) && ($url === null || trim($url) === '')) {
            throw new \InvalidArgumentException(
                "customForm mode '{$mode}' requires a non-empty url."
            );
        }

        if ($mode === 'tabs-manager' && ($tabType === null || trim($tabType) === '')) {
            throw new \InvalidArgumentException('customForm mode tabs-manager requires a non-empty tabType.');
        }

        if ($callbackQueryKey !== null && trim($callbackQueryKey) === '') {
            throw new \InvalidArgumentException('customForm callbackQueryKey must be null or a non-empty string.');
        }
    }
}
