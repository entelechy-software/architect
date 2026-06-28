<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Livewire;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Toolbar\ArchitectToolbarDefinition;
use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownCheckbox;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownRadioGroup as DropdownRadioGroupItem;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownTextInput;
use Entelechy\Architect\Toolbar\Items\ToolbarDropdown;
use Entelechy\Architect\Toolbar\Items\ToolbarRadioGroup;
use Entelechy\Architect\Toolbar\Items\ToolbarSearch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Architect Toolbar Livewire engine.
 *
 * Drives ArchitectToolbarDefinition instances. Manages stateful control
 * values (radio groups, toggles), dispatches browser events on change,
 * and handles localStorage persistence by delegating reads back to Alpine.
 *
 * Typical route usage:
 *   Route::get('/advice/dashboard', ToolbarEngine::class)
 *       ->defaults('definitionClass', MyToolbarDefinition::class);
 *
 * Embedded usage (inside another Livewire view):
 *   <livewire:architect-toolbar definition-class="MyToolbarDefinition::class" />
 *
 * Or pass a pre-built definition directly via a parent component:
 *   <livewire:architect-toolbar :definition="$definition" />
 */
#[Layout('layouts.app')]
class ToolbarEngine extends Component
{
    /**
     * FQCN of the definition class to drive.
     * Must expose a public static build(): ArchitectToolbarDefinition method.
     */
    public string $definitionClass = '';

    /**
     * Machine key for this toolbar instance. Derived from the definition class
     * name when not explicitly set in the builder chain.
     */
    public string $toolbarKey = '';

    /**
     * Values for all ToolbarRadioGroup items, keyed by item key.
     *
     * @var array<string, string>
     */
    public array $radioValues = [];

    /**
     * Values for all toggle-style DropdownCheckbox items (DropdownCheckbox::toggle())
     * across all ToolbarDropdowns, keyed by "dropdownKey.itemKey" compound key.
     *
     * @var array<string, bool>
     */
    public array $toggleValues = [];

    /**
     * Values for all DropdownCheckbox items, keyed by "dropdownKey.checkboxKey".
     *
     * @var array<string, bool>
     */
    public array $checkboxValues = [];

    /**
     * Values for all DropdownRadioGroup items inside dropdowns.
     * Keyed by "dropdownKey.radioGroupKey" to distinguish from top-level ToolbarRadioGroup
     * which uses a plain itemKey.
     *
     * @var array<string, string>
     */
    public array $dropdownRadioValues = [];

    /**
     * Values for all DropdownTextInput items, keyed by "dropdownKey.inputKey".
     *
     * @var array<string, string>
     */
    public array $textValues = [];

    /**
     * Current query string for each ToolbarSearch item, keyed by item key.
     *
     * @var array<string, string>
     */
    public array $searchValues = [];

    /**
     * Suggestion results for each ToolbarSearch in suggest mode.
     * Each entry is a list of suggestion records: { value, label, sublabel?, icon? }.
     *
     * @var array<string, list<array{value: string, label: string, sublabel?: string, icon?: string}>>
     */
    public array $searchSuggestions = [];

    /**
     * Loading flag per ToolbarSearch item key — true while waiting for suggestions.
     *
     * @var array<string, bool>
     */
    public array $searchLoading = [];

    /**
     * Flag set after Alpine has pushed localStorage values into the
     * component via loadFromLocalStorage(). Until then, render() uses
     * definition defaults.
     */
    public bool $localStorageLoaded = false;

    /**
     * Flag set after Alpine has pushed URL query-param values into the
     * component via loadFromUrl(). URL values take priority over localStorage.
     */
    public bool $urlLoaded = false;

    /** Standard Engine error/loading contract — see ARCHITECT_PACKAGE_PLAN.md §0.6. */
    public bool $hasError = false;

    public string $errorMessage = '';

    public bool $isLoading = false;

