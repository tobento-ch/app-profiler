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

use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Clock\ClockInterface;

/**
 * Profiler
 */
class Profiler implements ProfilerInterface
{
    /**
     * @var array<string, CollectorInterface>
     */
    protected array $collectors = [];

    /**
     * Create a new Profiler.
     *
     * @param ProfileRepositoryInterface $profileRepository
     * @param ClockInterface $clock
     */
    public function __construct(
        protected ProfileRepositoryInterface $profileRepository,
        protected ClockInterface $clock,
    ) {}
    
    /**
     * Add a new parameter.
     *
     * @param CollectorInterface $collector
     * @return static $this
     */
    public function addCollector(CollectorInterface $collector): static
    {
        $this->collectors[$collector->name()] = $collector;
        
        return $this;
    }

    /**
     * Returns the collectors.
     *
     * @return array<string, CollectorInterface>
     */
    public function collectors(): array
    {
        return $this->collectors;
    }
    
    /**
     * Returns the collector names.
     *
     * @return array<array-key, string>
     */
    public function collectorNames(): array
    {
        return array_keys($this->collectors);
    }

    /**
     * Create profile.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return Profile
     */
    public function createProfile(ServerRequestInterface $request, ResponseInterface $response): Profile
    {
        $data = [];
        
        foreach($this->collectors as $collector) {
            $data[$collector->name()] = $collector->collect($request, $response);
        }
        
        $profile = new Profile(
            id: bin2hex(random_bytes(50)),
            method: $request->getMethod(),
            uri: rawurldecode((string)$request->getUri()),
            statusCode: $response->getStatusCode(),
            contentType: $response->getHeaderLine('Content-Type'),
            time: $this->clock->now()->getTimestamp(),
            data: $data,
        );
        
        $this->profileRepository->write($profile);
        
        return $profile;
    }
    
    /**
     * Returns the found profile by id or null.
     *
     * @param string $id
     * @return null|Profile
     */
    public function findProfile(string $id): null|Profile
    {
        if (!ctype_alnum($id)) {
            return null;
        }

        return $this->profileRepository->findById($id);
    }
    
    /**
     * Returns the collected data for the given collector and profile.
     *
     * @param string $collector
     * @param Profile $profile
     * @return array
     */
    public function getCollectedData(string $collector, Profile $profile): array
    {
        if (!isset($this->collectors[$collector])) {
            return [];
        }
                
        $data = $profile->data()[$collector] ?? [];
        
        return is_array($data) ? $data : [];
    }
    
    /**
     * Render the collector data for the given profile.
     *
     * @param string $collector
     * @param Profile $profile
     * @param ViewInterface $view
     * @return string
     */
    public function renderCollector(string $collector, Profile $profile, ViewInterface $view): string
    {
        $data = $this->getCollectedData($collector, $profile);
        
        if (empty($data)) {
            return '';
        }
        
        return $this->collectors[$collector]->render($view, $data);
    }
    
    /**
     * Generates a name to a valid id.
     *
     * @param string $name
     * @return string The generated name id.
     */
    public function nameToId(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_\-\']/', '-', trim(strtolower($name)));
    }
}