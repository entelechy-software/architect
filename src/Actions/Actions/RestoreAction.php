<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Actions;

/**
 * Restores a soft-deleted Eloquent model record.
 *
 * Requires the model to use the SoftDeletes trait.
 */
class RestoreAction extends Action
{
    protected string $label = 'Restore';

    protected string $color = 'success';

    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void
    {
        if ($this->actionCallback !== null) {
            parent::run($record, $data);

            return;
        }

        $model = $this->getModelOrFail($record);

        // SoftDeletes::restore() exists on models using the trait;
        // call via method_exists to satisfy static analysis on base Model.
        if (method_exists($model, 'restore')) {
            $model->restore();
        }
    }
}
