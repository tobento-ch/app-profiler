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

namespace Tobento\App\Profiler\Test\Feature\Collector;

use Tobento\App\AppInterface;
use Tobento\App\Profiler\ProfileRepositoryInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\App\Logging\LoggersInterface;

class LogsTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(\Tobento\App\Profiler\Boot\Profiler::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (ResponserInterface $responser) {
                return $responser->html(
                    html: '<!DOCTYPE html><html><head></head><body><h1>Blog</h1></body></html>',
                    code: 200,
                );
            });
        });

        return $app;
    }
    
    public function testLogsAreNotDisplayedIfNone()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Logs');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }

    public function testLogsAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Logging\Boot\Logging::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $loggers = $app->get(LoggersInterface::class);
        $loggers->logger()->info('Log Message', ['foo' => 'bar']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Logs')
            ->assertBodyContains('Logger [default]')
            ->assertBodyContains('Log Message');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testLogsAreNotDisplayedIfLoggerIsDisabled()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Logging\Boot\Logging::class);
        
        $config = $this->fakeConfig();
        $config->with('profiler.collectors', [
            \Tobento\App\Profiler\Collector\Logs::class => [
                'exceptLoggers' => ['default'],
            ],
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $loggers = $app->get(LoggersInterface::class);
        $loggers->logger()->info('Log Message', ['foo' => 'bar']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Logger [default]');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
}