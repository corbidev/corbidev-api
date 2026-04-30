<?php

namespace App\Shared\Logging\Infrastructure\Monolog;

use Monolog\Formatter\JsonFormatter;

class UtcJsonFormatter extends JsonFormatter
{
    public function __construct()
    {
        parent::__construct();

        // 🔥 format ISO8601 UTC strict
        $this->dateFormat = 'Y-m-d\TH:i:s.u\Z';
    }

    protected function formatDate(\DateTimeInterface $date): string
    {
        if (!$date instanceof \DateTimeImmutable) {
            $date = \DateTimeImmutable::createFromInterface($date);
        }

        return $date
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format($this->dateFormat);
    }
}