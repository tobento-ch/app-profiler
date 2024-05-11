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

namespace Tobento\App\Profiler\Collector;

use Tobento\App\Profiler\LateCollectorInterface;
use Tobento\App\Profiler\View;
use Tobento\App\Profiler\VarDumper;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Collection\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Session
 */
class Session implements LateCollectorInterface
{
    /**
     * Create a new Session.
     *
     * @param null|SessionInterface $session
     * @param array $hiddens
     */
    public function __construct(
        protected null|SessionInterface $session,
        protected array $hiddens = [],
    ) {}
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Session';
    }
    
    /**
     * Returns the collected data.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array Must be serializable natively by json_encode().
     */
    public function collect(ServerRequestInterface $request, ResponseInterface $response): array
    {
        if (is_null($this->session)) {
            return [];
        }
        
        $data = $this->session->all();
        
        foreach($this->hiddens as $key) {
            if (Arr::has($data, $key)) {
                $data = Arr::set($data, $key, '******');
            }
        }
        
        if (empty($data)) {
            return [];
        }
        
        return [
            'session' => VarDumper::dump($data),
        ];
    }
    
    /**
     * Render the collected data.
     *
     * @param ViewInterface $view
     * @param array $data The collected data.
     * @return string
     */
    public function render(ViewInterface $view, array $data): string
    {
        return $view->render('profiler/item', [
            'item' => new View\Item(
                html: $data['session'] ?? '',
                title: 'Session',
            ),
        ]);
    }
    
    /**
     * Returns any data used for menu e.g.
     *
     * @param array $data The collected data.
     * @return array<string, mixed>
     */
    public function data(array $data): array
    {
        return [];
    }
}