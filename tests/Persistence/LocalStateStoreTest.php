<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Persistence;

use Entelechy\Architect\Persistence\LocalStateStore;
use PHPUnit\Framework\TestCase;

class LocalStateStoreTest extends TestCase
{
    public function test_get_always_returns_null(): void
    {
        $this->assertNull((new LocalStateStore)->get(1, '', 'table', 'k'));
    }

    public function test_put_and_forget_are_inert_and_do_not_throw(): void
    {
        $store = new LocalStateStore;

        $store->put(1, '', 'table', 'k', ['value' => 'x']);
        $store->forget(1, '', 'table', 'k');

        $this->assertNull($store->get(1, '', 'table', 'k'));
    }
}
