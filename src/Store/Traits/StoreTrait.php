<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Store\Traits;

use PHPUtils\Store\DataAccess;

/**
 * Trait StoreTrait.
 */
trait StoreTrait
{
	/**
	 * StoreTrait destructor.
	 */
	public function __destruct()
	{
		unset($this->data_access);
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
	 * Returns parent of a given key.
	 *
	 * @param null|string $key
	 * @param null|string &$access_key
	 *
	 * @return null|\PHPUtils\Store\DataAccess
	 */
	public function parentOf(?string $key, ?string &$access_key = null): null|DataAccess
	{
		$parts      = \is_string($key) ? \explode('.', $key) : [$key];
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
}
