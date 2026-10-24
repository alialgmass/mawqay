<?php

namespace Modules\Core\Tests\Unit;

use InvalidArgumentException;
use Modules\Core\Support\ColorContrast;
use PHPUnit\Framework\TestCase;

class ColorContrastTest extends TestCase
{
    public function test_black_on_white_is_the_maximum_ratio(): void
    {
        $this->assertEqualsWithDelta(21.0, ColorContrast::ratio('#000000', '#FFFFFF'), 0.01);
    }

    public function test_a_colour_against_itself_is_the_minimum_ratio(): void
    {
        $this->assertEqualsWithDelta(1.0, ColorContrast::ratio('#7A3E65', '#7A3E65'), 0.01);
    }

    public function test_the_ratio_is_the_same_in_either_order(): void
    {
        $this->assertEqualsWithDelta(
            ColorContrast::ratio('#7A3E65', '#FFFFFF'),
            ColorContrast::ratio('#FFFFFF', '#7A3E65'),
            0.0001,
        );
    }

    public function test_a_known_pair_matches_the_wcag_figure(): void
    {
        // #777777 on white is the classic 4.48:1 example.
        $this->assertEqualsWithDelta(4.48, ColorContrast::ratio('#777777', '#FFFFFF'), 0.01);
    }

    public function test_white_text_is_chosen_on_dark_backgrounds_and_black_on_light(): void
    {
        $this->assertSame('#FFFFFF', ColorContrast::readableTextOn('#1B2A49'));
        $this->assertSame('#FFFFFF', ColorContrast::readableTextOn('#7A3E65'));
        $this->assertSame('#000000', ColorContrast::readableTextOn('#F3E8EE'));
        $this->assertSame('#000000', ColorContrast::readableTextOn('#FFD700'));
    }

    public function test_a_colour_that_fades_into_a_white_page_is_flagged(): void
    {
        $this->assertTrue(ColorContrast::isHardToSeeOnPage('#FFD700'));
        $this->assertTrue(ColorContrast::isHardToSeeOnPage('#F3E8EE'));
        $this->assertFalse(ColorContrast::isHardToSeeOnPage('#808080'));
        $this->assertFalse(ColorContrast::isHardToSeeOnPage('#1B2A49'));
    }

    public function test_only_six_digit_hex_colours_are_accepted(): void
    {
        $this->assertTrue(ColorContrast::isHex('#a1B2c3'));
        $this->assertFalse(ColorContrast::isHex('a1b2c3'));
        $this->assertFalse(ColorContrast::isHex('#abc'));
        $this->assertFalse(ColorContrast::isHex('red'));
    }

    public function test_a_malformed_colour_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ColorContrast::ratio('red', '#FFFFFF');
    }
}
