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
use Tobento\App\Profiler\Profile;

class ProfileTest extends TestCase
{
    public function testProfileMethods()
    {
        $profile = new Profile(
            id: 'foo',
            method: 'GET',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: ['foo' => 'bar'],
        );
        
        $this->assertSame('foo', $profile->id());
        $this->assertSame('GET', $profile->method());
        $this->assertSame('page-foo', $profile->uri());
        $this->assertSame(200, $profile->statusCode());
        $this->assertSame('text/html', $profile->contentType());
        $this->assertSame(['foo' => 'bar'], $profile->data());
    }
    
    public function testIsUriVisitableMethod()
    {
        $profile = new Profile(
            id: 'foo',
            method: 'GET',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: ['foo' => 'bar'],
        );
        
        $this->assertTrue($profile->isUriVisitable());
        
        $profile = new Profile(
            id: 'foo',
            method: 'POST',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: ['foo' => 'bar'],
        );
        
        $this->assertFalse($profile->isUriVisitable());
    }
    
    public function testJsonSerializeMethod()
    {
        $profile = new Profile(
            id: 'foo',
            method: 'GET',
            uri: 'page-foo',
            statusCode: 200,
            contentType: 'text/html',
            time: null,
            data: ['foo' => 'bar'],
        );
        
        $this->assertSame([
            'id' => 'foo',
            'method' => 'GET',
            'uri' => 'page-foo',
            'statusCode' => 200,
            'contentType' => 'text/html',
            'time' => null,
            'data' => ['foo' => 'bar'],
        ], $profile->jsonSerialize());
    }
}