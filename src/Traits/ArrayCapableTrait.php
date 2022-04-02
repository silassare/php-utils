<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Traits;

/**
 * Trait ArrayCapableTrait.
 */
trait ArrayCapableTrait
{
	/**
	 * Returns array representation of the current object.
	 *
	 * @return array
	 */
	abstract public function toArray(): array;

	/**
	 * Specify data which should be serialized to JSON.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
