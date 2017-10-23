<?php

namespace KP\CommandBus;

use KP\CommandBus\Event\PostCommandEvent;
use KP\CommandBus\Event\PreCommandEvent;
use KP\CommandBus\Exception\ClassDoesNotImplementCommandHandlerInterfaceException;
use KP\CommandBus\Exception\GenericCommandHandlerException;
use KP\CommandBus\Exception\NotRegisteredCommandHandlerException;
use KP\CommandBus\Response\Response;
use KP\CommandBus\Response\ResponseInterface;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Proxy\ProxyInterface;
use ProxyManager\Proxy\VirtualProxyInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Konrad Podgórski <konrad.podgorski@gmail.com>
 */
class CommandBus implements CommandBusInterface
{
    /**
     * @var CommandHandlerInterface[]
     */
    protected $commandHandlers;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * CommandBus constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param CommandHandlerInterface[] $commandHandlers
     *
     * @throws ClassDoesNotImplementCommandHandlerInterfaceException
     */
    public function registerCommandHandlers($commandHandlers)
    {
        foreach ($commandHandlers as $commandHandler) {
            if ($commandHandler instanceof CommandHandlerInterface) {
                // support for @url http://ocramius.github.io/ProxyManager/
                if ($commandHandler instanceof ProxyInterface) {
                    $ref = new \ReflectionClass($commandHandler);

                    $commandHandlerClassName = $ref->getParentClass()->getName();
                } else {
                    $commandHandlerClassName = get_class($commandHandler);
                }

                $this->commandHandlers[$commandHandlerClassName] = $commandHandler;

            } else {
                throw new ClassDoesNotImplementCommandHandlerInterfaceException(
                    get_class($commandHandler)
                );
            }
        }
    }

    /**
     * @param CommandInterface $command
     *
     * @return ResponseInterface
     *
     * @throws NotRegisteredCommandHandlerException
     */
    public function execute(CommandInterface $command)
    {
        $this->eventDispatcher->dispatch(Events::PRE_COMMAND, new PreCommandEvent($command));

        try {
            $commandHandlerClass = $command->getCommandHandlerClass();
            if (isset($this->commandHandlers[$commandHandlerClass])) {
                $results = $this->commandHandlers[$commandHandlerClass]->handle($command);
            } else {
                throw new NotRegisteredCommandHandlerException($commandHandlerClass);
            }
        } catch (GenericCommandHandlerException $e) {
            $results = new Response(null);
        }

        if ($results instanceof ResponseInterface) {
            $response = $results;
        } else {
            $response = new Response($results);
        }

        $this->eventDispatcher->dispatch(Events::POST_COMMAND, new PostCommandEvent($command, $response));

        return $response;
    }
}
