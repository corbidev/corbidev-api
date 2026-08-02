<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Exception extends RuntimeException
{
    private readonly string $errorCode;
    private readonly ?int $statusCode;
    /**
     * @var list<string|int|float>
     */
    private readonly array $messageArgs;

    public function __construct(
        string $errorCode,
        ?int $statusCode = null,
        ?Throwable $previous = null,
        array $messageArgs = [],
    ) {
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->messageArgs = $messageArgs;

        parent::__construct($errorCode, 0, $previous);
    }

    public static function client(string $errorCode, ?Throwable $previous = null, array $messageArgs = []): self
    {
        return new self($errorCode, Response::HTTP_BAD_REQUEST, $previous, $messageArgs);
    }

    public static function server(string $errorCode, ?Throwable $previous = null, array $messageArgs = []): self
    {
        return new self($errorCode, Response::HTTP_INTERNAL_SERVER_ERROR, $previous, $messageArgs);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return list<string|int|float>
     */
    public function getMessageArgs(): array
    {
        return $this->messageArgs;
    }
}