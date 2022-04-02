<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUtils\Tests\Store;

/**
 * Trait ArrayAccessTrait.
 *
 * @internal
 */
trait ArrayAccessTrait
{
	/**
	 * {@inheritDoc}
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->data[$offset]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetGet($offset): mixed
	{
		return $this->data[$offset];
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetSet($offset, $value): void
	{
		$this->data[$offset] = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetUnset($offset): void
	{
		unset($this->data[$offset]);
	}
}
