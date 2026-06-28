@php /** @var \Entelechy\Architect\Forms\Fields\DateField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div x-data="{
            init() {
                flatpickr(this.$refs.dateInput, {
                    dateFormat: 'd/m/Y',
                    allowInput: true,
                    onChange: (selectedDates, dateStr) => $wire.set('formData.{{ $field->getName() }}', dateStr),
                });
            },
        }">
        <input x-ref="dateInput"
               type="text"
               id="field-{{ $field->getName() }}"
               class="arch-input"
               autocomplete="off"
               placeholder="{{ $field->getPlaceholder() ?? 'dd/mm/yyyy' }}"
               value="{{ $field->getDefault() }}">
    </div>
</x-architect::field-wrapper>
