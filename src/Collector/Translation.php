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
use Tobento\App\AppInterface;
use Tobento\Service\Translation\MissingTranslationHandlerInterface;
use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Translation
 */
class Translation implements LateCollectorInterface
{
    /**
     * @var null|MissingTranslationHandlerInterface
     */
    protected null|MissingTranslationHandlerInterface $handler = null;
    
    /**
     * Create a new View.
     *
     * @param protected AppInterface $app
     */
    public function __construct(
        protected AppInterface $app,
    ) {
        if ($app->has(MissingTranslationHandlerInterface::class)) {
            $app->on(
                MissingTranslationHandlerInterface::class,
                function(MissingTranslationHandlerInterface $handler) {
                    return $this->createHandler($handler);
                }
            );
        } else {
            $app->set(
                MissingTranslationHandlerInterface::class,
                function() {
                    return $this->createHandler();
                }
            );
        }
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Translation';
    }
    
    /**
     * Returns the collected data.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array Must be serializable natively by json_encode().
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function collect(ServerRequestInterface $request, ResponseInterface $response): array
    {
        if (is_null($this->handler)) {
            return [];
        }
        
        return $this->handler->getData();
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
        return
            $view->render('profiler/table', [
                'table' => new View\Table(
                    rows: $data['missing'] ?? [],
                    title: 'Missing Translations',
                    description: 'Missing translations.',
                ),
            ]).
            $view->render('profiler/table', [
                'table' => new View\Table(
                    rows: $data['fallback'] ?? [],
                    title: 'Fallback Translations',
                    description: 'Missing translation message fallbacked to the locale defined.',
                ),
            ]).
            $view->render('profiler/table', [
                'table' => new View\Table(
                    rows: $data['fallbackToDefault'] ?? [],
                    title: 'Fallback To Default Translations',
                    description: 'Missing translation message fallbacked to default locale.',
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
    
    /**
     * Returns a newly created view wrapper.
     *
     * @param MissingTranslationHandlerInterface $handler
     * @return MissingTranslationHandlerInterface
     */
    protected function createHandler(
        null|MissingTranslationHandlerInterface $handler = null
    ): MissingTranslationHandlerInterface {
        return $this->handler = new class($handler) implements MissingTranslationHandlerInterface
        {
            private array $data = [];
            
            public function __construct(
                private null|MissingTranslationHandlerInterface $handler = null,
            ) {}

            public function getData(): array
            {
                return $this->data;
            }

            public function missing(
                string $translation,
                string $message,
                array $parameters,
                string $locale,
                string $requestedLocale
            ): string {

                // Message might be a real message (not a keyword) and the default locale is used,
                // so it would not be a missing message.
                // Handle it here, depending on the message set on your views and such.
                
                $this->data['missing'][] = [
                    'translation' => $translation,
                    'message' => $message,
                    'parameters' => $parameters,
                    'locale' => $locale,
                    'requested locale' => $requestedLocale,
                ];
                
                if ($this->handler) {
                    return $this->handler->missing(
                        translation: $translation,
                        message: $message,
                        parameters: $parameters,
                        locale: $locale,
                        requestedLocale: $requestedLocale,
                    );
                }
                
                return $translation;
            }

            public function fallback(
                string $translation,
                string $message,
                array $parameters,
                string $fallbackLocale,
                string $requestedLocale
            ): string {
                $this->data['fallback'][] = [
                    'translation' => $translation,
                    'message' => $message,
                    'parameters' => $parameters,
                    'fallback locale' => $fallbackLocale,
                    'requested locale' => $requestedLocale,
                ];
                
                if ($this->handler) {
                    return $this->handler->fallback(
                        translation: $translation,
                        message: $message,
                        parameters: $parameters,
                        fallbackLocale: $fallbackLocale,
                        requestedLocale: $requestedLocale,
                    );
                }

                return $translation;
            }

            public function fallbackToDefault(
                string $translation,
                string $message,
                array $parameters,
                string $defaultLocale,
                string $requestedLocale
            ): string {
                $this->data['fallbackToDefault'][] = [
                    'translation' => $translation,
                    'message' => $message,
                    'parameters' => $parameters,
                    'default locale' => $defaultLocale,
                    'requested locale' => $requestedLocale,
                ];
                
                if ($this->handler) {
                    return $this->handler->fallbackToDefault(
                        translation: $translation,
                        message: $message,
                        parameters: $parameters,
                        defaultLocale: $defaultLocale,
                        requestedLocale: $requestedLocale,
                    );
                }

                return $translation;
            }
        };
    }
}