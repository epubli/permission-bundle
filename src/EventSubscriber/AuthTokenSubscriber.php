<?php

namespace Epubli\PermissionBundle\EventSubscriber;

use Epubli\PermissionBundle\Service\AuthToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AuthTokenSubscriber
 * @package Epubli\PermissionBundle\EventSubscriber
 */
class AuthTokenSubscriber implements EventSubscriberInterface
{
    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $header = $request->headers->get('Authorization');
        if (empty($header)) {
            $header = $request->headers->get('authorization');
        }
        if (empty($header)) {
            return;
        }
        $token = substr($header, strlen('Bearer '));

        $payload = json_decode(base64_decode(explode('.', $token)[1] ?? ''), true);

        $request->attributes->set(AuthToken::ATTRIBUTE_KEY, $payload);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 100],
        ];
    }
}
