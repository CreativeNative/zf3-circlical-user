<?php

declare(strict_types=1);

namespace CirclicalUser\Factory\Strategy;

use CirclicalUser\Strategy\RedirectStrategy;
use interop\container\containerinterface;
use InvalidArgumentException;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RedirectStrategyFactory implements FactoryInterface
{
    public function __invoke(containerinterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config['circlical']['user']['deny_strategy']['options'])) {
            throw new InvalidArgumentException("CirclicalUser > Please check your config. You specified the module-provided redirect strategy, but didn't include the provided configuration.");
        }
        $appliedOptions = $config['circlical']['user']['deny_strategy']['options'];

        return new RedirectStrategy($appliedOptions['controller'], $appliedOptions['action'], $appliedOptions['name'] ?? 'login-redirect');
    }
}
