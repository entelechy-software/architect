@php /** @var \Entelechy\Architect\Content\ArchitectContentDefinition $definition */ @endphp
<div class="arch-content">
    <div class="arch-grid" data-cols="{{ $definition->columns }}">
        @foreach ($definition->structure as $item)
            @include('architect::content.partials.structure-item', ['item' => $item, 'record' => $definition->record])
        @endforeach
    </div>
</div>
