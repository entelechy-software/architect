{{--
    Toolbar partial: ToolbarButtonGroup
    Renders a visually connected strip of buttons as a Bootstrap btn-group.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\ToolbarButtonGroup $item */
@endphp

<div class="btn-group" role="group" aria-label="{{ $item->key() }}">
    @foreach ($item->getItems() as $groupItem)
        @include('architect::toolbar.partials.' . $groupItem->getItemType(), [
            'item' => $groupItem,
            'definition' => $definition,
        ])
    @endforeach
</div>
