<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Actions;

/**
 * Creates a duplicate of the Eloquent model record.
 */
class ReplicateAction extends Action
{
    protected string $label = 'Duplicate';

    protected string $color = 'secondary';

    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void
    {
        if ($this->actionCallback !== null) {
            parent::run($record, $data);

            return;
        }

        $model = $this->getModelOrFail($record);
        $model->replicate()->save();
    }
}
