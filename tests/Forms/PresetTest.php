<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Validation\Preset;
use Entelechy\Architect\Tests\TestCase;

class PresetTest extends TestCase
{
    public function test_work_email_excludes_common_free_domains(): void
    {
        $rules = Preset::workEmail()->compile();

        $this->assertContains('email', $rules);
        $this->assertContains('not_regex:/@(gmail|yahoo|hotmail|outlook|icloud|aol)\.com$/i', $rules);
    }

    public function test_currency_preset(): void
    {
        $rules = Preset::currency()->compile();

        $this->assertContains('numeric', $rules);
        $this->assertContains('min:0', $rules);
    }

    public function test_percentage_preset(): void
    {
        $rules = Preset::percentage()->compile();

        $this->assertContains('numeric', $rules);
        $this->assertContains('min:0', $rules);
        $this->assertContains('max:100', $rules);
    }

    public function test_image_preset(): void
    {
        $rules = Preset::image()->compile();

        $this->assertContains('mimes:jpg,jpeg,png,gif,webp', $rules);
    }

    public function test_custom_make_preset(): void
    {
        $rules = Preset::make(['custom_rule:1'])->compile();

        $this->assertSame(['custom_rule:1'], $rules);
    }
}
