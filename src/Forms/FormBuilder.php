<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Closure;
use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Fluent builder for a standalone Architect form.
 *
 * Usage:
 *   Architect::form('member')
 *       ->structure([
 *           TextField::make('name')->required(),
 *           SelectField::make('status')->options([...]),
 *       ])
 *       ->fill($member)
 *       ->saveUsing(fn (array $data) => $member->update($data))
 *       ->build();
 */
final class FormBuilder
{
    /** @var array<int, StructureItem> */
    private array $structure = [];

    private ?Closure $saveUsing = null;

    private mixed $fillData = null;

    private string $formKey;

    private ?Closure $beforeSave = null;

    private ?Closure $afterSave = null;

    private ?string $redirectAfterSave = null;

    private ?int $autosaveInterval = null;

    public static function make(string $key = 'default'): static
    {
        $instance = new self;
        $instance->formKey = $key;

        return $instance;
    }

    /** @param  array<int, StructureItem>  $items */
    public function structure(array $items): static
    {
        $this->structure = $items;

        return $this;
    }

    /** Fluent shorthand: ->field(TextField::make('name')) */
    public function field(StructureItem $field): static
    {
        $this->structure[] = $field;

        return $this;
    }

    public function saveUsing(Closure $callback): static
    {
        $this->saveUsing = $callback;

        return $this;
    }

    /** Pre-fill the form with an Eloquent model or array. */
    public function fill(mixed $data): static
    {
        $this->fillData = $data;

        return $this;
    }

    /** Called immediately before saveUsing with the validated form data. */
    public function beforeSave(Closure $callback): static
    {
        $this->beforeSave = $callback;

        return $this;
    }

    /** Called immediately after saveUsing with the validated form data. */
    public function afterSave(Closure $callback): static
    {
        $this->afterSave = $callback;

        return $this;
    }

    /** Redirect to this URL after a successful save. */
    public function redirectAfterSave(string $url): static
    {
        $this->redirectAfterSave = $url;

        return $this;
    }

    /** Enable autosave polling at the given interval in seconds. */
    public function autosave(int $intervalSeconds = 30): static
    {
        $this->autosaveInterval = $intervalSeconds;

        return $this;
    }

    public function build(): ArchitectFormDefinition
    {
        return new ArchitectFormDefinition(
            key: $this->formKey,
            structure: $this->structure,
            saveUsing: $this->saveUsing,
            fillData: $this->fillData,
            beforeSave: $this->beforeSave,
            afterSave: $this->afterSave,
            redirectAfterSave: $this->redirectAfterSave,
            autosaveInterval: $this->autosaveInterval,
        );
    }
}