    /** Per-request memoisation — not serialised by Livewire. */
    private ?ArchitectToolbarDefinition $cachedDefinition = null;

    private ?Authenticatable $cachedUser = null;

    private bool $cachedUserResolved = false;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(string $definitionClass = ''): void
    {
        if ($definitionClass !== '') {
            $this->definitionClass = $definitionClass;
        }

        try {
            $def = $this->definition();
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while loading this toolbar. Please try again.';
            report($e);

            return;
        }

        $this->toolbarKey = $def->getKey() !== ''
            ? $def->getKey()
            : Str::slug(class_basename($this->definitionClass));

        // Seed defaults from definition (may be overwritten by localStorage).
        $this->seedDefaults($def);
    }

    public function render(): View
    {
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            $definition = $this->definition();

            $permission = $definition->getPermission();
            if ($permission !== null && ! app(PermissionResolver::class)->can($this->resolveUser(), $permission)) {
                throw new AuthorizationException('You do not have permission to view this toolbar.');
            }

            $byPosition = $definition->itemsByPosition();
        } catch (AuthorizationException $e) {
            $this->hasError = true;
            $this->errorMessage = $e->getMessage();
            $this->dispatch('architect:unauthorized');
            $definition = null;
            $byPosition = [];
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while loading this toolbar. Please try again.';
            report($e);
            $definition = null;
            $byPosition = [];
        }

