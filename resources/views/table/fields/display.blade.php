<div class="flex flex-col gap-2">
    <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
        {{ $field->getLabel() }}
    </div>
    <div class=" text-sm text-gray-900 dark:text-white">
        @php $value = $form[$field->name()] ?? null; @endphp
        @if ($value === null || $value === '')
            <span class="text-gray-500 dark:text-gray-400">—</span>
        @else
            {{ is_scalar($value) ? $value : json_encode($value) }}
        @endif
    </div>
</div>
