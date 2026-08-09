<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures\Doctor;

use Entelechy\Architect\Table\AbstractEloquentDataModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Compliant baseQuery() override for TenantScopeAuditor's test suite —
 * calls parent::baseQuery() first, so tenant scoping still applies.
 */
class CompliantDataModel extends AbstractEloquentDataModel
{
    public function modelClass(): string
    {
        return TenantScopeFixtureModel::class;
    }

    protected function baseQuery(): Builder
    {
        return parent::baseQuery()->orderBy('id');
    }
}
