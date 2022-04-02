<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Interfaces;

use JsonSerializable;

/**
 * Interface ArrayCapableInterface.
 */
interface ArrayCapableInterface extends JsonSerializable
{
	/**
	 * Returns array representation of the current object.
	 *
	 * @return array
	 */
	public function toArray(): array;
}
