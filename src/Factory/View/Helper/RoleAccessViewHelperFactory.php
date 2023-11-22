<?php

declare(strict_types=1);

namespace CirclicalUser\Factory\View\Helper;

use CirclicalUser\Service\AccessService;
use CirclicalUser\View\Helper\RoleAccessViewHelper;
use interop\container\containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RoleAccessViewHelperFactory implements FactoryInterface
{
    public function __invoke(containerinterface $container, $requestedName, ?array $options = null)
    {
        return new RoleAccessViewHelper(
            $container->get(AccessService::class)
        );
    }
}
