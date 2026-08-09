@php /** @var \Entelechy\Architect\Forms\Fields\RoleBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-role-builder">
        <select class="arch-select" multiple wire:model="formData.{{ $field->getName() }}.permissions">
            @foreach ($field->getAvailablePermissions() as $permission)
                <option value="{{ $permission }}">{{ $permission }}</option>
            @endforeach
        </select>
        <select class="arch-select" wire:model="formData.{{ $field->getName() }}.inherits_from">
            <option value="">{{ __('None') }}</option>
            @foreach ($field->getAvailableRolesToInheritFrom() as $role)
                <option value="{{ $role }}">{{ $role }}</option>
            @endforeach
        </select>
        <input type="text" class="arch-input" placeholder="{{ __('Scope (optional)') }}" wire:model="formData.{{ $field->getName() }}.scope">
        <div x-data="architectTagsInput({ wireField: 'formData.{{ $field->getName() }}.exceptions' })">
            <div class="arch-tags-input__tags">
                <template x-for="(tag, index) in tags" :key="index">
                    <span class="arch-badge" data-color="primary" data-variant="soft">
                        <span x-text="tag"></span>
                        <button type="button" @click="removeTag(index)">&times;</button>
                    </span>
                </template>
            </div>
            <input type="text"
                   class="arch-input"
                   x-model="query"
                   @keydown.enter.prevent="addFromQuery()"
                   placeholder="{{ __('Add an exception…') }}">
        </div>
    </div>
</x-architect::field-wrapper>
