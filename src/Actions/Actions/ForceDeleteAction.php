<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Actions;

/**
 * Permanently deletes a soft-deleted Eloquent model record.
 *
 * Requires the model to use the SoftDeletes trait.
 */
class ForceDeleteAction extends Action
{
    protected string $label = 'Permanently Delete';

    protected string $color = 'danger';

    protected bool $destructive = true;

    protected bool $confirmationRequired = true;

    protected string $confirmationTitle = 'Permanently delete?';

    protected string $confirmationMessage = 'This will permanently remove the record and cannot be undone.';

    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void
    {
        if ($this->actionCallback !== null) {
            parent::run($record, $data);

            return;
        }

        $model = $this->getModelOrFail($record);
        $model->forceDelete();
    }
}
