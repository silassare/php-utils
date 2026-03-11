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
use PHPUtils\Interfaces\ArrayCapableInterface;
use PHPUtils\Store\Traits\StoreTrait;

/**
 * Read-only view over an array or object.
 *
 * All write attempts (set, remove, offsetSet, offsetUnset) throw RuntimeException.
 * Use {@see Store} for editable access.
 *
 * @template T of array|object
 *
 * @implements ArrayAccess<int|string, mixed>
 * @implements IteratorAggregate<int|string, mixed>
 */
class StoreNotEditable implements ArrayAccess, IteratorAggregate, ArrayCapableInterface
{
	use StoreTrait;

	/**
	 * @var DataAccess<T>
	 */
	protected DataAccess $data_access;

	/**
	 * StoreNotEditable constructor.
	 *
	 * @param T $data the array or object to wrap (read-only, writes throw)
	 */
	public function __construct(array|object $data)
	{
		$this->data_access = new DataAccess($data, false);
	}

	/**
	 * Magic setter.
	 */
	public function __set(string $key, mixed $_): void
	{
		throw new RuntimeException(\sprintf('Not editable store, can\'t set key: %s', $key));
	}

	/**
	 * Gets the store data.
	 *
	 * @return T
	 */
	public function getData(): mixed
	{
		return $this->data_access->getData();
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
		throw new RuntimeException(\sprintf('Not editable store, can\'t set offset: %s', $offset ?? 'null'));
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetUnset($offset): void
	{
		throw new RuntimeException(\sprintf('Not editable store, can\'t unset offset: %s', (string) $offset));
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator(): ArrayIterator
	{
		return $this->data_access->getIterator();
	}
}
