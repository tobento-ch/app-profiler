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
 
namespace Tobento\App\Profiler\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Profiler\LateCollectorInterface;
use Tobento\App\Profiler\ProfilerInterface;
use Tobento\App\Profiler\ProfilerResponseHandler;
use Tobento\App\Profiler\Console\ConsoleProfiling;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Console\ConsoleInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Profiler boot.
 */
class Profiler extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads profiler config file',
            'implements interfaces based on config',
            'adds collectors based on config',
            'boots late profiler boot',
            'on response emit collects data from collectors and may inject the profiler toolbar',
        ],
    ];
    
    public const BOOT = [
        Config::class,
        \Tobento\App\Boot\Functions::class,
        Migration::class,
    ];
    
    /**
     * Boot application services.
     *
     * @param Config $config
     * @param Migration $migration
     * @return void
     */
    public function boot(
        Config $config,
        Migration $migration,
    ): void {
        // Install migrations:
        $migration->install(\Tobento\App\Profiler\Migration\Profiler::class);
        
        // Load the profiler configuration.
        $config = $config->load('profiler.php');
        
        if (empty($config['enabled'])) {
            return;
        }
        
        // setting interfaces:
        foreach($config['interfaces'] as $interface => $implementation) {
            $this->app->set($interface, $implementation);
        }
        
        $profiler = $this->app->get(ProfilerInterface::class);

        // adding collectors:
        foreach($config['collectors'] ?? [] as $collector => $with) {
            if (is_int($collector)) {
                $collector = $with;
                $with = [];
            }
            
            if (! (new \ReflectionClass($collector))->implementsInterface(LateCollectorInterface::class)) {
                $profiler->addCollector($this->app->make($collector, $with));
            }
        }
        
        // HTTP Profiling:
        $this->app->on(
            ResponseEmitterInterface::class,
            static function (
                ResponseEmitterInterface $emitter,
                ProfilerInterface $profiler,
                StreamFactoryInterface $streamFactory,
                null|ViewInterface $view
            ) use ($config) {
                $emitter->before(function(
                    ResponseInterface $response,
                    ServerRequestInterface $request
                ) use ($profiler, $streamFactory, $view, $config) {
                    $handler = new ProfilerResponseHandler(
                        profiler: $profiler,
                        streamFactory: $streamFactory,
                        view: $view,
                        exceptRouteNames: $config['unprofiled_route_names'] ?? [],
                        toolbar: !empty($config['toolbar']),
                    );
                    
                    return $handler->handle($request, $response);
                });
            }
        );
        
        // Console Profiling:
        if (!empty($config['console'])) {
            $this->app->on(ConsoleInterface::class, function (ConsoleInterface $console): ConsoleInterface {
                return new ConsoleProfiling($console, $this->app);
            });
        }
        
        $this->app->boot(LateProfiler::class);
    }
    
    /**
     * Returns the boot priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return 1000;
    }
}