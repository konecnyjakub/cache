<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Psr\Cache\InvalidArgumentException;

/**
 * Exception thrown if directory does not exist or is not writable
 */
class InvalidDirectoryException extends \InvalidArgumentException implements InvalidArgumentException
{
}
