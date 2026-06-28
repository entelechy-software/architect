{{-- Multi-select filter — native multiple select, Alpine batches the change into a single call. --}}
<div
    x-data="{
        update(el) {
            const selected = Array.from(el.options)
                .filter(o => o.selected)
                .map(o => o.value);
            $wire.call('setFilter', '{{ $filter->name() }}', selected);
        }
    }"
    x-effect="
        const active = ($wire.filters['{{ $filter->name() }}'] ?? []).map(String);
        if ($refs.select) {
            Array.from($refs.select.options).forEach(o => { o.selected = active.includes(o.value); });
        }
    "
>
    <select
        x-ref="select"
        class="arch-select arch-select-sm"
        multiple
        @change="update($el)"
        aria-label="{{ $filter->getLabel() }}"
    >
        @foreach ($filter->getOptions() as $optValue => $label)
            <option value="{{ $optValue }}">{{ $label }}</option>
        @endforeach
    </select>
</div>
