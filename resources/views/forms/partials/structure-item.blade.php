{{--
    Recursive dispatcher for anything implementing StructureItem:
    Section, Grid, Fieldset, or a concrete Forms\Fields\* field.

    Variables:
        $item  Entelechy\Architect\Forms\Contracts\StructureItem
        $get   Closure(string): mixed — resolves the current value of any
               field by name, used to evaluate hidden()/disabled() conditions.
--}}
@if ($item instanceof \Entelechy\Architect\Forms\Section)
    @include('architect::forms.section', ['section' => $item, 'get' => $get])
@elseif ($item instanceof \Entelechy\Architect\Forms\Grid)
    @include('architect::forms.grid', ['grid' => $item, 'get' => $get])
@elseif ($item instanceof \Entelechy\Architect\Forms\Fields\Fieldset)
    @include('architect::forms.fields.fieldset', ['field' => $item, 'get' => $get])
@elseif (! ($item instanceof \Entelechy\Architect\Forms\Contracts\ArchitectField) || ! $item->isHidden($get))
    @php
        $__viewData = method_exists($item, 'getViewData') ? $item->getViewData() : [];
        $__disabled = $item instanceof \Entelechy\Architect\Forms\Contracts\ArchitectField && $item->isDisabled($get);
    @endphp
    <div @if ($__disabled) data-disabled="true" @endif>
        @include($item->getViewName(), array_merge(['field' => $item, 'get' => $get], $__viewData))
    </div>
@endif
