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

namespace Tobento\App\Profiler\Controller;

use Tobento\App\Profiler\ProfilerInterface;
use Tobento\App\Profiler\ProfileRepositoryInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Menu\Menu;
use Psr\Http\Message\ResponseInterface;

/**
 * ProfilesController
 */
class ProfilesController
{
    /**
     * Display the profiles.
     *
     * @param ResponserInterface $responser
     * @param RouterInterface $router
     * @param ProfilerInterface $profiler
     * @param ProfileRepositoryInterface $profileRepository
     * @return ResponseInterface
     */
    public function index(
        ResponserInterface $responser,
        RouterInterface $router,
        ProfilerInterface $profiler,
        ProfileRepositoryInterface $profileRepository,
    ): ResponseInterface {
        
        $profiles = $profileRepository->findAll();
        
        return $responser->render(
            view: 'profiler/profiles/index',
            data: [
                'profiler' => $profiler,
                'profiles' => $profiles,
            ]
        );
    }
    
    /**
     * Display a profile.
     *
     * @param string $id
     * @param ResponserInterface $responser
     * @param RouterInterface $router
     * @param ProfilerInterface $profiler
     * @return ResponseInterface
     */
    public function show(
        string $id,
        ResponserInterface $responser,
        RouterInterface $router,
        ProfilerInterface $profiler,
    ): ResponseInterface {
        
        $profile = $profiler->findProfile($id);
        
        if (!$profile) {
            return $responser->create(code: 404);
        }

        return $responser->render(
            view: 'profiler/profiles/profile',
            data: [
                'profiler' => $profiler,
                'profile' => $profile,
            ]
        );
    }
    
    /**
     * Clears all profiles.
     *
     * @param ResponserInterface $responser
     * @param RouterInterface $router
     * @param ProfileRepositoryInterface $profileRepository
     * @return ResponseInterface
     */
    public function clear(
        ResponserInterface $responser,
        RouterInterface $router,
        ProfileRepositoryInterface $profileRepository,
    ): ResponseInterface {
        $profileRepository->clear();
        return $responser->redirect($router->url('profiler.profiles.index'));
    }
}