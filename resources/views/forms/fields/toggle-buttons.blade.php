@php
/**
 * @var \Entelechy\Architect\Forms\Fields\ToggleButtons $field
 * @var \Closure(string): mixed $get
 */
@endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-toggle-buttons" x-data="{
            multi: {{ $field->isMultiple() ? 'true' : 'false' }},
            value: $wire.entangle('formData.{{ $field->getName() }}'),
            isActive(v) { return this.multi ? (this.value ?? []).includes(v) : this.value === v; },
            toggle(v) {
                if (this.multi) {
                    const arr = this.value ?? [];
                    this.value = arr.includes(v) ? arr.filter(x => x !== v) : [...arr, v];
                } else {
                    this.value = v;
                }
            },
        }">
        @foreach ($field->getOptions($get) as $value => $optionLabel)
            <button type="button"
                    class="arch-toggle-buttons__item"
                    :data-active="isActive('{{ $value }}')"
                    @click="toggle('{{ $value }}')">
                {{ $optionLabel }}
            </button>
        @endforeach
    </div>
</x-architect::field-wrapper>
