<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures;

use Entelechy\Architect\Concerns\HasStorageContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fixture model with an explicit contract assignment, for
 * HasStorageContractTest.
 */
class StorageContractTestModel extends Model
{
    use HasStorageContract;
    use SoftDeletes;

    protected $table = 'storage_contract_test_models';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::storageContract('finance');
    }
}
