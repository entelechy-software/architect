<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Support\Redaction;

use Entelechy\Architect\Support\Redaction\RedactionStrategy;
use Entelechy\Architect\Tests\TestCase;

class RedactionStrategyTest extends TestCase
{
    public function test_full_strategy_masks_entire_value(): void
    {
        $this->assertSame('••••••••', RedactionStrategy::full()->apply('123-45-6789'));
    }

    public function test_full_strategy_does_not_leak_true_length(): void
    {
        $short = RedactionStrategy::full()->apply('ab');
        $long = RedactionStrategy::full()->apply(str_repeat('a', 100));

        $this->assertSame($short, $long);
    }

    public function test_full_strategy_respects_custom_mask_and_length(): void
    {
        $this->assertSame('****', RedactionStrategy::full(mask: '*', length: 4)->apply('anything'));
    }

    public function test_partial_strategy_keeps_trailing_characters_by_default(): void
    {
        $this->assertSame('••••6789', RedactionStrategy::partial()->apply('4111111111116789'));
    }

    public function test_partial_strategy_keeps_leading_characters_when_side_is_start(): void
    {
        $this->assertSame('4111••••', RedactionStrategy::partial(show: 4, side: 'start')->apply('4111111111116789'));
    }

    public function test_partial_strategy_never_shows_more_than_the_value_contains(): void
    {
        $this->assertSame('••••ab', RedactionStrategy::partial(show: 4)->apply('ab'));
    }

    public function test_partial_strategy_respects_custom_mask_character(): void
    {
        $this->assertSame('####6789', RedactionStrategy::partial(mask: '#')->apply('123456789012 6789'));
    }

    public function test_custom_strategy_delegates_to_closure(): void
    {
        $strategy = RedactionStrategy::custom(fn (mixed $value): string => 'REDACTED('.strlen((string) $value).')');

        $this->assertSame('REDACTED(5)', $strategy->apply('hello'));
    }

    public function test_preset_full_matches_full_factory(): void
    {
        $this->assertSame(RedactionStrategy::full()->apply('secret'), RedactionStrategy::preset('full')->apply('secret'));
    }

    public function test_preset_partial_matches_partial_factory(): void
    {
        $this->assertSame(RedactionStrategy::partial()->apply('secret-value'), RedactionStrategy::preset('partial')->apply('secret-value'));
    }

    public function test_unknown_preset_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RedactionStrategy::preset('nonsense');
    }
}
