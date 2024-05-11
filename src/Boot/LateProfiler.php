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
use Tobento\App\Profiler\LateCollectorInterface;
use Tobento\App\Profiler\ProfilerInterface;
use Tobento\App\Profiler\Controller\ToolbarController;
use Tobento\App\Profiler\Controller\ProfilesController;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Menu\MenusInterface;

/**
 * LateProfiler boot.
 */
class LateProfiler extends Boot
{
    public const INFO = [
        'boot' => [
            'installs view and asset files',
            'adds late collectors based on config',
            'adds toolbar and profile routes',
        ],
    ];
    
    public const BOOT = [
        Config::class,
        Migration::class,
        \Tobento\App\Http\Boot\Routing::class,
        \Tobento\App\Http\Boot\RequesterResponser::class,
        \Tobento\App\View\Boot\View::class,
        \Tobento\App\View\Boot\Form::class,
        \Tobento\App\View\Boot\Table::class,
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
        RouterInterface $router,
    ): void {
        // Install migrations:
        $migration->install(\Tobento\App\Profiler\Migration\LateProfiler::class);
        
        // Load the profiler configuration.
        $config = $config->load('profiler.php');
        
        $profiler = $this->app->get(ProfilerInterface::class);
        
        // adding collectors:
        foreach($config['collectors'] ?? [] as $collector => $with) {
            if (is_int($collector)) {
                $collector = $with;
                $with = [];
            }
            
            if ((new \ReflectionClass($collector))->implementsInterface(LateCollectorInterface::class)) {
                $profiler->addCollector($this->app->make($collector, $with));
            }
        }
        
        // Routes toolbar:
        if (!empty($config['toolbar'])) {
            $router->post('profiler/toolbar/profile', [ToolbarController::class, 'profile'])->name('profiler.toolbar.profile');
        }
        
        if (empty($config['profiles'])) {
            return;
        }
                
        // Routes Profiles:
        $router->get('profiler/profiles', [ProfilesController::class, 'index'])->name('profiler.profiles.index');
        $router->get('profiler/profiles/{id}', [ProfilesController::class, 'show'])->name('profiler.profiles.show');
        $router->post('profiler/profiles/clear', [ProfilesController::class, 'clear'])->name('profiler.profiles.clear');
        
        // Menu:
        $this->app->on(MenusInterface::class, static function(MenusInterface $menus) use ($router) {
            $menus->menu('main')
                ->link($router->url('profiler.profiles.index'), 'Profiles')
                ->id('profiler.profiles.index');
        });
    }
    
    /**
     * Returns the boot priority.
     *
     * @return int
     */
    public function priority(): int
    {
        // we set a low priority so that boot method gets called last.
        return -1000000;
    }
}