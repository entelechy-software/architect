@php /** @var \Entelechy\Architect\Forms\Fields\DateTimeField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div x-data="{
            init() {
                flatpickr(this.$refs.dateTimeInput, {
                    enableTime: true,
                    dateFormat: 'd/m/Y H:i',
                    time_24hr: true,
                    allowInput: true,
                    onChange: (selectedDates, dateStr) => $wire.set('formData.{{ $field->getName() }}', dateStr),
                });
            },
        }">
        <input x-ref="dateTimeInput"
               type="text"
               id="field-{{ $field->getName() }}"
               class="arch-input"
               autocomplete="off"
               placeholder="{{ $field->getPlaceholder() ?? 'dd/mm/yyyy hh:mm' }}"
               value="{{ $field->getDefault() }}">
    </div>
</x-architect::field-wrapper>
