<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures\Discovery;

use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Table\Contracts\ProvidesTableDefinition;

class SampleDiscoveryTableDefinition implements ProvidesTableDefinition
{
    public static function definition(): ArchitectTableDefinition
    {
        return TableBuilder::make()
            ->title('Sample Discovery Records')
            ->model(SampleDiscoveryDataModel::class)
            ->permissions(read: 'sample.read', create: 'sample.create', modify: 'sample.modify', remove: 'sample.remove')
            ->column(Column::make('avatar_path')->type('upload'))
            ->column(Column::make('attachment_path')->type('upload'))
            ->build();
    }
}
