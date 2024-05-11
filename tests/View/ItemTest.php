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
use Tobento\App\Profiler\View\Item;

class ItemTest extends TestCase
{
    public function testHtmlMethod()
    {
        $this->assertSame('<p>lorem</p>', (new Item(html: '<p>lorem</p>'))->html());
        $this->assertSame('', (new Item(html: ''))->html());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('', (new Item(html: ''))->title());
        $this->assertSame('Foo', (new Item(html: '', title: 'Foo'))->title());
    }
    
    public function testDescriptionMethod()
    {
        $this->assertSame('', (new Item(html: ''))->description());
        $this->assertSame('Foo', (new Item(html: '', description: 'Foo'))->description());
    }
    
    public function testRenderMethod()
    {
        $this->assertFalse((new Item(html: ''))->render());
        $this->assertFalse((new Item(html: '', renderEmpty: false))->render());
        $this->assertTrue((new Item(html: '', renderEmpty: true))->render());
        $this->assertTrue((new Item(html: 'foo'))->render());
    }
}