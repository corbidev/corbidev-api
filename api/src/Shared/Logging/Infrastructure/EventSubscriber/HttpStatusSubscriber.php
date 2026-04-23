<?php

namespace App\Shared\Logging\Infrastructure\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HttpStatusSubscriber implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Stocke le status dans la requête
        $request->attributes->set('http_status', $response->getStatusCode());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.response' => ['onKernelResponse', 100],
        ];
    }
}