{{--
    View-record partial — renders a list<{label, value}> from Model::viewAll()
    as a readable definition list inside the ModuleTable panel.

    Variables:
      $viewRecord  list<array{label: string, value: mixed}>
--}}
<dl class="row mb-0">
    @foreach ($viewRecord as $item)
        <dt class="col-span-5 text-gray-500 dark:text-gray-400 font-normal text-sm uppercase">{{ $item['label'] }}</dt>
        <dd class="col-span-7 mb-3">
            @if ($item['value'] === null || $item['value'] === '')
                <span class="text-gray-500 dark:text-gray-400">—</span>
            @elseif (is_bool($item['value']))
                {{ $item['value'] ? 'Yes' : 'No' }}
            @else
                {{ $item['value'] }}
            @endif
        </dd>
    @endforeach
</dl>
