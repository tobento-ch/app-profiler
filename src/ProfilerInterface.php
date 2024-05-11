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

/**
 * ProfilerInterface
 */
interface ProfilerInterface
{
    /**
     * Add a new parameter.
     *
     * @param CollectorInterface $collector
     * @return static $this
     */
    public function addCollector(CollectorInterface $collector): static;
    
    /**
     * Returns the collectors.
     *
     * @return array<string, CollectorInterface>
     */
    public function collectors(): array;
    
    /**
     * Returns the collector names.
     *
     * @return array<array-key, string>
     */
    public function collectorNames(): array;

    /**
     * Create profile.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return Profile
     */
    public function createProfile(ServerRequestInterface $request, ResponseInterface $response): Profile;
    
    /**
     * Returns the found profile by id or null.
     *
     * @param string $id
     * @return null|Profile
     */
    public function findProfile(string $id): null|Profile;
    
    /**
     * Returns the collected data for the given collector and profile.
     *
     * @param string $collector
     * @param Profile $profile
     * @return array
     */
    public function getCollectedData(string $collector, Profile $profile): array;
    
    /**
     * Render the collector data for the given profile.
     *
     * @param string $collector
     * @param Profile $profile
     * @param ViewInterface $view
     * @return string
     */
    public function renderCollector(string $collector, Profile $profile, ViewInterface $view): string;
    
    /**
     * Generates a name to a valid id.
     *
     * @param string $name
     * @return string The generated name id.
     */
    public function nameToId(string $name): string;
}