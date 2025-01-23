<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Psr\SimpleCache\InvalidArgumentException;

class InvalidKeyException extends \InvalidArgumentException implements InvalidArgumentException
{
}
