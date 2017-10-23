<?php

namespace KP\CommandBus;

interface CommandBusInterface
{
    /**
     * @param CommandHandlerInterface[] $commandHandlers
     *
     * @throws ClassDoesNotImplementCommandHandlerInterfaceException
     */
    public function registerCommandHandlers($commandHandlers);

    /**
     * @param CommandInterface $command
     *
     * @return ResponseInterface
     *
     * @throws NotRegisteredCommandHandlerException
     */
    public function execute(CommandInterface $command);
}
