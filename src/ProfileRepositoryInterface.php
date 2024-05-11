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

/**
 * ProfileRepositoryInterface
 */
interface ProfileRepositoryInterface
{
    /**
     * Write profile.
     *
     * @param Profile $profile
     * @return void
     */
    public function write(Profile $profile): void;
    
    /**
     * Returns the found profile or null.
     *
     * @param string $id
     * @return null|Profile
     */
    public function findById(string $id): null|Profile;
    
    /**
     * Returns the found profiles by the given parameters.
     *
     * @param array $where Usually where parameters.
     * @param array $orderBy The order by parameters.
     * @param null|int|array $limit The limit e.g. 5 or [5(number), 10(offset)].
     * @return iterable<Profile>
     */
    public function findAll(array $where = [], array $orderBy = [], null|int|array $limit = null): iterable;
    
    /**
     * Clears all profiles.
     *
     * @return void
     */
    public function clear(): void;
}