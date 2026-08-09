<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures\Doctor;

use Illuminate\Database\Eloquent\Model;

class TenantScopeFixtureModel extends Model
{
    protected $table = 'tenant_scope_fixture_widgets';

    protected $guarded = [];
}
