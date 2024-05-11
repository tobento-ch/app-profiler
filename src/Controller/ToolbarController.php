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
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Menu\Menu;
use Psr\Http\Message\ResponseInterface;

/**
 * ToolbarController
 */
class ToolbarController
{
    /**
     * Display a profile.
     *
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param RouterInterface $router
     * @param ProfilerInterface $profiler
     * @param ProfileRepositoryInterface $profileRepository
     * @return ResponseInterface
     */
    public function profile(
        RequesterInterface $requester,
        ResponserInterface $responser,
        RouterInterface $router,
        ProfilerInterface $profiler,
        ProfileRepositoryInterface $profileRepository,
    ): ResponseInterface {
        $id = $requester->input()->get('profiler_profile', '');
        $count = (int)$requester->input()->get('profiler_profiles_count');
        
        if ($count > 30) {
            $count = 30;
        }
        
        $profile = $profiler->findProfile($id);

        if (!$profile) {
            return $responser->json(data: [], code: 404);
        }
        
        /*$profiles = [];
        
        foreach($profileRepository->findAll(limit: $count+1) as $p) {
            $profiles[$p->id()] = $p->name();
        }*/
        
        $profileResponse = $responser->render(
            view: 'profiler/toolbar/toolbar',
            data: [
                'profiler' => $profiler,
                'profile' => $profile,
                'profiles' => $profileRepository->findAll(limit: $count+1),
            ],
        );

        return $responser->json(
            data: [
                'profile_html' => (string)$profileResponse->getBody(),
            ],
            code: 200, // is default
        );
    }
}