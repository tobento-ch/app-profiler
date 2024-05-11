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
use Tobento\Service\Translation\TranslatorInterface;

class TranslationTest extends \Tobento\App\Testing\TestCase
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

    public function testTranslationAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Translation\Boot\Translation::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $app->get(TranslatorInterface::class)->trans('Some Message');
        
        $http->response()
            ->assertStatus(200)
            ->assertNodeExists('a[href="#profiler-translation"]')
            ->assertBodyContains('Missing Translations')
            ->assertBodyContains('Some Message');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testTranslationAreNotDisplayedIfNoMissed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Translation\Boot\Translation::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $http->response()
            ->assertStatus(200)
            ->assertNodeMissing('a[href="#profiler-translation"]');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testTranslationAreNotDisplayedIfNoBootedAtAll()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $http->response()
            ->assertStatus(200)
            ->assertNodeMissing('a[href="#profiler-translation"]');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }    
}