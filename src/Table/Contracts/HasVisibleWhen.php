<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

/**
 * Implemented by anything that can declare conditional form-field visibility
 * (Column, ArchitectField). VisibleWhenAlpineCompiler accepts any
 * implementer and produces an Alpine `x-show` expression referencing other
 * form fields by their editKey.
 */
interface HasVisibleWhen
{
    /**
     * @return list<array{field: string, op: string, value: mixed}>
     */
    public function getVisibleWhen(): array;
}
