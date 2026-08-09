<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures\Doctor;

use Illuminate\Database\Eloquent\Builder;

/**
 * Non-compliant baseQuery() override for TenantScopeAuditor's test suite —
 * builds the query from scratch, never calling parent::baseQuery(), so
 * tenant scoping and connection switching are silently bypassed.
 */
class NonCompliantDataModel extends CompliantDataModel
{
    protected function baseQuery(): Builder
    {
        return TenantScopeFixtureModel::query()->orderBy('id');
    }
}
