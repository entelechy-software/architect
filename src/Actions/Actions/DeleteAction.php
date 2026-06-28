<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Actions;

/**
 * Soft-deletes the resolved Eloquent model record.
 *
 * Defaults to requiring confirmation and being styled as destructive.
 * Calls $record->delete() — works with both hard-delete models and those
 * using the SoftDeletes trait.
 */
class DeleteAction extends Action
{
    protected string $label = 'Delete';

    protected string $color = 'danger';

    protected bool $destructive = true;

    protected bool $confirmationRequired = true;

    protected string $confirmationTitle = 'Delete record?';

    protected string $confirmationMessage = 'This action cannot be undone.';

    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void
    {
        if ($this->actionCallback !== null) {
            parent::run($record, $data);

            return;
        }

        $model = $this->getModelOrFail($record);
        $model->delete();
    }
}
