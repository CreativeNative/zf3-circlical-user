<?php

declare(strict_types=1);

namespace CirclicalUser\Factory\Validator;

use CirclicalUser\Provider\PasswordCheckerInterface;
use CirclicalUser\Validator\PasswordValidator;
use interop\container\containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PasswordValidatorFactory implements FactoryInterface
{
    public function __invoke(containerinterface $container, $requestedName, ?array $options = null)
    {
        return new PasswordValidator($container->get(PasswordCheckerInterface::class), $options);
    }
}
