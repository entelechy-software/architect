<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Uses the camera to detect document edges, correct perspective, and
 * produce a cleaned-up image or PDF — Wave C (FORMS_FEATURE_PLAN.md
 * Phase 3). Extends FileUpload for accept/maxSize/disk handling; adds
 * only the desired output format.
 */
class DocumentScannerField extends FileUpload
{
    /** @var 'image'|'pdf' */
    private string $outputFormat = 'pdf';

    /** @param  'image'|'pdf'  $format */
    public function outputFormat(string $format): static
    {
        $clone = clone $this;
        $clone->outputFormat = $format;

        return $clone;
    }

    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.document-scanner';
    }
}
