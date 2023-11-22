<?php

declare(strict_types=1);

namespace CirclicalUser\Factory\View\Helper;

use CirclicalUser\Service\AccessService;
use CirclicalUser\View\Helper\ControllerAccessViewHelper;
use interop\container\containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ControllerAccessViewHelperFactory implements FactoryInterface
{
    public function __invoke(containerinterface $container, $requestedName, ?array $options = null)
    {
        return new ControllerAccessViewHelper(
            $container->get(AccessService::class)
        );
    }
}
