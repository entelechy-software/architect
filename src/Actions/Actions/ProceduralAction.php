<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Actions;

/**
 * A generic, callback-driven action.
 *
 * Use when none of the built-in actions cover your use case:
 *
 *   class SendWelcomeEmailAction extends ProceduralAction
 *   {
 *       protected ?string $modelClass = Member::class;
 *       protected string  $label      = 'Send Welcome Email';
 *
 *       public function run(mixed $record, array $data = []): void
 *       {
 *           Mail::to($record)->send(new WelcomeEmail($record));
 *       }
 *   }
 *
 * Or fluently via the action() callback for simple cases:
 *
 *   ProceduralAction::make()
 *       ->label('Approve')
 *       ->action(fn ($record, $data) => $record->approve())
 */
class ProceduralAction extends Action
{
    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void
    {
        parent::run($record, $data);
    }
}
