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

namespace Tobento\App\Profiler\Test\View;

use PHPUnit\Framework\TestCase;
use Tobento\App\Profiler\View\Table;
use Tobento\App\Profiler\View\Item;

class TableTest extends TestCase
{
    public function testHeadingsMethod()
    {
        $this->assertSame(['title'], (new Table(rows: [['title' => 'foo']]))->headings());
        $this->assertSame(['title', 'desc'], (new Table(rows: [['title' => 'foo', 'desc' => 'FOO']]))->headings());
        $this->assertSame([], (new Table(rows: []))->headings());
    }
    
    public function testRowsMethod()
    {
        $this->assertSame([['title' => 'foo']], (new Table(rows: [['title' => 'foo']]))->rows());
        $this->assertSame([], (new Table(rows: []))->rows());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('', (new Table(rows: []))->title());
        $this->assertSame('foo', (new Table(rows: [], title: 'foo'))->title());
    }
    
    public function testDescriptionMethod()
    {
        $this->assertSame('', (new Table(rows: []))->description());
        $this->assertSame('foo', (new Table(rows: [], description: 'foo'))->description());
    }
    
    public function testRenderMethod()
    {
        $this->assertFalse((new Table(rows: []))->render());
        $this->assertFalse((new Table(rows: [], renderEmpty: false))->render());
        $this->assertTrue((new Table(rows: [], renderEmpty: true))->render());
        $this->assertTrue((new Table(rows: [['title' => 'foo']]))->render());
    }
    
    public function testVerifyRowMethod()
    {
        $table = new Table(rows: []);
        
        $this->assertSame([], $table->verifyRow([]));
        $this->assertSame(['foo' => 'bar'], $table->verifyRow(['foo' => 'bar']));
        $this->assertSame([''], $table->verifyRow(''));
        $this->assertSame([100], $table->verifyRow(100));
    }
    
    public function testRenderValueMethod()
    {
        $table = new Table(rows: []);
        
        $this->assertSame('foo', $table->renderValue(value: 'foo', name: ''));
        $this->assertSame('100', $table->renderValue(value: 100, name: ''));
        $this->assertSame('1', $table->renderValue(value: true, name: ''));
        $this->assertSame(
            'invalid row data!',
            $table->renderValue(value: new \stdClass(), name: '')
        );
    }
    
    public function testRenderValueMethodIsEscapedByDefault()
    {
        $table = new Table(rows: []);
        
        $this->assertSame('&lt;p&gt;foo&lt;/p&gt;', $table->renderValue(value: '<p>foo</p>', name: ''));
        $this->assertSame(
            htmlspecialchars(json_encode(['foo' => '<p>foo</p>'], JSON_PRETTY_PRINT)),
            $table->renderValue(value: ['foo' => '<p>foo</p>'], name: '')
        );
    }
    
    public function testRenderValueMethodWithoutEscaping()
    {
        $table = new Table(rows: [], html: ['foo', 'bar']);
        
        $this->assertSame('<p>foo</p>', $table->renderValue(value: '<p>foo</p>', name: 'foo'));
        $this->assertSame(
            json_encode(['foo' => '<p>foo</p>'], JSON_PRETTY_PRINT),
            $table->renderValue(value: ['foo' => '<p>foo</p>'], name: 'bar')
        );
    }    
}