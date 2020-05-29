<?php

namespace Epubli\PermissionBundle\EventSubscriber;

use ApiPlatform\Core\Action\ExceptionAction;
use Epubli\PermissionBundle\Service\AuthToken;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PermissionSubscriber
 * @package Epubli\PermissionBundle\EventSubscriber
 */
class PermissionSubscriber implements EventSubscriberInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var PermissionDiscovery */
    private $permissionDiscovery;

    public function __construct(
        RequestStack $requestStack,
        PermissionDiscovery $permissionDiscovery
    ) {
        $this->requestStack = $requestStack;
        $this->permissionDiscovery = $permissionDiscovery;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof ExceptionAction) {
            return;
        }

        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        if ($this->permissionDiscovery->needsAuthentication(
            $controller,
            $request->getMethod(),
            $request->getPathInfo()
        )) {
            $permissionKey = $this->permissionDiscovery->getPermissionKey(
                $controller,
                $request->getMethod(),
                $request->getPathInfo()
            );

            $authToken = new AuthToken($this->requestStack);

            if (!$authToken->isValid() || !$authToken->hasPermissionKey($permissionKey)) {
                throw new AccessDeniedHttpException("Missing permission key: $permissionKey");
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 90],
        ];
    }
}
