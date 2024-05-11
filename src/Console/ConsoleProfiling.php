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

namespace Tobento\App\Profiler\Console;

use Tobento\App\AppInterface;
use Tobento\App\Profiler\ProfilerInterface;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Console\CommandInterface;
use Tobento\Service\Console\ExecutedInterface;
use Tobento\Service\Console\ConsoleException;
use Tobento\Service\Console\CommandNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * ConsoleProfiling
 */
class ConsoleProfiling implements ConsoleInterface
{
    /**
     * Create a new ConsoleProfiling.
     *
     * @param ConsoleInterface $console
     * @param AppInterface $app
     */
    public function __construct(
        protected ConsoleInterface $console,
        protected AppInterface $app,
    ) {}

    /**
     * Returns the console name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->console->name();
    }
    
    /**
     * Add a command.
     *
     * @param string|CommandInterface $command
     * @return static $this
     */
    public function addCommand(string|CommandInterface $command): static
    {
        $this->console->addCommand($command);
        return $this;
    }
    
    /**
     * Returns true if command exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function hasCommand(string $name): bool
    {
        return $this->console->hasCommand($name);
    }
    
    /**
     * Returns the command or null if not exists.
     *
     * @param string $name The command name or classname.
     * @return CommandInterface
     * @throws CommandNotFoundException
     */
    public function getCommand(string $name): CommandInterface
    {
        return $this->console->getCommand($name);
    }
    
    /**
     * Run console.
     *
     * @return int
     * @throws ConsoleException
     */
    public function run(): int
    {
        $status = $this->console->run();
        
        $profiler = $this->app->get(ProfilerInterface::class);
        $request = $this->app->get(ServerRequestInterface::class);
        $uriFactory = $this->app->get(UriFactoryInterface::class);
        
        $input = implode(' ', $_SERVER['argv'] ?? []);
        $request = $request
            ->withUri($uriFactory->createUri($input))
            ->withMethod('BATCH');
        
        $response = $this->app->get(ResponseInterface::class);
        
        $profiler->createProfile($request, $response);
        
        return $status;
    }
    
    /**
     * Execute a command.
     *
     * @param string|CommandInterface $command A command name, classname or class instance.
     * @param array $input
     *   arguments: ['username' => 'Tom'] or ['username' => ['Tom', 'Tim']]
     *   options: ['--some-option' => 'value'] or ['--some-option' => ['value']]
     * @return ExecutedInterface
     * @throws ConsoleException
     */
    public function execute(string|CommandInterface $command, array $input = []): ExecutedInterface
    {
        return $this->console->execute($command, $input);
    }
}