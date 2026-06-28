{{-- Tabler-styled select for a SelectFilter. --}}
<select
    class="arch-select arch-select-sm"
    x-effect="$el.value = ($wire.filters['{{ $filter->name() }}'] ?? '')"
    @change="$wire.call('setFilter', '{{ $filter->name() }}', $event.target.value)"
    aria-label="{{ $filter->getLabel() }}"
>
    <option value="">All</option>
    @foreach ($filter->getOptions() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
