<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures;

use Entelechy\Architect\Concerns\HasStorageContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fixture model that never calls storageContract(), for asserting fallback
 * to config('architect.storage_contracts.default_contract') in
 * HasStorageContractTest.
 */
class DefaultContractTestModel extends Model
{
    use HasStorageContract;
    use SoftDeletes;

    protected $table = 'default_contract_test_models';

    protected $guarded = [];
}
