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

namespace SensioLabs\AnsiConverter\Theme;

use JetBrains\PhpStorm\Pure;

/**
 * Base theme.
 */
class Theme
{
    #[Pure] public function asCss($prefix = 'ansi_color'): string
    {
        $css = [];
        foreach ($this->asArray() as $name => $color) {
            $css[] = sprintf('.%s_fg_%s { color: %s }', $prefix, $name, $color);
            $css[] = sprintf('.%s_bg_%s { background-color: %s }', $prefix, $name, $color);
        }

        return implode("\n", $css);
    }

    public function asArray(): array
    {
        return [
            'black'   => 'black',
            'red'     => 'darkred',
            'green'   => 'green',
            'yellow'  => 'yellow',
            'blue'    => 'blue',
            'magenta' => 'darkmagenta',
            'cyan'    => 'cyan',
            'white'   => 'white',

            'brblack'   => 'black',
            'brred'     => 'red',
            'brgreen'   => 'lightgreen',
            'bryellow'  => 'lightyellow',
            'brblue'    => 'lightblue',
            'brmagenta' => 'magenta',
            'brcyan'    => 'lightcyan',
            'brwhite'   => 'white',
        ];
    }
}
