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

namespace Tobento\App\Profiler\Migration;

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\Action\DirCopy;
use Tobento\Service\Migration\Action\DirDelete;
use Tobento\Service\Dir\DirsInterface;

/**
 * LateProfiler
 */
class LateProfiler implements MigrationInterface
{
    /**
     * Create a new Migration.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {}
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Profiler view and asset files.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        return new Actions(
            new DirCopy(
                dir: $resources.'views/profiler/',
                destDir: $this->dirs->get('views').'profiler/',
                name: 'Profiler web views',
                type: 'views',
                description: 'Profiler web views.',
            ),
            new DirCopy(
                dir: $resources.'assets/profiler/',
                destDir: $this->dirs->get('public').'assets/profiler/',
                name: 'Profiler asset files',
                type: 'assets',
                description: 'Profiler asset files.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            new DirDelete(
                dir: $this->dirs->get('views').'profiler/',
                name: 'Profiler web views',
                type: 'views',
                description: 'Profiler web views.',
            ),
            new DirDelete(
                dir: $this->dirs->get('public').'assets/profiler/',
                name: 'Profiler asset files.',
                type: 'assets',
                description: 'Profiler asset files.',
            ),
        );
    }
}