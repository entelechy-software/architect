<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Date (optionally + time) field with a calendar popover, driven by
 * Flatpickr via Alpine (see resources/views/forms/fields/datetime-picker.blade.php).
 */
class DateTimePicker extends Field
{
    private bool $withTime = false;

    private ?string $minDate = null;

    private ?string $maxDate = null;

    private string $format = 'd/m/Y';

    public function withTime(bool $withTime = true): static
    {
        $clone = clone $this;
        $clone->withTime = $withTime;
        $clone->format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';

        return $clone;
    }

    public function minDate(string $date): static
    {
        $clone = clone $this;
        $clone->minDate = $date;

        return $clone;
    }

    public function maxDate(string $date): static
    {
        $clone = clone $this;
        $clone->maxDate = $date;

        return $clone;
    }

    public function format(string $format): static
    {
        $clone = clone $this;
        $clone->format = $format;

        return $clone;
    }

    public function isWithTime(): bool
    {
        return $this->withTime;
    }

    public function getMinDate(): ?string
    {
        return $this->minDate;
    }

    public function getMaxDate(): ?string
    {
        return $this->maxDate;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.datetime-picker';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = "date_format:{$this->format}";

        return $rules;
    }
}
