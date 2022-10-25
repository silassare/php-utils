<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Store\Traits;

use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Store\DataAccess;

/**
 * Trait StoreEditableTrait.
 */
trait StoreEditableTrait
{
	/**
	 * Gets the store data.
	 *
	 * @return array|object
	 */
	public function getData(): object|array
	{
		return $this->data_access->getData();
	}

	/**
	 * Sets the store data.
	 *
	 * @param array|object $data
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
}
