<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Store;

use ArrayAccess;
use PHPUtils\Store\Traits\StoreEditableTrait;
use PHPUtils\Store\Traits\StoreTrait;

/**
 * Class Store.
 */
class Store implements ArrayAccess
{
	use StoreEditableTrait;
	use StoreTrait;

	/**
	 * StoreEditable constructor.
	 *
	 * @param array|object $data
	 */
	public function __construct(array|object $data)
	{
		$this->data_access = new DataAccess($data, true);
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetExists($offset): bool
	{
		return $this->has((string) $offset);
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetGet($offset): mixed
	{
		return $this->get((string) $offset);
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetSet($offset, $value): void
	{
		$this->set((string) $offset, $value);
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetUnset($offset): void
	{
		$this->remove((string) $offset);
	}
}
