@php /** @var \Entelechy\Architect\Forms\Fields\RankingField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <ol class="arch-ranking"
        wire:ignore
        x-data="architectRanking({ wireField: 'formData.{{ $field->getName() }}', options: @js($field->getOptions($get)) })"
        x-ref="list">
    </ol>
</x-architect::field-wrapper>
