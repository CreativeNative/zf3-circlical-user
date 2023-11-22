<?php

declare(strict_types=1);

namespace CirclicalUser\Factory\Listener;

use CirclicalUser\Listener\UserEntityListener;
use DomainException;
use interop\container\containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserEntityListenerFactory implements FactoryInterface
{
    public function __invoke(containerinterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');

        if (!isset($config['circlical']['user']['doctrine']['entity'])) {
            throw new DomainException("CirclicalUser > You must specify the user Entity that CirclicalUser will use!");
        }

        return new UserEntityListener($config['circlical']['user']['doctrine']['entity']);
    }
}
