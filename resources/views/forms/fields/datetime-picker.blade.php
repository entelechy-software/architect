@php /** @var \Entelechy\Architect\Forms\Fields\DateTimePicker $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div x-data="{
            init() {
                flatpickr(this.$refs.input, {
                    enableTime: {{ $field->isWithTime() ? 'true' : 'false' }},
                    dateFormat: '{{ $field->getFormat() }}',
                    time_24hr: true,
                    allowInput: true,
                    @if ($field->getMinDate()) minDate: '{{ $field->getMinDate() }}', @endif
                    @if ($field->getMaxDate()) maxDate: '{{ $field->getMaxDate() }}', @endif
                    onChange: (selectedDates, dateStr) => $wire.set('formData.{{ $field->getName() }}', dateStr),
                });
            },
        }">
        <input x-ref="input"
               type="text"
               id="field-{{ $field->getName() }}"
               class="arch-input"
               autocomplete="off"
               placeholder="{{ $field->getPlaceholder() ?? $field->getFormat() }}"
               value="{{ $field->getDefault() }}">
    </div>
</x-architect::field-wrapper>
