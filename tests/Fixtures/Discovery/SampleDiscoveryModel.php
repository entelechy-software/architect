<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures\Discovery;

use Entelechy\Architect\Concerns\HasStorageContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SampleDiscoveryModel extends Model
{
    use HasStorageContract;
    use SoftDeletes;

    protected $table = 'sample_discovery_models';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::storageContract('finance');
    }
}
