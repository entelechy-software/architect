{{--
    Toolbar partial: ToolbarSpacer.
    'push' mode consumes remaining space; 'fixed' mode renders a constant-width gap.
--}}
@php /** @var \Entelechy\Architect\Toolbar\Items\ToolbarSpacer $item */ @endphp
@if ($item->getMode() === 'fixed')
    <div style="width: {{ $item->getWidth() ?? '1rem' }}; flex-shrink: 0;" aria-hidden="true"></div>
@else
    <div class="flex-1" aria-hidden="true"></div>
@endif
