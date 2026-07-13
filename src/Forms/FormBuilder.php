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

    private ?string $onSavedDispatchEvent = null;

    /** @var array<string, mixed> */
    private array $onSavedDispatchPayload = [];

    private ?Closure $onSaveSuccess = null;

    private ?Closure $onSaveFailure = null;

    private ?string $supersearchLabel = null;

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

    /**
     * Dispatch an additional custom browser event after a successful save,
     * alongside the standard architect:form:saved event (FORMS_FEATURE_PLAN.md
     * Phase 5). Useful for host-app UI that needs to refresh independently
     * of the generic saved event, e.g. a sibling Livewire component listing
     * the records this form manages.
     *
     * @param  array<string, mixed>  $payload  Merged into the versioned EventPayload alongside form_key/version/timestamp.
     */
    public function onSavedDispatch(string $event, array $payload = []): static
    {
        $this->onSavedDispatchEvent = $event;
        $this->onSavedDispatchPayload = $payload;

        return $this;
    }

    /**
     * Register success/failure callbacks invoked around saveUsing(),
     * intended to call Architect::toast()/Architect::alert() from the
     * existing Notifications subsystem — Forms never implements its own
     * notification rendering (FORMS_FEATURE_PLAN.md Architectural
     * Principle #10). $failure receives the Throwable saveUsing() threw;
     * the exception is always rethrown afterward regardless of whether a
     * failure callback is registered, so Livewire's own error handling is
     * never suppressed.
     */
    public function notifyOnSave(?Closure $success = null, ?Closure $failure = null): static
    {
        $this->onSaveSuccess = $success;
        $this->onSaveFailure = $failure;

        return $this;
    }

    /**
     * Declares this form as a Supersearch entry point with the given
     * label. This records intent on the definition only — actually
     * surfacing it in the Supersearch overlay requires the host app to
     * wire Entelechy\Architect\Forms\FormSearchSet into their own
     * HasSupersearchHook implementation together with a real URL, since
     * a bare label alone has nothing to link to. See FormSearchSet's
     * docblock for the full wiring example.
     */
    public function exposeToSupersearch(string $label): static
    {
        $this->supersearchLabel = $label;

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
            onSavedDispatchEvent: $this->onSavedDispatchEvent,
            onSavedDispatchPayload: $this->onSavedDispatchPayload,
            onSaveSuccess: $this->onSaveSuccess,
            onSaveFailure: $this->onSaveFailure,
            supersearchLabel: $this->supersearchLabel,
        );
    }
}
