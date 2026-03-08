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
use PHPUtils\DotPath;
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Interfaces\ArrayCapableInterface;
use PHPUtils\Store\Traits\StoreTrait;

/**
 * Editable key-value store with dot/bracket-notation path access.
 *
 * Wraps any array or object. Keys are parsed via {@see DotPath}:
 *   - Plain segments:     `foo.bar`
 *   - Bracket-integer:    `foo[0]`
 *   - Bracket-quoted:     `foo['my.key']`
 *
 * Intermediate missing segments are auto-created as empty arrays on set().
 * Use {@see StoreNotEditable} for a read-only view of the same data.
 *
 * @template T of array|object
 *
 * @implements ArrayAccess<int|string, mixed>
 * @implements IteratorAggregate<int|string, mixed>
 */
class Store implements ArrayAccess, IteratorAggregate, ArrayCapableInterface
{
	use StoreTrait;

	/**
	 * @var DataAccess<T>
	 */
	protected DataAccess $data_access;

	/**
	 * Store constructor.
	 *
	 * @param T $data the array or object to wrap
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
	public function getData(): mixed
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
	 * Sets a value at the given path.
	 *
	 * Intermediate segments that do not exist are auto-created as empty arrays.
	 *
	 * @param string $key   DotPath expression e.g. 'foo.bar', 'items[0]', "map['k']"
	 * @param mixed  $value
	 *
	 * @return static
	 */
	public function set(string $key, mixed $value): static
	{
		$parts   = DotPath::parse($key)->getSegments();
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
	 * Removes the value at the given path.
	 *
	 * Silently no-ops for non-existent paths. Class constants and certain
	 * object properties cannot be unset (PHP limitation).
	 *
	 * @param string $key DotPath expression
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
