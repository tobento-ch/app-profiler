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

namespace Tobento\App\Profiler\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Profiler\JsonFileProfileRepository;
use Tobento\App\Profiler\ProfileRepositoryInterface;
use Tobento\App\Profiler\Profile;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Filesystem\JsonFile;

class JsonFileProfileRepositoryTest extends TestCase
{
    public function testImplementsProfileRepositoryInterface()
    {
        $repo = new JsonFileProfileRepository(
            storageDir: __DIR__.'/tmp/profiles/'
        );
        
        $this->assertInstanceof(ProfileRepositoryInterface::class, $repo);
        
        (new Dir())->delete(__DIR__.'/tmp/profiles/');
    }
    
    public function testWriteMethod()
    {
        $repo = new JsonFileProfileRepository(
            storageDir: __DIR__.'/tmp/profiles/'
        );
        
        $this->assertSame(0, count($repo->findAll()));
        
        $profile = new Profile(
            id: 'foo',
            method: 'GET',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: [],
        );
        
        $repo->write($profile);
        
        $this->assertSame(1, count($repo->findAll()));
        
        (new Dir())->delete(__DIR__.'/tmp/profiles/');
    }
    
    public function testFindByIdMethod()
    {
        $repo = new JsonFileProfileRepository(
            storageDir: __DIR__.'/tmp/profiles/'
        );
        
        $profile = new Profile(
            id: 'foo',
            method: 'GET',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: [],
        );
        
        $repo->write($profile);
        
        $this->assertNull($repo->findById('bar'));
        $this->assertSame('page-foo', $repo->findById('foo')->uri());
        
        (new Dir())->delete(__DIR__.'/tmp/profiles/');
    }
    
    public function testFindAllMethodIsSortedByFileDateAsDefault()
    {
        $repo = new JsonFileProfileRepository(
            storageDir: __DIR__.'/tmp/profiles/'
        );
        
        $repo->write(new Profile(
            id: 'foo',
            method: 'GET',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: [],
        ));
        
        $repo->write(new Profile(
            id: 'bar',
            method: 'GET',
            uri: 'page-bar',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: [],
        ));
        
        $this->assertSame('bar', $repo->findAll()[0]->id());
        $this->assertSame('foo', $repo->findAll()[1]->id());
        
        (new Dir())->delete(__DIR__.'/tmp/profiles/');
    }
    
    public function testFindAllMethodLimitsProfiles()
    {
        $repo = new JsonFileProfileRepository(
            storageDir: __DIR__.'/tmp/profiles/'
        );
        
        $repo->write(new Profile(
            id: 'foo',
            method: 'GET',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: [],
        ));
        
        $repo->write(new Profile(
            id: 'bar',
            method: 'GET',
            uri: 'page-bar',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: [],
        ));
        
        $this->assertSame(2, count($repo->findAll()));
        $this->assertSame(1, count($repo->findAll(limit: [1])));
        $this->assertSame(2, count($repo->findAll(limit: [3])));
        $this->assertSame('foo', $repo->findAll(limit: [1, 1])[0]->id()); // with offset
        
        (new Dir())->delete(__DIR__.'/tmp/profiles/');
    }
    
    public function testClearMethod()
    {
        $repo = new JsonFileProfileRepository(
            storageDir: __DIR__.'/tmp/profiles/'
        );
        
        $repo->write(new Profile(
            id: 'foo',
            method: 'GET',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: [],
        ));
        
        $this->assertSame(1, count($repo->findAll()));
        
        $repo->clear();
        
        $this->assertSame(0, count($repo->findAll(limit: [1])));
        
        (new Dir())->delete(__DIR__.'/tmp/profiles/');
    }
}