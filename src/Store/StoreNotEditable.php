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
use ArrayIterator;
use IteratorAggregate;
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Store\Traits\StoreTrait;

/**
 * Class StoreNotEditable.
 */
class StoreNotEditable implements ArrayAccess, IteratorAggregate
{
	use StoreTrait;

	protected DataAccess $data_access;

	/**
	 * StoreNotEditable constructor.
	 *
	 * @param array|object $data
	 */
	public function __construct(array|object $data)
	{
		$this->data_access = new DataAccess($data, false);
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
		throw new RuntimeException(\sprintf('Not editable store, can\'t set offset: %s', $offset));
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetUnset($offset): void
	{
		throw new RuntimeException(\sprintf('Not editable store, can\'t unset offset: %s', $offset));
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator(): ArrayIterator
	{
		return $this->data_access->getIterator();
	}
}
