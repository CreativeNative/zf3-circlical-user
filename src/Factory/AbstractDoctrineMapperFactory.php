<?php

declare(strict_types=1);

namespace CirclicalUser\Factory;

use interop\container\containerinterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

use function strstr;

class AbstractDoctrineMapperFactory implements AbstractFactoryInterface
{
    public function canCreate(containerinterface $container, $requestedName)
    {
        return strstr($requestedName, '\\Mapper\\') !== false;
    }

    public function __invoke(containerinterface $container, $requestedName, ?array $options = null)
    {
        $mapper = new $requestedName();
        $mapper->setEntityManager($container->get('doctrine.entitymanager.orm_default'));

        return $mapper;
    }
}
