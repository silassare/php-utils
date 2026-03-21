<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Interfaces;

use ArrayAccess;
use JsonSerializable;
use PHPUtils\Traits\ArrayCapableTrait;

/**
 * Interface ArrayCapableInterface.
 *
 * Objects implementing this interface can be converted to arrays and serialized to JSON.
 * The {@see ArrayCapableTrait} provides a default implementation of this interface.
 */
interface ArrayCapableInterface extends JsonSerializable
{
	/**
	 * Returns array representation of the current object.
	 *
	 * @return array|ArrayAccess
	 */
	public function toArray(): array|ArrayAccess;
}
