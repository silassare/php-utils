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
 * Class Store.
 *
 * @template T of array|object
 */
class Store implements ArrayAccess, IteratorAggregate
{
	use StoreTrait;

	/**
	 * @var \PHPUtils\Store\DataAccess<T>
	 */
	protected DataAccess $data_access;

	/**
	 * StoreEditable constructor.
	 *
	 * @param T $data
	 */
	public function __construct(array|object $data)
	{
		$this->data_access = new DataAccess($data, true);
	}

	/**
	 * Gets the store data.
	 *
	 * @return T
	 */
	public function getData()
	{
		return $this->data_access->getData();
	}

	/**
	 * Sets the store data.
	 *
	 * @param T $data
	 *
	 * @return static
	 */
	public function setData(array|object $data): static
	{
		$this->data_access = new DataAccess($data, true);

		return $this;
	}

	/**
	 * Merge data to this store.
	 *
	 * @param iterable $data
	 *
	 * @return static
	 */
	public function merge(iterable $data): static
	{
		foreach ($data as $key => $value) {
			$this->set($key, $value);
		}

		return $this;
	}

	/**
	 * Sets value for a given key.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return static
	 */
	public function set(string $key, mixed $value): static
	{
		$parts   = \explode('.', $key);
		$counter = \count($parts);
		$next    = $this->data_access;

		foreach ($parts as $part) {
			--$counter;

			if ($counter) {
				$t = $next->next($part);

				if (null === $t) {
					$t = $next->set($part, [])
						->next($part);

					if (null === $t) {
						throw new RuntimeException('Unable to set property for nesting.', [
							'key'      => $key,
							'property' => $part,
							'target'   => $next->getData(),
						]);
					}
				}

				$next = $t;
			} else {
				$next->set($part, $value);
			}
		}

		return $this;
	}

	/**
	 * Removes a given key value from the store.
	 *
	 * In some conditions this will fails because of the way unset work:
	 * see: https://www.php.net/manual/en/function.unset.php
	 * can't remove class/object constants etc.
	 *
	 * @param string $key
	 *
	 * @return static
	 */
	public function remove(string $key): static
	{
		if (($parent = $this->parentOf($key, $access_key)) && null !== $access_key) {
			$parent->remove($access_key);
		}

		return $this;
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

	/**
	 * {@inheritDoc}
	 */
	public function getIterator(): ArrayIterator
	{
		return $this->data_access->getIterator();
	}
}
