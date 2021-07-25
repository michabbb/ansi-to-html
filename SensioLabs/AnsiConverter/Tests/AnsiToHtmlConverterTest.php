<?php

/*
 * This file is part of ansi-to-html.
 *
 * (c) 2013 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\AnsiConverter\Tests;

use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use PHPUnit\Framework\TestCase;

class AnsiToHtmlConverterTest extends TestCase
{
    /**
     * @dataProvider getConvertData
     */
    public function testConvert($expected, $input): void
    {
        $converter = new AnsiToHtmlConverter();
        $this->assertEquals($expected, $converter->convert($input));
    }

    public function getConvertData(): array
    {
        return [
            // text is escaped
            [
                '<span style="background-color: black; color: white">foo &lt;br /&gt;</span>',
                'foo <br />'
            ],

            // newlines are preserved
            [
                "<span style=\"background-color: black; color: white\">foo\nbar</span>",
                "foo\nbar"
            ],

            // backspaces
            [
                '<span style="background-color: black; color: white">foo   </span>',
                "foobar\x08\x08\x08   "
            ],
            [
                '<span style="background-color: black; color: white">foo</span><span style="background-color: black; color: white">   </span>',
                "foob\e[31;41ma\e[0mr\x08\x08\x08   "
            ],

            // color
            [
                '<span style="background-color: darkred; color: darkred">foo</span>',
                "\e[31;41mfoo\e[0m"
            ],

            // color with [m as a termination (equivalent to [0m])
            [
                '<span style="background-color: darkred; color: darkred">foo</span>',
                "\e[31;41mfoo\e[m"
            ],

            // bright color
            [
                '<span style="background-color: red; color: red">foo</span>',
                "\e[31;41;1mfoo\e[0m"
            ],

            // carriage returns
            [
                '<span style="background-color: black; color: white">foobar</span>',
                "foo\rbar\rfoobar"
            ],

            // underline
            [
                '<span style="background-color: black; color: white; text-decoration: underline">foo</span>',
                "\e[4mfoo\e[0m"
            ],

            // non valid unicode codepoints substitution (only available with PHP >= 5.4)
            PHP_VERSION_ID < 50400 ?: [
                '<span style="background-color: black; color: white">foo ' . "\xEF\xBF\xBD" . '</span>',
                "foo \xF4\xFF\xFF\xFF"
            ],
        ];
    }
}
