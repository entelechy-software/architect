<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications;

/**
 * Registry of all notification trigger points declared by the host app.
 *
 * Bound as a singleton in the service container. Host apps register
 * triggers in their service provider:
 *
 *   app(TriggerRegistry::class)->register(
 *       'member.approved',
 *       'Member Approved',
 *       ['memberId' => 'int', 'memberName' => 'string']
 *   );
 *
 * Or via the Architect facade:
 *
 *   Architect::notification()->registerTrigger('member.approved', 'Member Approved', [...]);
 */
final class TriggerRegistry
{
    /**
     * @var array<string, array{label: string, context: array<string, string>}>
     */
    private array $triggers = [];

    /**
     * Register a trigger.
     *
     * @param  string  $key  Dot-namespaced trigger identifier (e.g. 'member.approved').
     * @param  string  $label  Human-readable label for the rules UI.
     * @param  array<string, string>  $context  Map of context variable name => type hint.
     */
    public function register(string $key, string $label, array $context = []): void
    {
        $this->triggers[$key] = ['label' => $label, 'context' => $context];
    }

    public function has(string $key): bool
    {
        return isset($this->triggers[$key]);
    }

    /**
     * @return array<string, array{label: string, context: array<string, string>}>
     */
    public function all(): array
    {
        return $this->triggers;
    }
}
