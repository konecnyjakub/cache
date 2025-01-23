<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Exception thrown if key is not a legal value
 */
class InvalidKeyException extends \InvalidArgumentException implements InvalidArgumentException
{
}
