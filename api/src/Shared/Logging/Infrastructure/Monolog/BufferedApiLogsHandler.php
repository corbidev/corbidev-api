<?php

namespace App\Shared\Logging\Infrastructure\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use App\Shared\Logging\Infrastructure\Context\LogContextProvider;

class BufferedApiLogsHandler extends AbstractProcessingHandler
{
    private array $buffer = [];
    private int $limit = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $fallbackLogger,
        private LogContextProvider $contextProvider,
        private string $endpoint,
        private string $appVersion = '1.0.0',
        private string $client = 'web'
    ) {
        parent::__construct();
    }

    protected function write(LogRecord $record): void
    {
        $log = $this->normalize($record);

        // Anti-récursion : ne pas loguer les appels vers l'endpoint /api/logs lui-même
        if (isset($log['uri']) && str_contains((string) $log['uri'], '/api/logs')) {
            return;
        }

        $log = $this->validate($log);

        $this->buffer[] = $log;

        if (count($this->buffer) >= $this->limit) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        try {
            $response = $this->httpClient->request('POST', $this->endpoint, [
                'json' => ['logs' => $this->buffer],
                'timeout' => 1.0,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                $this->fallbackLogger->error('API_LOGS error', [
                    'statusCode' => $statusCode,
                    'logs' => $this->buffer,
                ]);
            }
        } catch (\Throwable $e) {
            $this->fallbackLogger->error('API_LOGS unreachable', [
                'exception' => $e->getMessage(),
                'logs' => $this->buffer,
            ]);
        }

        $this->buffer = [];
    }

    public function close(): void
    {
        $this->flush();
    }

    private function normalize(LogRecord $record): array
    {
        $system = $this->contextProvider->getSystemContext();
        $context = $record->context;

        return [
            'externalId' => Uuid::v4()->toRfc4122(),

            // 🌐 CONTEXTE HTTP
            'domain' => $system['domain'],
            'uri' => $system['uri'],
            'method' => $system['method'],
            'ip' => $system['ip'],

            // 🧾 LOG
            'message' => $record->message,
            'level' => strtoupper($record->level->name),
            'env' => $_SERVER['APP_ENV'] ?? 'dev',

            // 📦 META
            'client' => $context['client'] ?? $this->client,
            'version' => $_ENV['APP_VERSION'] ?? $this->appVersion,

            // 🧠 DONNÉES MÉTIER
            'userId' => $context['userId'] ?? null,
            'httpStatus' => $context['httpStatus'] ?? null,
            'requestId' => $context['request_id'] ?? null,

            // 🔍 FINGERPRINT
            'fingerprint' => $this->generateFingerprint($record, $system),

            // 📚 CONTEXT NETTOYÉ
            'context' => $this->cleanContext($context),
        ];
    }

    private function cleanContext(array $context): array
    {
        unset(
            $context['userId'],
            $context['httpStatus'],
            $context['client'],
            $context['request_id'],
            $context['password'],
            $context['token']
        );

        return $context;
    }

    private function validate(array $log): array
    {
        $required = ['externalId', 'message', 'level'];

        foreach ($required as $field) {
            if (empty($log[$field])) {
                $log[$field] = 'unknown';
            }
        }

        return $log;
    }

    private function generateFingerprint(LogRecord $record, array $system): string
    {
        return sha1(
            $record->message .
            ($system['uri'] ?? '') .
            $record->level->name
        );
    }
}
