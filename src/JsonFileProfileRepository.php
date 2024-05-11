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

use Tobento\Service\Filesystem\JsonFile;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\FileCreator\FileCreator;
use Tobento\Service\FileCreator\FileCreatorException;
use Throwable;

/**
 * JsonFileProfileRepository
 */
class JsonFileProfileRepository implements ProfileRepositoryInterface
{
    /**
     * Create a new JsonFileProfileRepository
     *
     * @param string $storageDir The storage dir where to store the file
     */
    public function __construct(
        protected string $storageDir,
    ) {
        $this->storageDir = rtrim($storageDir, '/').'/';
    }

    /**
     * Write profile.
     *
     * @param Profile $profile
     * @return void
     */
    public function write(Profile $profile): void
    {
        $filename = $this->storageDir.basename($profile->id()).'.json';
        
        try {
            (new FileCreator())
                ->content(json_encode($profile))
                ->create($filename, FileCreator::CONTENT_NEW);
        } catch (FileCreatorException $e) {
            throw $e;
        }
    }
    
    /**
     * Returns the found profile or null.
     *
     * @param string $id
     * @return null|Profile
     */
    public function findById(string $id): null|Profile
    {
        $file = new JsonFile($this->storageDir.basename($id).'.json');
        
        if (
            ! $file->isWithinDir($this->storageDir)
            || ! $file->isFile()
        ) {
            return null;
        }
        
        try {
            return new Profile(...$file->toArray());
        } catch (Throwable $e) {
            return null;
        }
    }
    
    /**
     * Returns the found profiles by the given parameters.
     *
     * @param array $where Usually where parameters.
     * @param array $orderBy The order by parameters.
     * @param null|int|array $limit The limit e.g. 5 or [5(number), 10(offset)].
     * @return iterable<Profile>
     */
    public function findAll(array $where = [], array $orderBy = [], null|int|array $limit = null): iterable
    {
        if (! (new Dir())->has($this->storageDir)) {
            return [];
        }
        
        $files = [];
        
        foreach (new \DirectoryIterator($this->storageDir) as $file) {
            if ($file->getExtension() === 'json') {
                $files[] = [
                    'time' => $file->getMTime(),
                    'path' => $file->getPathname(),
                ];
            }
        }
        
        // Sort the files, newest first:
        usort($files, function ($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        // Limit:
        $num = 100;
        $offset = 0;
        
        if (is_int($limit)) {
            $num = $limit;
        } elseif (is_array($limit)) {
            $num = $limit[0] ?? 100;
            $offset = $limit[1] ?? 00;
        }
        
        $files = array_slice($files, $offset, $num);
        
        // Create profiles:
        $profiles = [];
        
        foreach($files as $file) {
            try {
                $file = new JsonFile($file['path']);
                $data = json_decode($file->getContent(), true, 512, JSON_THROW_ON_ERROR);
                $profiles[] = new Profile(...$data);
            } catch (Throwable $e) {
                continue;
            }
        }
        
        return $profiles;
    }
    
    /**
     * Clears all profiles.
     *
     * @return void
     */
    public function clear(): void
    {
        (new Dir())->delete($this->storageDir);
    }
}