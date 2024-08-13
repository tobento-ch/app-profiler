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
use JsonException;

/**
 * Table
 */
class Table
{
    /**
     * @var null|array
     */
    protected null|array $headings = null;    
    
    /**
     * Create a new Table.
     *
     * @param array $rows
     * @param string $title
     * @param string $description
     * @param array<array-key, string|int> $html The row names which has html code.
     * @param bool $renderEmpty
     */
    public function __construct(
        protected array $rows = [],
        protected string $title = '',
        protected string $description = '',
        protected array $html = [],
        protected bool $renderEmpty = false,
    ) {}

    /**
     * Returns the headings.
     *
     * @return array
     */
    public function headings(): array
    {
        if ($this->headings === null) {
            $first = (new Collection($this->rows))->first();
            $this->headings = (new Collection($first))->keys()->all();
        }
        
        return $this->headings;
    }
    
    /**
     * Returns the rows.
     *
     * @return array
     */
    public function rows(): array
    {
        return $this->rows;
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
        if (! $this->renderEmpty && empty($this->rows())) {
            return false;
        }
        
        return true;
    }

    /**
     * Verify a row.
     *
     * @param mixed $row
     * @return array
     */
    public function verifyRow(mixed $row): array
    {
        if (is_array($row)) {
            return $row;
        }
        
        return (new Collection($row))->toArray();
    }
    
    /**
     * Render a value.
     *
     * @param mixed $value
     * @param string|int $name
     * @return string
     */
    public function renderValue(mixed $value, string|int $name): string
    {
        $esc = true;

        if (in_array($name, $this->html)) {
            $esc = false;
        }
        
        if (is_array($value)) {
            try {
                $value = json_encode(
                    json_decode((new Collection($value))->toJson(), true, 512, JSON_THROW_ON_ERROR),
                    JSON_PRETTY_PRINT
                );
                return $esc ? $this->esc($value) : $value;
            } catch (JsonException $e) {
                return 'failed to JSON encode data!';
            }
        }
        
        if (is_string($value)) {
            return $esc ? $this->esc($value) : $value;
        }
        
        if (is_numeric($value) || is_bool($value)) {
            return $esc ? $this->esc((string)$value) : (string)$value;
        }
        
        if (is_null($value)) {
            return 'null';
        }
        
        return 'invalid row data!';
    }
    
    /**
     * Escapes string with htmlspecialchars.
     * 
     * @param string $string
     * @param int $flags
     * @param string $encoding
     * @param bool $double_encode
     * @return string
     */
    public function esc(
        string $string,
        int $flags = ENT_QUOTES,
        string $encoding = 'UTF-8',
        bool $double_encode = true
    ): string {
        return htmlspecialchars($string, $flags, $encoding, $double_encode);
    }
}