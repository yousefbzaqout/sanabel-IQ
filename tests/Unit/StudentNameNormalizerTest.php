<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\StudentNameNormalizer;
use PHPUnit\Framework\TestCase;

class StudentNameNormalizerTest extends TestCase
{
    public function test_it_strips_bidirectional_control_characters(): void
    {
        $this->assertSame('ليان', StudentNameNormalizer::normalize(" \xE2\x80\x8Fليان  "));
    }

    public function test_it_removes_latin_characters_stuck_to_arabic_names(): void
    {
        $this->assertSame('سارة Audit', StudentNameNormalizer::normalize('سارةSara Audit'));
    }
}
