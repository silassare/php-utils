<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Exceptions;

use PHPUtils\Interfaces\RichExceptionInterface;
use PHPUtils\Traits\RichExceptionTrait;

/**
 * Class RuntimeException.
 */
class RuntimeException extends \RuntimeException implements RichExceptionInterface
{
	use RichExceptionTrait;
}
