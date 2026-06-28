@php /** @var \Entelechy\Architect\Forms\Grid $grid */ @endphp
<div class="arch-grid" data-cols="{{ $grid->getCols() }}" data-gap="{{ $grid->getGap() }}">
    @foreach ($grid->getStructure() as $child)
        @include('architect::forms.partials.structure-item', ['item' => $child, 'get' => $get])
    @endforeach
</div>
