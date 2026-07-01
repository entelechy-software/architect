{{-- Date range filter — two native date inputs that update together via Alpine. --}}
{{-- Value shape: ['from' => 'Y-m-d', 'to' => 'Y-m-d'] or null --}}
<div
    x-data="{
        update() {
            $wire.call('setFilter', '{{ $filter->name() }}', {
                from: this.$refs.from.value,
                to: this.$refs.to.value
            });
        }
    }"
    class="flex gap-2"
>
    <input
        type="date"
        x-ref="from"
        class="arch-input arch-input-sm"
        x-effect="$el.value = ($wire.filters['{{ $filter->name() }}']?.from ?? '')"
        @change="update()"
        placeholder="From"
        aria-label="{{ $filter->getLabel() }} from"
    >
    <input
        type="date"
        x-ref="to"
        class="arch-input arch-input-sm"
        x-effect="$el.value = ($wire.filters['{{ $filter->name() }}']?.to ?? '')"
        @change="update()"
        placeholder="To"
        aria-label="{{ $filter->getLabel() }} to"
    >
</div>
