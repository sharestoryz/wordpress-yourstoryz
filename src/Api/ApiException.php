<?php

declare(strict_types=1);

namespace YourStoryz\Api;

use RuntimeException;
use Throwable;

final class ApiException extends RuntimeException
{
    public const KIND_UNAUTHORIZED = 'unauthorized';

    public const KIND_UNREACHABLE = 'unreachable';

    public const KIND_HTTP = 'http';

    public function __construct(
        public readonly string $kind,
        public readonly int $status = 0,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function userMessage(): string
    {
        return match ($this->kind) {
            self::KIND_UNAUTHORIZED => 'Token ongeldig',
            self::KIND_UNREACHABLE => 'YourStoryz niet bereikbaar',
            default => 'Fout '.$this->status,
        };
    }
}
