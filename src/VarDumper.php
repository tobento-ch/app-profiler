<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Profiler;

use Symfony\Component\VarDumper\Cloner;
use Symfony\Component\VarDumper\Dumper;

/**
 * VarDumper
 */
class VarDumper
{
    /**
     * Dumps the given var to HTML.
     *
     * @param mixed $var
     * @return string
     */
    public static function dump(mixed $var): string
    {
        $cloner = new Cloner\VarCloner();
        $dumper = new Dumper\HtmlDumper();
        $output = fopen('php://memory', 'r+b');
        $dumper->setOutput($output);
        $dumper->setDumpHeader('');
        $dumper->setDumpBoundaries(prefix: '<pre class=profiler-dump id=%s data-indent-pad="%s">', suffix: '</pre>');
        $dumper->dump($cloner->cloneVar($var));
        $result = stream_get_contents($output, -1, 0);
        fclose($output);
        return $result;
    }
}