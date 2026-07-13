@php /** @var \Entelechy\Architect\Forms\Fields\CronScheduleBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-cron-schedule-builder"
         wire:ignore
         x-data="architectCronScheduleBuilder({ wireField: 'formData.{{ $field->getName() }}' })">
        <div x-ref="builder"></div>
        <p class="arch-field__preview" x-ref="preview"></p>
    </div>
</x-architect::field-wrapper>
