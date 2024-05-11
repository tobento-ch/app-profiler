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

namespace Tobento\App\Profiler;

use JsonSerializable;

/**
 * Profile
 */
class Profile implements JsonSerializable
{
    /**
     * Create a new Profile.
     *
     * @param string $id
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     * @param string $contentType
     * @param null|int $time
     * @param array $data
     */
    public function __construct(
        protected string $id,
        protected string $method,
        protected string $uri,
        protected int $statusCode,
        protected string $contentType,
        protected null|int $time,
        protected array $data,
    ) {}
    
    /**
     * Returns the profile id.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }
    
    /**
     * Returns the method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }
    
    /**
     * Returns the uri.
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }
    
    /**
     * Returns true if uri is visitable, otherwise false.
     *
     * @return bool
     */
    public function isUriVisitable(): bool
    {
        if ($this->method() !== 'GET') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Returns the status code.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * Returns the content type.
     *
     * @return string
     */
    public function contentType(): string
    {
        return $this->contentType;
    }
    
    /**
     * Returns the time.
     *
     * @return null|int
     */
    public function time(): null|int
    {
        return $this->time;
    }
    
    /**
     * Returns the profile data.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id(),
            'method' => $this->method(),
            'uri' => $this->uri(),
            'statusCode' => $this->statusCode(),
            'contentType' => $this->contentType(),
            'time' => $this->time(),
            'data' => $this->data(),
        ];
    }
}