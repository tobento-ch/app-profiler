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
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\CallableJob;
use Tobento\Service\Queue\QueueInterface;

class JobsTest extends \Tobento\App\Testing\TestCase
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

    public function testJobsAreNotDisplayedIfNone()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Jobs');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testJobsAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Queue\Boot\Queue::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $queue = $app->get(QueueInterface::class);
        $queue->push(new TestJob(name: 'FooJob'));
        
        $http->response()
            ->assertStatus(200)
            ->assertNodeExists('a[href="#profiler-jobs"]')
            ->assertBodyContains('Pushed Jobs')
            ->assertBodyContains('TestJob');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
}

final class TestJob extends CallableJob
{
    public function __construct(
        private string $name,
    ) {}
    
    public function handleJob(JobInterface $job): void
    {
        //
    }
    
    public function getPayload(): array
    {
        return [];
    }
}