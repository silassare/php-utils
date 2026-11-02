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
use Override;
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
 *
 * The properties it answers to are the keys of the data it holds, read and written through `__get`
 * and `__set`, so they are not a list anything can seal.
 *
 * @psalm-no-seal-properties
 */
class Store implements ArrayAccess, IteratorAggregate, ArrayCapableInterface
{
	use StoreTrait;

	/**
	 * How deep {@see self::merge()} follows nested data: {@see self::deepMerge()}.
	 */
	public const int MAX_MERGE_DEPTH = 64;

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
	 * @return static enables fluent chaining; returns $this even when the write is a no-op
	 */
	public function setData(array|object $data): static
	{
		$this->data_access = new DataAccess($data, true);

		return $this;
	}

	/**
	 * Deep-merges data into this store.
	 *
	 * For each key: if both the existing value and the incoming value are
	 * array/object, they are merged recursively. Otherwise the incoming value
	 * replaces the existing one (type mismatch or leaf value).
	 *
	 * @return static enables fluent chaining; returns $this even when the write is a no-op
	 */
	public function merge(iterable $data): static
	{
		foreach ($data as $key => $value) {
			$key      = (string) $key;
			$existing = $this->get($key);

			if (DataAccess::typeOk($existing) && \is_iterable($value)) {
				/** @var array|object $existing */
				$this->set($key, self::deepMerge($existing, $value));
			} else {
				$this->set($key, $value);
			}
		}

		return $this;
	}

	/**
	 * Sets a value at the given path.
	 *
	 * Intermediate segments that do not exist are auto-created as empty arrays.
	 *
	 * @param string $key DotPath expression e.g. 'foo.bar', 'items[0]', "map['k']"
	 *
	 * @return static enables fluent chaining; returns $this even when the write is a no-op
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
	 * @return static enables fluent chaining; returns $this even when the write is a no-op
	 */
	public function remove(string $key): static
	{
		if (($parent = $this->parentOf($key, $access_key)) && null !== $access_key) {
			$parent->remove($access_key);
		}

		return $this;
	}

	#[Override]
	public function offsetExists($offset): bool
	{
		return $this->has((string) $offset);
	}

	#[Override]
	public function offsetGet($offset): mixed
	{
		return $this->get((string) $offset);
	}

	#[Override]
	public function offsetSet($offset, $value): void
	{
		$this->set((string) $offset, $value);
	}

	#[Override]
	public function offsetUnset($offset): void
	{
		$this->remove((string) $offset);
	}

	#[Override]
	public function getIterator(): ArrayIterator
	{
		return $this->data_access->getIterator();
	}

	/**
	 * Recursively merges $source into a copy of $target and returns the result.
	 *
	 * Bounded: an array cannot hold itself by value, but a `Traversable` can yield itself and a
	 * `Store` is one, so the depth of this walk is the caller's data and nothing else. A limit that
	 * says which key it stopped at is worth more than a stack exhausted with no message.
	 */
	private static function deepMerge(array|object $target, iterable $source, int $depth = 0): array|object
	{
		if ($depth >= self::MAX_MERGE_DEPTH) {
			throw new RuntimeException(\sprintf(
				'Merge is nested deeper than %d levels: the data merged in holds itself, or is deeper'
					. ' than this store is meant to carry.',
				self::MAX_MERGE_DEPTH
			));
		}

		$store = new self($target);

		foreach ($source as $key => $value) {
			$key      = (string) $key;
			$existing = $store->get($key);

			if (DataAccess::typeOk($existing) && \is_iterable($value)) {
				/** @var array|object $existing */
				$store->set($key, self::deepMerge($existing, $value, $depth + 1));
			} else {
				$store->set($key, $value);
			}
		}

		return $store->getData();
	}
}
