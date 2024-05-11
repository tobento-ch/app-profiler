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
 
namespace Tobento\App\Profiler\View;

use Tobento\Service\Collection\Collection;

/**
 * Item
 */
class Item
{
    /**
     * Create a new Item.
     *
     * @param string $html
     * @param string $title
     * @param string $description
     * @param bool $renderEmpty
     */
    public function __construct(
        protected string $html,
        protected string $title = '',
        protected string $description = '',
        protected bool $renderEmpty = false,
    ) {}
    
    /**
     * Returns the html.
     *
     * @return string
     */
    public function html(): string
    {
        return $this->html;
    }

    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }
    
    /**
     * Returns the description.
     *
     * @return string
     */
    public function description(): string
    {
        return $this->description;
    }
    
    /**
     * Returns true if to render the table, otherwise false.
     *
     * @return bool
     */
    public function render(): bool
    {
        if (! $this->renderEmpty && $this->html === '') {
            return false;
        }
        
        return true;
    }
}