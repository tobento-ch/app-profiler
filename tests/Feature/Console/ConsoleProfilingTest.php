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

namespace Tobento\App\Profiler\Test\Feature\Console;

use Tobento\App\AppInterface;
use Tobento\App\Profiler\Console\ConsoleProfiling;
use Tobento\Service\Console\ConsoleInterface;

class ConsoleProfilingTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(\Tobento\App\Profiler\Boot\Profiler::class);
        return $app;
    }

    public function testConsoleIsNotProfiling()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Console\Boot\Console::class);
        
        $app->booting();
        $console = $app->get(ConsoleInterface::class);

        $this->assertFalse($console instanceof ConsoleProfiling);
    }
    
    public function testConsoleIsProfilingIfEnabled()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Console\Boot\Console::class);
        
        $config = $this->fakeConfig();
        $config->with('profiler.console', true);
        
        $app->booting();
        $console = $app->get(ConsoleInterface::class);

        $this->assertTrue($console instanceof ConsoleProfiling);
    }
}