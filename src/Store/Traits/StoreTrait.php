<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Store\Traits;

use PHPUtils\DotPath;
use PHPUtils\Store\DataAccess;
use PHPUtils\Traits\ArrayCapableTrait;

/**
 * Trait StoreTrait.
 */
trait StoreTrait
{
	use ArrayCapableTrait;

	/**
	 * StoreTrait destructor.
	 */
	public function __destruct()
	{
		unset($this->data_access);
	}

	/**
	 * Magic getter.
	 */
	public function __get(string $key): mixed
	{
		return $this->get($key);
	}

	/**
	 * Magic setter.
	 */
	public function __set(string $key, mixed $value): void
	{
		$this->set($key, $value);
	}

	/**
	 * Magic isset.
	 */
	public function __isset(string $key): bool
	{
		return $this->has($key);
	}

	/**
	 * Checks if a given key is in the data store.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has(string $key): bool
	{
		if ($parent = $this->parentOf($key, $access_key)) {
			return $parent->has($access_key);
		}

		return false;
	}

	/**
	 * Gets the given key value.
	 *
	 * @param string     $key
	 * @param null|mixed $default
	 *
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		$parent = $this->parentOf($key, $access_key);

		if (null !== $parent && null !== $access_key) {
			return $parent->get($access_key, $default);
		}

		return $default;
	}

	/**
	 * Resolves the parent DataAccess for a given path and sets $access_key to the final segment.
	 *
	 * Given 'foo.bar.baz', traverses 'foo' -> 'bar' via DataAccess::next() and returns the
	 * DataAccess holding 'bar', with $access_key = 'baz'.
	 * For single-segment keys, returns the root DataAccess unchanged.
	 * Returns null if any intermediate segment is missing or not traversable.
	 *
	 * @param null|string $key
	 * @param null|string &$access_key set to the last path segment on success
	 *
	 * @return null|DataAccess
	 */
	public function parentOf(?string $key, ?string &$access_key = null): ?DataAccess
	{
		$parts      = \is_string($key) ? DotPath::parse($key)->getSegments() : [$key];
		$access_key = $key;
		$counter    = \count($parts);
		$parent     = $this->data_access;

		if ($counter > 1) {
			foreach ($parts as $k) {
				--$counter;

				if ($counter) {
					$parent = $parent->next($k);

					if (null === $parent) {
						return null;
					}
				} else {
					$access_key = $k;
				}
			}
		}

		return $parent;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return (array) $this->getData();
	}
}
