<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use RuntimeException;

/**
 * Thrown from inside CheckController::processCheck() when the locked user
 * row shows a balance below the service price. Caught by store() and
 * converted to a friendly flash message; the DB tx rolls back automatically.
 */
class InsufficientCreditException extends RuntimeException
{
}
