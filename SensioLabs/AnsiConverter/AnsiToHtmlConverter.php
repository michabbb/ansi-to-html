<?php
declare(strict_types=1);

/*
 * This file is part of ansi-to-html.
 *
 * (c) 2013 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\AnsiConverter;

use JetBrains\PhpStorm\Pure;
use SensioLabs\AnsiConverter\Theme\Theme;

/**
 * Converts an ANSI text to HTML5.
 */
class AnsiToHtmlConverter
{
    protected Theme $theme;
    protected mixed $charset;
    protected mixed $inlineStyles;
    protected array $inlineColors;
    protected array $colorNames;

    #[Pure] public function __construct(Theme $theme = null, $inlineStyles = true, $charset = 'UTF-8')
    {
        $this->theme = $theme ?? new Theme();
        $this->inlineStyles = $inlineStyles;
        $this->charset = $charset;
        $this->inlineColors = $this->theme->asArray();
        $this->colorNames = [
            'black',
            'red',
            'green',
            'yellow',
            'blue',
            'magenta',
            'cyan',
            'white',
            '',
            '',
            'brblack',
            'brred',
            'brgreen',
            'bryellow',
            'brblue',
            'brmagenta',
            'brcyan',
            'brwhite',
        ];
    }

    public function convert($text): array|string|null
    {
        // remove cursor movement sequences
        $text = preg_replace('#\e\[(K|s|u|2J|2K|\d+([ABCDEFGJKST])|\d+;\d+([Hf]))#', '', $text);
        // remove character set sequences
        $text = preg_replace('#\e([()])(A|B|[0-2])#', '', $text);

        $text = htmlspecialchars($text, PHP_VERSION_ID >= 50400 ? ENT_QUOTES | ENT_SUBSTITUTE : ENT_QUOTES, $this->charset);

        // carriage return
        $text = preg_replace('#^.*\r(?!\n)#m', '', $text);

        $tokens = $this->tokenize($text);

        // a backspace remove the previous character but only from a text token
        foreach ($tokens as $i => $token) {
            if ('backspace' === $token[0]) {
                $j = $i;
                while (--$j >= 0) {
                    if ('text' === $tokens[$j][0] && $tokens[$j][1] !== '') {
                        $tokens[$j][1] = substr($tokens[$j][1], 0, -1);

                        break;
                    }
                }
            }
        }

        $html = '';
        foreach ($tokens as $token) {
            if ('text' === $token[0]) {
                $html .= $token[1];
            } elseif ('color' === $token[0]) {
                $html .= $this->convertAnsiToColor($token[1]);
            }
        }

        if ($this->inlineStyles) {
            $html = sprintf('<span style="background-color: %s; color: %s">%s</span>', $this->inlineColors['black'], $this->inlineColors['white'], $html);
        } else {
            $html = sprintf('<span class="ansi_color_bg_black ansi_color_fg_white">%s</span>', $html);
        }

        // remove empty span
        return preg_replace('#<span[^>]*></span>#', '', $html);
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    protected function convertAnsiToColor($ansi): ?string
    {
        $bg = 0;
        $fg = 7;
        $as = '';
        if ('0' !== $ansi && '' !== $ansi) {
            $options = explode(';', $ansi);

            foreach ($options as $option) {
                $option = (int)$option;
                if ($option >= 30 && $option < 38) {
                    $fg = $option - 30;
                } elseif ($option >= 40 && $option < 48) {
                    $bg = $option - 40;
                } elseif (39 === $option) {
                    $fg = 7;
                } elseif (49 === $option) {
                    $bg = 0;
                }
            }

            // options: bold => 1, underscore => 4, blink => 5, reverse => 7, conceal => 8
            if (in_array(1, $options, false)) {
                $fg += 10;
                $bg += 10;
            }

            if (in_array(4, $options, false)) {
                $as = '; text-decoration: underline';
            }

            if (in_array(7, $options, false)) {
                $tmp = $fg;
                $fg  = $bg;
                $bg  = $tmp;
            }
        }

        if ($this->inlineStyles) {
            return sprintf('</span><span style="background-color: %s; color: %s%s">', $this->inlineColors[$this->colorNames[$bg]], $this->inlineColors[$this->colorNames[$fg]], $as);
        }

        return sprintf('</span><span class="ansi_color_bg_%s ansi_color_fg_%s">', $this->colorNames[$bg], $this->colorNames[$fg]);
    }

    protected function tokenize($text): array
    {
        $tokens = [];
        preg_match_all("/\e\[(.*?)m|()/", $text, $matches, PREG_OFFSET_CAPTURE);

        $offset = 0;
        foreach ($matches[0] as $i => $match) {
            if ($match[1] - $offset > 0) {
                $tokens[] = [
                    'text',
                    substr($text, $offset, $match[1] - $offset)
                ];
            }
            $tokens[] = [
                "\x08" === $match[0] ? 'backspace' : 'color',
                $matches[1][$i][0]
            ];
            $offset   = $match[1] + strlen($match[0]);
        }
        if ($offset < strlen($text)) {
            $tokens[] = [
                'text',
                substr($text, $offset)
            ];
        }

        return $tokens;
    }
}
