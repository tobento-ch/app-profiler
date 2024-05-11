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

class ProfilesTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Profiler\Boot\Profiler::class);
        return $app;
    }

    public function testProfilesScreenIsRenderedWithoutProfiles()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'profiler/profiles');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Profiles')
            ->assertBodyContains('Clear profiles');
    }    
    
    public function testProfilesScreenIsRendered()
    {
        $http = $this->fakeHttp();
        
        // make a request as to create a profile:
        $http->request(method: 'GET', uri: 'page-foo');
        $http->response()->assertStatus(404);
        
        $http->request(method: 'GET', uri: 'profiler/profiles');
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('page-foo');
        
        $app = $this->getApp();
        $app->get(ProfileRepositoryInterface::class)->clear();
    }
    
    public function testProfilesAreNotAvailableIfDisabled()
    {
        $config = $this->fakeConfig();
        $config->with('profiler.profiles', false);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'profiler/profiles');
        
        $http->response()->assertStatus(404);
    }
    
    public function testProfileScreenIsRendered()
    {
        $http = $this->fakeHttp();
        
        // make a request as to create a profile:
        $http->request(method: 'GET', uri: 'page-foo');
        $http->response()->assertStatus(404);
        
        $app = $this->getApp();
        $repo = $app->get(ProfileRepositoryInterface::class);
        $profile = $repo->findAll()[0] ?? null;
        
        $http->request(method: 'GET', uri: 'profiler/profiles/'.$profile?->id());
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Profile')
            ->assertBodyContains('Middleware')
            ->assertBodyContains('Routes');
        
        $repo->clear();
    }
    
    public function testProfileReturnsNotFoundIfProfileNotExist()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'profiler/profiles/uweqoiue433452sdfs');
        $http->response()->assertStatus(404);
    }
    
    public function testProfileIsNotAvailableIfDisabled()
    {
        $config = $this->fakeConfig();
        $config->with('profiler.profiles', false);
        
        $http = $this->fakeHttp();
        
        // make a request as to create a profile:
        $http->request(method: 'GET', uri: 'page-foo');
        $http->response()->assertStatus(404);
        
        $app = $this->getApp();
        $repo = $app->get(ProfileRepositoryInterface::class);
        $profile = $repo->findAll()[0] ?? null;
        
        $http->request(method: 'GET', uri: 'profiler/profiles/'.$profile?->id());
        $http->response()->assertStatus(404);
        
        $repo->clear();
    }
    
    public function testProfilesGetsCleared()
    {
        $http = $this->fakeHttp();
        
        // make a request as to create a profile:
        $http->request(method: 'GET', uri: 'page-foo');
        $http->response()->assertStatus(404);
        
        $app = $this->getApp();
        $repo = $app->get(ProfileRepositoryInterface::class);
        $this->assertSame(1, count($repo->findAll()));
        
        $http->request(method: 'POST', uri: 'profiler/profiles/clear');
        $http->response()->assertStatus(302);
        
        $this->assertSame(0, count($repo->findAll()));
        $repo->clear();
    }
    
    public function testProfilesCannotBeClearedIfDisabled()
    {
        $config = $this->fakeConfig();
        $config->with('profiler.profiles', false);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'profiler/profiles/clear');
        $http->response()->assertStatus(404);
    }
}