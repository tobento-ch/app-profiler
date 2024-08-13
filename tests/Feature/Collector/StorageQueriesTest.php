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
use Tobento\Service\Storage\StorageInterface;

class StorageQueriesTest extends \Tobento\App\Testing\TestCase
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

    public function testQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\User\Boot\User::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Storage Queries')
            ->assertBodyContains('time')
            ->assertBodyContains('statement');
        
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testQueriesAreNotDisplayedIfNone()
    {
        $app = $this->getApp();
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Storage Queries');
        
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testGetQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $products = $storage->table('products')->get();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('SELECT `id`, `sku` FROM `products`');
        
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testFindQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $product = $storage->table('products')->find(12);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('SELECT `id`, `sku` FROM `products` WHERE `id` = ?');

        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testFirstQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $product = $storage->table('products')->first();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('SELECT `id`, `sku` FROM `products`');

        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testValueQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $value = $storage->table('products')->value('sku');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('SELECT `sku` FROM `products`');
        
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testColumnQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $value = $storage->table('products')->column('sku');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('SELECT `id`, `sku` FROM `products`');
        
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testCountQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $value = $storage->table('products')->count();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Storage Queries');
        
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testInsertQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $inserted = $storage->table('products')->insert(['sku' => 'foo']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('INSERT INTO `products` (`sku`) VALUES (?)');

        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testInsertItemsQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $inserted = $storage->table('products')->insertItems([
            ['sku' => 'glue'],
            ['sku' => 'pencil'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('insertItems()');

        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testUpdateQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $updated = $storage->table('products')->update(['sku' => 'glue']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('UPDATE `products` SET `sku` = ?');

        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testUpdateOrInsertQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $inserted = $storage->table('products')->insertItems([
            ['sku' => 'glue'],
            ['sku' => 'pencil'],
        ]);        
        $items = $storage->table('products')->updateOrInsert(['id' => 2], ['sku' => 'glue']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('UPDATE `products` SET `sku` = ? WHERE `id` = ?');

        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testDeleteQueriesAreDisplayed()
    {
        $app = $this->getApp();
        $app->boot(\Tobento\App\Database\Boot\Database::class);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $app->booting();
        $storage = $app->get(StorageInterface::class);
        $storage->tables()->add('products', ['id', 'sku'], 'id');
        $items = $storage->table('products')->delete();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('DELETE FROM `products`');

        $app->get(ProfileRepositoryInterface::class)->clear();
    }
}