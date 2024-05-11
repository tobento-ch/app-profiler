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
use Tobento\Service\Menu\Menu;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * ProfilerResponseHandler
 */
class ProfilerResponseHandler
{
    /**
     * Create a new ProfilerResponseHandler.
     *
     * @param ProfilerInterface $profiler
     * @param StreamFactoryInterface $streamFactory
     * @param ViewInterface $view
     * @param bool $toolbar If to inject toolbar
     * @param array $exceptRouteNames The routes names not to profile.
     */
    public function __construct(
        protected ProfilerInterface $profiler,
        protected StreamFactoryInterface $streamFactory,
        protected null|ViewInterface $view,
        protected bool $toolbar = true,
        protected array $exceptRouteNames = [],
    ) {}
    
    /**
     * Handle the response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (! $this->canProfile($request, $response)) {
            return $response;
        }
        
        $profile = $this->profiler->createProfile($request, $response);
        
        if (! $this->canInjectProfileBar($request, $response)) {
            return $response;
        }
        
        return $this->injectProfileBar($profile, $response);
    }
    
    /**
     * Determine if request can be profiled.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    protected function canProfile(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        $routeName = $request->getAttribute('route.name');
        
        if (in_array($routeName, $this->exceptRouteNames)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Determine if profile bar can be injected into the response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    protected function canInjectProfileBar(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        if (is_null($this->view) || !$this->toolbar) {
            return false;
        }
        
        if (!str_contains($response->getHeaderLine('Content-Type'), 'text/html')) {
            return false;
        }

        return true;
    }
    
    /**
     * Handle html responses.
     *
     * @param Profile $profile
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function injectProfileBar(Profile $profile, ResponseInterface $response): ResponseInterface
    {
        $html = (string) $response->getBody();

        $this->view->assets()->clear();
        
        $toolbarHtml = $this->view->render(
            view: 'profiler/toolbar/toolbar',
            data: [
                'profiler' => $this->profiler,
                'profile' => $profile,
                'profiles' => [$profile],
            ],
        );

        $headHtml = $this->view->render('profiler/toolbar/head');
        
        $html = $this->injectHtml($html, $headHtml, '</head>');
        $html = $this->injectHtml($html, $toolbarHtml, '</body>');
        
        $body = $this->streamFactory->createStream();
        $body->write($html);

        return $response
            ->withBody($body)
            ->withoutHeader('Content-Length');
    }

    /**
     * Inject html code before a tag.
     *
     * @param string $html
     * @param string $code
     * @param string $before
     * @return string
     */
    private function injectHtml(string $html, string $code, string $before): string
    {
        $pos = strripos($html, $before);

        if ($pos === false) {
            return $html.$code;
        }

        return substr($html, 0, $pos).$code.substr($html, $pos);
    }
}