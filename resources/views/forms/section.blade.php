@php /** @var \Entelechy\Architect\Forms\Section $section */ @endphp
<div class="arch-form__section"
     @if ($section->isCollapsible())
         x-data="{ collapsed: {{ $section->isCollapsed() ? 'true' : 'false' }} }"
     @endif>
    @if ($section->getTitle() !== '')
        <div class="arch-form__section-header"
             @if ($section->isCollapsible()) @click="collapsed = !collapsed" style="cursor: pointer" @endif>
            <h3 class="arch-form__section-title">{{ $section->getTitle() }}</h3>
            @if ($section->getDescription() !== null)
                <p class="arch-form__section-description">{{ $section->getDescription() }}</p>
            @endif
        </div>
    @endif

    <div class="arch-form__section-body" @if ($section->isCollapsible()) x-show="!collapsed" @endif>
        @foreach ($section->getStructure() as $child)
            @include('architect::forms.partials.structure-item', ['item' => $child, 'get' => $get])
        @endforeach
    </div>
</div>
