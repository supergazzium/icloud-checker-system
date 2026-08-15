<?php

declare(strict_types=1);

namespace App\Services\IFreeiCloud;

use RuntimeException;
use Throwable;

/**
 * Thrown by IFreeiCloudClient when the provider rejects a call. Carries
 * the provider's `error` string (if present) and the raw HTTP status so
 * callers can distinguish transport failures (0/5xx) from API errors
 * (200 + `success:false`).
 */
class IFreeiCloudException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpCode = 0,
        public readonly ?string $providerError = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
