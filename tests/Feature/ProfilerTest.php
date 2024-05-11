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

namespace Tobento\App\Profiler\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Profiler\ProfileRepositoryInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Responser\ResponserInterface;

class ProfilerTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
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

    public function testToolbarIsInjectedIfHtml()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Profile')
            ->assertNodeExists('#profiler');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testToolbarIsNotInjectedIfHtmlAndToolbarIsDisabled()
    {
        $config = $this->fakeConfig();
        $config->with('profiler.toolbar', false);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Profile')
            ->assertNodeMissing('#profiler');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testToolbarIsNotInjectedIfHtmlButProfilerIsDisabledAtAll()
    {
        $config = $this->fakeConfig();
        $config->with('profiler.enabled', false);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app = $this->getApp();
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Profile')
            ->assertNodeMissing('#profiler');
    }    
    
    public function testToolbarIsNotInjectedIfNotHtml()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog-json');
        
        $app = $this->getApp();
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog-json', function (ResponserInterface $responser) {
                return $responser->json(data: ['key' => 'value']);
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Profile');
        
        $app->get(ProfileRepositoryInterface::class)->clear();
    }    
}