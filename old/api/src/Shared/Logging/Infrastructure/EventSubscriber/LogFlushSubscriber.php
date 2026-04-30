<?php

namespace App\Shared\Logging\Infrastructure\EventSubscriber;

use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Shared\Logging\Infrastructure\Monolog\BufferedApiLogsHandler;
use Psr\Log\LoggerInterface;

class LogFlushSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BufferedApiLogsHandler $technicalHandler,
        private BufferedApiLogsHandler $businessHandler,
        private LoggerInterface $fallbackLogger
    ) {}

    public function onTerminate(TerminateEvent $event): void
    {
        $this->safeFlush();
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->safeFlush();
    }

    private function safeFlush(): void
    {
        try {
            $this->technicalHandler->flush();
            $this->businessHandler->flush();
        } catch (\Throwable $e) {
            $this->fallbackLogger->error('Log flush failed', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.terminate' => ['onTerminate', -100],
            'kernel.response' => ['onResponse', -100],
        ];
    }
}