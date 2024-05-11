<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\App\Profiler\ProfilerInterface;
use Tobento\App\Profiler\Profiler;
use Tobento\App\Profiler\ProfileRepositoryInterface;
use Tobento\App\Profiler\JsonFileProfileRepository;
use function Tobento\App\{directory};

return [
    
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Profiler
    |--------------------------------------------------------------------------
    |
    | Never enable the profiler in production environments as it
    | may lead to major security vulnerabilities in your project.
    |
    */

    'enabled' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Profiles, Toolbar And Console Profiling
    |--------------------------------------------------------------------------
    */
    
    'profiles' => true,
    'toolbar' => true,
    'console' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Collectors
    |--------------------------------------------------------------------------
    |
    | Configure any collectors for your application.
    |
    | see: https://github.com/tobento-ch/app-profiler#available-collectors
    |
    */
    
    'collectors' => [
        \Tobento\App\Profiler\Collector\Logs::class => [
            'exceptLoggers' => ['null'],
        ],
        \Tobento\App\Profiler\Collector\Boots::class,
        \Tobento\App\Profiler\Collector\RequestResponse::class,
        \Tobento\App\Profiler\Collector\Middleware::class,
        \Tobento\App\Profiler\Collector\Routes::class,
        \Tobento\App\Profiler\Collector\Session::class,
        /*\Tobento\App\Profiler\Collector\Session::class => [
            'hiddens' => [
                '_session_flash_once',
                '_session_flash.old',
            ],
        ],*/
        \Tobento\App\Profiler\Collector\StorageQueries::class,
        \Tobento\App\Profiler\Collector\View::class => [
            'collectViews' => true,
            'collectAssets' => true,
        ],
        \Tobento\App\Profiler\Collector\Events::class,
        \Tobento\App\Profiler\Collector\Jobs::class,
        \Tobento\App\Profiler\Collector\Translation::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Unprofiled Route Names
    |--------------------------------------------------------------------------
    |
    | Configure any route names not being profiled.
    |
    */
    
    'unprofiled_route_names' => [
        'profiler.toolbar.profile',
        'profiler.profiles.index',
        'profiler.profiles.show',
        'profiler.profiles.clear',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Profiler Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names only its implementation if needed!
    |
    */
    
    'interfaces' => [
        ProfileRepositoryInterface::class => static function() {
            return new JsonFileProfileRepository(
                storageDir: directory('app').'storage/profiler/',
            );
        },
        
        ProfilerInterface::class => Profiler::class,
    ],
];