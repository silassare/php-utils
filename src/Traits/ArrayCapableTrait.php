<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Traits;

use ArrayAccess;
use ArrayObject;

/**
 * Trait ArrayCapableTrait.
 */
trait ArrayCapableTrait
{
	/**
	 * When empty array is serialized to JSON, should it be an object?
	 *
	 * O'Zone API relies on this behavior
	 * when empty we would like JSON to be: "{}" not "[]".
	 *
	 * @var bool
	 */
	protected bool $json_empty_array_is_object = false;

	/**
	 * Returns array representation of the current object.
	 *
	 * @return array|ArrayAccess
	 */
	abstract public function toArray(): array|ArrayAccess;

	/**
	 * Specify data which should be serialized to JSON.
	 *
	 * @return array|ArrayAccess
	 */
	public function jsonSerialize(): array|ArrayAccess
	{
		$result = $this->toArray();

		if ($this->json_empty_array_is_object && empty($result)) {
			return new ArrayObject();
		}

		return $result;
	}
}
