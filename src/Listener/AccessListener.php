<?php

declare(strict_types=1);

namespace CirclicalUser\Listener;

use CirclicalUser\Provider\DenyStrategyInterface;
use CirclicalUser\Service\AccessService;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use LogicException;

use function implode;
use function is_string;
use function strtolower;

class AccessListener implements ListenerAggregateInterface
{
    private AccessService $accessService;

    protected array $listeners;

    private ?DenyStrategyInterface $accessDeniedStrategy;

    public function __construct(AccessService $accessService, ?DenyStrategyInterface $accessDeniedStrategy = null)
    {
        $this->listeners = [];
        $this->accessService = $accessService;
        $this->accessDeniedStrategy = $accessDeniedStrategy;
    }

    /**
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 100): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'verifyAccess']);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'onDispatchError']);
    }

    public function detach(EventManagerInterface $events): void
    {
        foreach ($this->listeners as $index => $listener) {
            $events->detach($listener);
            unset($this->listeners[$index]);
        }
    }

    public function verifyAccess(MvcEvent $event): void
    {
        $route = $event->getRouteMatch();
        $controllerName = $route->getParam('controller');
        $actionName = $route->getParam('action');
        $middleware = $route->getParam('middleware');

        // support for zend-mvc's middleware definitions which do not pass controllers or actions
        if ($controllerName && $actionName) {
            if (!$this->accessService->requiresAuthentication($controllerName, $actionName)) {
                return;
            }
        } else {
            if ($middleware) {
                // zend-mvc supports both string and array definitions for middleware
                if (is_string($middleware)) {
                    $middleware = [$middleware];
                }

                // check all middleware handlers to ascertain access
                $requiresAuthentication = false;
                foreach ($middleware as $middlewareHandler) {
                    if (!$this->accessService->canAccessController($middlewareHandler)) {
                        $requiresAuthentication = true;
                        $controllerName = $middlewareHandler;
                    }
                }

                if (!$requiresAuthentication) {
                    return;
                }
            } else {
                throw new LogicException('A controller-action pair or middleware are required to verify access!');
            }
        }

        $eventError = null;
        if ($this->accessService->hasUser()) {
            if ($this->accessService->canAccessAction($controllerName, $actionName)) {
                return;
            }
            $eventError = AccessService::ACCESS_DENIED;
        } else {
            $eventError = AccessService::ACCESS_UNAUTHORIZED;
        }

        if ($this->accessDeniedStrategy !== null) {
            if ($this->accessDeniedStrategy->handle($event, $eventError)) {
                return;
            }
        }

        $event->setError($eventError);
        $event->setParam('route', $route->getMatchedRouteName());
        $event->setParam('controller', $controllerName);
        $event->setParam('action', $actionName ?? 'none');

        if ($roles = $this->accessService->getRoles()) {
            $event->setParam('roles', implode(',', $roles));
        } else {
            $event->setParam('roles', 'none');
        }

        $app = $event->getTarget();

        if (is_string($app)) {
            return;
        }

        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
        $app->getEventManager()->triggerEvent($event);
    }

    public function onDispatchError(MvcEvent $event)
    {
        switch ($event->getError()) {
            case AccessService::ACCESS_DENIED:
                $statusCode = 403;
                break;

            case AccessService::ACCESS_UNAUTHORIZED:
                $statusCode = 401;
                break;

            default:
                // do nothing if this is a different kind of error we should not trap
                return;
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $viewModel = new JsonModel();
        } else {
            $viewModel = new ViewModel();
            $viewModel->setTemplate('user/' . $statusCode);
        }

        $viewModel->setVariables($event->getParams());
        $response = $event->getResponse();
        if ($response instanceof Response) {
            $response->setStatusCode($statusCode);
        }
        $event->setViewModel($viewModel);
        $event->setResponse($response);
    }
}
