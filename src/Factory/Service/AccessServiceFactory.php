<?php

declare(strict_types=1);

namespace CirclicalUser\Factory\Service;

use CirclicalUser\Exception\InvalidRoleException;
use CirclicalUser\Mapper\GroupPermissionMapper;
use CirclicalUser\Mapper\RoleMapper;
use CirclicalUser\Mapper\UserMapper;
use CirclicalUser\Mapper\UserPermissionMapper;
use CirclicalUser\Provider\RoleProviderInterface;
use CirclicalUser\Service\AccessService;
use CirclicalUser\Service\AuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use LogicException;
use Psr\Container\ContainerInterface;

class AccessServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        $userConfig = $config['circlical']['user'];

        if (!isset($userConfig['guards'])) {
            throw new LogicException("You don't have any guards set up! Please follow the steps in the README.  Define an empty guards definition to get rid of this error.");
        }

        $guards = $userConfig['guards'] ?? [];
        $userProviderClass = $userConfig['providers']['user'] ?? UserMapper::class;

        $roleProviderClass = $userConfig['providers']['role'] ?? RoleMapper::class;

        /** @var RoleProviderInterface $roleProvider */
        $roleProvider = $container->get($roleProviderClass);
        $groupRuleProviderClass = $userConfig['providers']['rule']['group'] ?? GroupPermissionMapper::class;
        $userRuleProviderClass = $userConfig['providers']['rule']['user'] ?? UserPermissionMapper::class;

        $superAdminRole = null;
        if (!empty($userConfig['access']['superadmin']['role_name'])) {
            $superAdminRole = $roleProvider->getRoleWithName($userConfig['access']['superadmin']['role_name']);
            if ($userConfig['access']['superadmin']['throw_exception_when_missing'] ?? false) {
                throw new InvalidRoleException(
                    'A super-admin role was configured in the application files, yet could not be found in the database. Please create this role, or disable exceptions in config.'
                );
            }
        }

        $accessService = new AccessService(
            $guards,
            $roleProvider,
            $container->get($groupRuleProviderClass),
            $container->get($userRuleProviderClass),
            $container->get($userProviderClass),
            $superAdminRole
        );

        $user = $container->get(AuthenticationService::class)->getIdentity();

        if ($user) {
            $accessService->setUser($user);
        }

        return $accessService;
    }
}
