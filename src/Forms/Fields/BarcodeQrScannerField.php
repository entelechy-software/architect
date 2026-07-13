<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Uses a camera or hardware scanner to enter a barcode/QR-code value —
 * Wave C (FORMS_FEATURE_PLAN.md Phase 3). Unlike the file-capture fields
 * in this wave, the value is the decoded text/data itself, not an
 * uploaded file.
 */
class BarcodeQrScannerField extends Field
{
    /** @var array<int, string> */
    private array $formats = ['qr_code', 'ean_13', 'code_128'];

    /** @param  array<int, string>  $formats */
    public function formats(array $formats): static
    {
        $clone = clone $this;
        $clone->formats = $formats;

        return $clone;
    }

    /** @return array<int, string> */
    public function getFormats(): array
    {
        return $this->formats;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.barcode-qr-scanner';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
