{{-- Button-check radio group filter. --}}
{{-- Options (including any clear/"All" option with value '') are owned by the definition. --}}
@php $groupName = 'mt-radio-' . $filter->name(); @endphp
<div
    class="flex gap-0.5 w-100"
    role="group"
    aria-label="{{ $filter->getLabel() }}"
>
    @foreach ($filter->getOptions() as $value => $label)
        @php $inputId = $groupName . '-' . $loop->index; @endphp
        <input
            type="radio"
            class="btn-check"
            name="{{ $groupName }}"
            id="{{ $inputId }}"
            autocomplete="off"
            x-effect="$el.checked = (String($wire.filters[{{ json_encode($filter->name()) }}] ?? '') === {{ json_encode((string) $value) }})"
            @change="$wire.call('setFilter', {{ json_encode($filter->name()) }}, {{ json_encode((string) $value) }})"
        >
        <label class="arch-btn arch-btn-outline-secondary" for="{{ $inputId }}">{{ $label }}</label>
    @endforeach
</div>
