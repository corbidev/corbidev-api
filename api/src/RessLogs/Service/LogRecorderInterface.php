<?php

namespace App\RessLogs\Service;

use App\RessLogs\Dto\CreateLogRequestDto;
use App\RessLogs\Entity\LogEntry;

interface LogRecorderInterface
{
    public function record(CreateLogRequestDto $request): LogEntry;
}
