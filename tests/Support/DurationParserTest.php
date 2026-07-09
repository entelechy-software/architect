<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Support;

use Carbon\CarbonImmutable;
use Entelechy\Architect\Support\DurationParser;
use Entelechy\Architect\Tests\TestCase;
use InvalidArgumentException;

class DurationParserTest extends TestCase
{
    public function test_null_duration_returns_null(): void
    {
        $this->assertNull(DurationParser::cutoff(null));
    }

    public function test_empty_string_duration_returns_null(): void
    {
        $this->assertNull(DurationParser::cutoff('  '));
    }

    public function test_years_duration_computes_expected_cutoff(): void
    {
        $now = CarbonImmutable::create(2026, 7, 8, 12, 0, 0);

        $cutoff = DurationParser::cutoff('2 years', $now);

        $this->assertNotNull($cutoff);
        $this->assertTrue($cutoff->equalTo(CarbonImmutable::create(2024, 7, 8, 12, 0, 0)));
    }

    public function test_days_duration_computes_expected_cutoff(): void
    {
        $now = CarbonImmutable::create(2026, 7, 8, 12, 0, 0);

        $cutoff = DurationParser::cutoff('90 days', $now);

        $this->assertNotNull($cutoff);
        $this->assertTrue($cutoff->equalTo($now->subDays(90)));
    }

    public function test_months_duration_computes_expected_cutoff(): void
    {
        $now = CarbonImmutable::create(2026, 7, 8, 12, 0, 0);

        $cutoff = DurationParser::cutoff('6 months', $now);

        $this->assertNotNull($cutoff);
        $this->assertTrue($cutoff->equalTo($now->subMonths(6)));
    }

    public function test_invalid_duration_string_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DurationParser::cutoff('not a duration');
    }
}