        return view('architect::toolbar.engine', [
            'definition' => $definition,
            'byPosition' => $byPosition,
            'user' => $this->resolveUser(),
        ]);
    }

    // ── State mutations ───────────────────────────────────────────────────────

    /**
     * Set the value of a ToolbarRadioGroup item and dispatch the change event.
     */
    public function setRadio(string $itemKey, string $value): void
    {
        $this->radioValues[$itemKey] = $value;

        $def = $this->definition();
        $item = $this->findItem($def, $itemKey);

        if (! ($item instanceof ToolbarRadioGroup)) {
            return;
        }

        $payload = array_merge($item->getChangePayload(), [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $itemKey,
            'value' => $value,
        ]);

        $this->dispatch('architect:toolbar:radio-changed', ...$payload);

        if ($item->getChangeEvent() !== null && $item->getChangeEvent() !== 'architect:toolbar:radio-changed') {
            $this->dispatch($item->getChangeEvent(), ...$payload);
        }

        $this->dispatchBoundChanged();
    }

    /**
     * Set the value of a DropdownCheckbox item and dispatch the change event.
     *
     * @param  string  $compoundKey  "dropdownKey.checkboxKey"
     */
    public function setCheckbox(string $compoundKey, bool $value): void
    {
        $this->checkboxValues[$compoundKey] = $value;

        [$dropdownKey, $checkboxKey] = explode('.', $compoundKey, 2);
        $dropdown = $this->findItem($this->definition(), $dropdownKey);

        if (! ($dropdown instanceof ToolbarDropdown)) {
            return;
        }

        $checkboxItem = null;
        foreach ($dropdown->getItems() as $child) {
            if ($child instanceof DropdownCheckbox && $child->getKey() === $checkboxKey) {
                $checkboxItem = $child;
                break;
            }
        }

        if ($checkboxItem === null) {
            return;
        }

        $payload = array_merge($checkboxItem->getChangePayload(), [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $checkboxKey,
            'value' => $value,
        ]);

        $this->dispatch('architect:toolbar:checkbox-changed', ...$payload);

        if ($checkboxItem->getChangeEvent() !== null && $checkboxItem->getChangeEvent() !== 'architect:toolbar:checkbox-changed') {
            $this->dispatch($checkboxItem->getChangeEvent(), ...$payload);
        }

        $this->dispatchBoundChanged();
    }

    /**
     * Set the selected option of a DropdownRadioGroup and dispatch the change event.
     *
     * @param  string  $compoundKey  "dropdownKey.radioGroupKey"
     */
    public function setDropdownRadio(string $compoundKey, string $value): void
    {
        $this->dropdownRadioValues[$compoundKey] = $value;

        [$dropdownKey, $radioGroupKey] = explode('.', $compoundKey, 2);
        $dropdown = $this->findItem($this->definition(), $dropdownKey);

        if (! ($dropdown instanceof ToolbarDropdown)) {
            return;
        }

        $radioGroup = null;
        foreach ($dropdown->getItems() as $child) {
            if ($child instanceof DropdownRadioGroupItem && $child->getKey() === $radioGroupKey) {
                $radioGroup = $child;
                break;
            }
        }

        if ($radioGroup === null) {
            return;
        }

        $payload = array_merge($radioGroup->getChangePayload(), [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $compoundKey,
            'value' => $value,
        ]);

        // Reuse the same standard event as ToolbarRadioGroup for consistency.
        $this->dispatch('architect:toolbar:radio-changed', ...$payload);

        if ($radioGroup->getChangeEvent() !== null && $radioGroup->getChangeEvent() !== 'architect:toolbar:radio-changed') {
            $this->dispatch($radioGroup->getChangeEvent(), ...$payload);
        }

        $this->dispatchBoundChanged();
    }

    /**
     * Set the value of a DropdownTextInput item and dispatch the change event.
     *
     * @param  string  $compoundKey  "dropdownKey.inputKey"
     */
    public function setTextValue(string $compoundKey, string $value): void
    {
        $this->textValues[$compoundKey] = $value;

        [$dropdownKey, $inputKey] = explode('.', $compoundKey, 2);
        $dropdown = $this->findItem($this->definition(), $dropdownKey);

        if (! ($dropdown instanceof ToolbarDropdown)) {
            return;
        }

        $inputItem = null;
        foreach ($dropdown->getItems() as $child) {
            if ($child instanceof DropdownTextInput && $child->getKey() === $inputKey) {
                $inputItem = $child;
                break;
            }
        }

        if ($inputItem === null) {
            return;
        }

        $payload = array_merge($inputItem->getChangePayload(), [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $inputKey,
            'value' => $value,
        ]);

        $this->dispatch('architect:toolbar:text-changed', ...$payload);

        if ($inputItem->getChangeEvent() !== null && $inputItem->getChangeEvent() !== 'architect:toolbar:text-changed') {
            $this->dispatch($inputItem->getChangeEvent(), ...$payload);
        }

        $this->dispatchBoundChanged();
    }

    /**
     * Update the search query for a simple ToolbarSearch item and dispatch
     * architect:toolbar:search-changed.
     */
    public function setSearch(string $itemKey, string $value): void
    {
        $this->searchValues[$itemKey] = $value;

        $item = $this->findItem($this->definition(), $itemKey);

        if (! ($item instanceof ToolbarSearch)) {
            return;
        }

        $payload = array_merge($item->getChangePayload(), [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $itemKey,
            'value' => $value,
            'query' => $value,
        ]);

        $this->dispatch('architect:toolbar:search-changed', ...$payload);

        if ($item->getChangeEvent() !== null && $item->getChangeEvent() !== 'architect:toolbar:search-changed') {
            $this->dispatch($item->getChangeEvent(), ...$payload);
        }

        $this->dispatchBoundChanged();
    }

    /**
     * Request suggestions from a parent Livewire component for a suggest-mode
     * ToolbarSearch item. Dispatches architect:toolbar:search-suggest-requested
     * so the parent can compute and respond with architect:toolbar:search-suggestions.
     */
    public function requestSuggestions(string $itemKey, string $query): void
    {
        $this->searchValues[$itemKey] = $query;

        $item = $this->findItem($this->definition(), $itemKey);

        if (! ($item instanceof ToolbarSearch)) {
            return;
        }

        // Clear stale suggestions and bail early if below min-char threshold.
        if (strlen($query) < $item->getMinChars()) {
            $this->searchSuggestions[$itemKey] = [];
            $this->searchLoading[$itemKey] = false;

            return;
        }

        $this->searchLoading[$itemKey] = true;

        $this->dispatch('architect:toolbar:search-suggest-requested', [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $itemKey,
            'query' => $query,
        ]);
    }

    /**
     * Called by the search partial when the user clicks a suggestion item.
     * Updates the displayed value, clears the flyout, and dispatches events.
     *
     * @param  string  $value  Machine value (e.g. record ID or slug)
     * @param  string  $label  Display label used as the input text
     */
    public function selectSuggestion(string $itemKey, string $value, string $label): void
    {
        // Show the selected label in the input field.
        $this->searchValues[$itemKey] = $label;
        $this->searchSuggestions[$itemKey] = [];
        $this->searchLoading[$itemKey] = false;

        $item = $this->findItem($this->definition(), $itemKey);

        if (! ($item instanceof ToolbarSearch)) {
            return;
        }

        $payload = array_merge($item->getChangePayload(), [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $itemKey,
            'value' => $value,
            'label' => $label,
            'query' => $label,
        ]);

        $this->dispatch('architect:toolbar:search-changed', ...$payload);
        $this->dispatch('architect:toolbar:search-suggestion-selected', ...$payload);

        if ($item->getChangeEvent() !== null && $item->getChangeEvent() !== 'architect:toolbar:search-changed') {
            $this->dispatch($item->getChangeEvent(), ...$payload);
        }

        $this->dispatchBoundChanged();
    }

    /**
     * Clear the search input and suggestion flyout for a ToolbarSearch item.
     */
    public function clearSearch(string $itemKey): void
    {
        $this->searchValues[$itemKey] = '';
        $this->searchSuggestions[$itemKey] = [];
        $this->searchLoading[$itemKey] = false;

        $item = $this->findItem($this->definition(), $itemKey);

        if (! ($item instanceof ToolbarSearch)) {
            return;
        }

        $payload = array_merge($item->getChangePayload(), [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $itemKey,
            'value' => '',
            'query' => '',
        ]);

        $this->dispatch('architect:toolbar:search-changed', ...$payload);

        if ($item->getChangeEvent() !== null && $item->getChangeEvent() !== 'architect:toolbar:search-changed') {
            $this->dispatch($item->getChangeEvent(), ...$payload);
        }

        $this->dispatchBoundChanged();
    }

    /**
     * Receive suggestion results from a parent Livewire component responding to
     * architect:toolbar:search-suggest-requested.
     *
     * The parent dispatches architect:toolbar:search-suggestions with:
     *   { toolbarKey, itemKey, results: [{ value, label, sublabel?, icon? }] }
     *
     * @param  list<array{value: string, label: string, sublabel?: string, icon?: string}>  $results
     */
    #[On('architect:toolbar:search-suggestions')]
    public function receiveSuggestions(string $toolbarKey, string $itemKey, array $results): void
    {
        if ($toolbarKey !== $this->toolbarKey) {
            return;
        }

        $this->searchLoading[$itemKey] = false;
        $this->searchSuggestions[$itemKey] = $results;
    }

    /**
     * Accept a state push from an external component (e.g. a bound Architect Table).
     * Applies values WITHOUT re-dispatching architect:toolbar:bound-changed to
     * prevent circular feedback loops.
     *
     * The external component dispatches:
     *   $this->dispatch('architect:toolbar:receive-state',
     *       toolbarKey: 'my-toolbar',
     *       state: ['radio.view' => 'card', 'search.q' => 'foo']
     *   );
     *
     * @param  array<string, string|bool>  $state
     */
    #[On('architect:toolbar:receive-state')]
    public function receiveState(string $toolbarKey, array $state): void
    {
        if ($toolbarKey !== $this->toolbarKey) {
            return;
        }

        $this->applyStateDict($state);
    }

    /**
     * Set the value of a toggle-style DropdownCheckbox item and dispatch the change event.
     *
     * @param  string  $compoundKey  "dropdownKey.itemKey"
     */
    public function setToggle(string $compoundKey, bool $value): void
    {
        $this->toggleValues[$compoundKey] = $value;

        [$dropdownKey, $toggleKey] = explode('.', $compoundKey, 2);

        $def = $this->definition();
        $dropdown = $this->findItem($def, $dropdownKey);

        if (! ($dropdown instanceof ToolbarDropdown)) {
            return;
        }

        $toggleItem = null;
        foreach ($dropdown->getItems() as $child) {
            if (($child instanceof DropdownCheckbox && $child->isToggle()) && $child->getKey() === $toggleKey) {
                $toggleItem = $child;
                break;
            }
        }

        if ($toggleItem === null) {
            return;
        }

        $payload = array_merge($toggleItem->getChangePayload(), [
            'toolbarKey' => $this->toolbarKey,
            'itemKey' => $toggleKey,
            'value' => $value,
        ]);

        $this->dispatch('architect:toolbar:toggle-changed', ...$payload);

        if ($toggleItem->getChangeEvent() !== null && $toggleItem->getChangeEvent() !== 'architect:toolbar:toggle-changed') {
            $this->dispatch($toggleItem->getChangeEvent(), ...$payload);
        }

        $this->dispatchBoundChanged();
    }

    /**
     * Called once by Alpine on first render after it has read localStorage.
     * Overwrites definition defaults with persisted values where present.
     *
     * @param  array<string, string|bool>  $state  e.g. ['radio.view' => 'card', 'toggle.options.archived' => true]
     */
    public function loadFromLocalStorage(array $state): void
    {
        $this->applyStateDict($state);
        $this->localStorageLoaded = true;
    }

    /**
     * Called once by Alpine after it has read URL query params on first render
     * (and again on browser popstate). URL values take priority over localStorage.
     *
     * @param  array<string, string|bool>  $state  e.g. ['radio.view' => 'card', 'search.q' => 'foo']
     */
    public function loadFromUrl(array $state): void
    {
        $this->applyStateDict($state);
        $this->urlLoaded = true;
    }

    // ── Permission helpers ────────────────────────────────────────────────────

    /**
     * Resolve whether the current user has a given permission node.
     * Returns true when no node is required (item is always visible).
     */
    public function can(?string $permissionNode): bool
    {
        if ($permissionNode === null) {
            return true;
        }

        $user = $this->resolveUser();

        if ($user === null) {
            return false;
        }

        return app(PermissionResolver::class)->can($user, $permissionNode);
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function definition(): ArchitectToolbarDefinition
    {
        if (! $this->cachedDefinition instanceof ArchitectToolbarDefinition) {
            /** @var class-string $class */
            $class = $this->definitionClass;
            $built = $class::build();

            if (! $built instanceof ArchitectToolbarDefinition) {
                throw new \LogicException("ToolbarEngine: [{$class}::build()] must return an ArchitectToolbarDefinition.");
            }

            $this->cachedDefinition = $built;
        }

        return $this->cachedDefinition;
    }

    private function seedDefaults(ArchitectToolbarDefinition $def): void
    {
        foreach ($def->getItems() as $item) {
            if ($item instanceof ToolbarRadioGroup && ! isset($this->radioValues[$item->getKey()])) {
                if ($item->getDefault() !== null) {
                    $this->radioValues[$item->getKey()] = $item->getDefault();
                }
            }

            if ($item instanceof ToolbarSearch && ! isset($this->searchValues[$item->getKey()])) {
                $this->searchValues[$item->getKey()] = '';
                $this->searchSuggestions[$item->getKey()] = [];
                $this->searchLoading[$item->getKey()] = false;
            }

            if ($item instanceof ToolbarDropdown) {
                foreach ($item->getItems() as $child) {
                    $ck = $item->getKey().'.'.$child->getKey();

                    if (($child instanceof DropdownCheckbox && $child->isToggle()) && ! isset($this->toggleValues[$ck])) {
                        $this->toggleValues[$ck] = $child->getDefault();
                    }

                    if ($child instanceof DropdownCheckbox && ! isset($this->checkboxValues[$ck])) {
                        $this->checkboxValues[$ck] = $child->getDefault();
                    }

                    if ($child instanceof DropdownRadioGroupItem && ! isset($this->dropdownRadioValues[$ck])) {
                        if ($child->getDefault() !== null) {
                            $this->dropdownRadioValues[$ck] = $child->getDefault();
                        }
                    }

                    if ($child instanceof DropdownTextInput && ! isset($this->textValues[$ck])) {
                        $this->textValues[$ck] = $child->getDefault();
                    }
                }
            }
        }
    }

    private function findItem(ArchitectToolbarDefinition $def, string $key): ?ToolbarItem
    {
        foreach ($def->getItems() as $item) {
            if ($item->getKey() === $key) {
                return $item;
            }
        }

        return null;
    }

    private function resolveUser(): ?Authenticatable
    {
        if (! $this->cachedUserResolved) {
            $this->cachedUser = auth()->user();
            $this->cachedUserResolved = true;
        }

        return $this->cachedUser;
    }

    /**
     * Build the map of localStorage persistence keys that Alpine needs on init.
     * Returns: { 'radio.itemKey' => 'ls_key', 'toggle.dropdownKey.toggleKey' => 'ls_key' }
     *
     * @return array<string, string>
     */
    public function buildPersistKeys(ArchitectToolbarDefinition $def): array
    {
        $keys = [];

        foreach ($def->getItems() as $item) {
            if ($item instanceof ToolbarRadioGroup && $item->getPersist() === 'local') {
                $lsKey = "architectToolbar_{$this->toolbarKey}_radio_{$item->getKey()}";
                $keys['radio.'.$item->getKey()] = $lsKey;
            }

            if ($item instanceof ToolbarSearch && $item->getPersist() === 'local') {
                $keys['search.'.$item->getKey()] = "architectToolbar_{$this->toolbarKey}_search_{$item->getKey()}";
            }

            if ($item instanceof ToolbarDropdown) {
                foreach ($item->getItems() as $child) {
                    $ck = $item->getKey().'.'.$child->getKey();

                    if (($child instanceof DropdownCheckbox && $child->isToggle()) && $child->getPersist() === 'local') {
                        $keys['toggle.'.$ck] = "architectToolbar_{$this->toolbarKey}_toggle_{$ck}";
                    }

                    if ($child instanceof DropdownCheckbox && $child->getPersist() === 'local') {
                        $keys['checkbox.'.$ck] = "architectToolbar_{$this->toolbarKey}_checkbox_{$ck}";
                    }

                    if ($child instanceof DropdownRadioGroupItem && $child->getPersist() === 'local') {
                        $keys['dropdown-radio.'.$ck] = "architectToolbar_{$this->toolbarKey}_dropdown-radio_{$ck}";
                    }

                    if ($child instanceof DropdownTextInput && $child->getPersist() === 'local') {
                        $keys['text.'.$ck] = "architectToolbar_{$this->toolbarKey}_text_{$ck}";
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Build the map of URL query-param persistence keys for Alpine on init.
     * Returns: { 'radio.view' => 'view', 'search.q' => 'q', 'toggle.options.archived' => 'options_archived' }
     *
     * Top-level item keys are used as URL param names as-is (e.g. 'view', 'q').
     * Compound dropdown.child keys have dots replaced with underscores
     * (e.g. 'options.archived' → 'options_archived').
     *
     * @return array<string, string>
     */
    public function buildUrlPersistKeys(ArchitectToolbarDefinition $def): array
    {
        $keys = [];

        foreach ($def->getItems() as $item) {
            if ($item instanceof ToolbarRadioGroup && $item->getPersist() === 'url') {
                $keys['radio.'.$item->getKey()] = $item->getKey();
            }

            if ($item instanceof ToolbarSearch && $item->getPersist() === 'url') {
                $keys['search.'.$item->getKey()] = $item->getKey();
            }

            if ($item instanceof ToolbarDropdown) {
                foreach ($item->getItems() as $child) {
                    $ck = $item->getKey().'.'.$child->getKey();
                    $urlParam = str_replace('.', '_', $ck);

                    if (($child instanceof DropdownCheckbox && $child->isToggle()) && $child->getPersist() === 'url') {
                        $keys['toggle.'.$ck] = $urlParam;
                    }

                    if ($child instanceof DropdownCheckbox && $child->getPersist() === 'url') {
                        $keys['checkbox.'.$ck] = $urlParam;
                    }

                    if ($child instanceof DropdownRadioGroupItem && $child->getPersist() === 'url') {
                        $keys['dropdown-radio.'.$ck] = $urlParam;
                    }

                    if ($child instanceof DropdownTextInput && $child->getPersist() === 'url') {
                        $keys['text.'.$ck] = $urlParam;
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Apply a state dictionary (keyed by prefixed compound keys) to the
     * component's public state arrays. Used by loadFromLocalStorage(),
     * loadFromUrl(), and receiveState().
     *
     * @param  array<string, string|bool>  $state
     */
    private function applyStateDict(array $state): void
    {
        foreach ($state as $compoundKey => $value) {
            if (str_starts_with($compoundKey, 'radio.')) {
                $itemKey = substr($compoundKey, strlen('radio.'));
                if (is_string($value)) {
                    $this->radioValues[$itemKey] = $value;
                }
            } elseif (str_starts_with($compoundKey, 'toggle.')) {
                $rest = substr($compoundKey, strlen('toggle.'));
                $this->toggleValues[$rest] = (bool) $value;
            } elseif (str_starts_with($compoundKey, 'checkbox.')) {
                $rest = substr($compoundKey, strlen('checkbox.'));
                $this->checkboxValues[$rest] = (bool) $value;
            } elseif (str_starts_with($compoundKey, 'dropdown-radio.')) {
                $rest = substr($compoundKey, strlen('dropdown-radio.'));
                if (is_string($value)) {
                    $this->dropdownRadioValues[$rest] = $value;
                }
            } elseif (str_starts_with($compoundKey, 'text.')) {
                $rest = substr($compoundKey, strlen('text.'));
                if (is_string($value)) {
                    $this->textValues[$rest] = $value;
                }
            } elseif (str_starts_with($compoundKey, 'search.')) {
                $rest = substr($compoundKey, strlen('search.'));
                if (is_string($value)) {
                    $this->searchValues[$rest] = $value;
                }
            }
        }
    }

    /**
     * Snapshot of all current toolbar state values, used in bound-changed events.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildStateSnapshot(): array
    {
        return [
            'radioValues' => $this->radioValues,
            'toggleValues' => $this->toggleValues,
            'checkboxValues' => $this->checkboxValues,
            'dropdownRadioValues' => $this->dropdownRadioValues,
            'textValues' => $this->textValues,
            'searchValues' => $this->searchValues,
        ];
    }

    /**
     * Dispatch architect:toolbar:bound-changed to the bound-target component.
     * Called at the end of every user-initiated mutation. Skipped when there is
     * no bound target, keeping the common case free of extra dispatches.
     */
    private function dispatchBoundChanged(): void
    {
        $boundTarget = $this->definition()->getBoundTarget();

        if ($boundTarget === null) {
            return;
        }

        $this->dispatch('architect:toolbar:bound-changed', [
            'toolbarKey' => $this->toolbarKey,
            'boundTarget' => $boundTarget,
            'state' => $this->buildStateSnapshot(),
        ]);
    }
}
