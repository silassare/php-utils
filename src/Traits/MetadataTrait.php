<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Traits;

use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Lock\Interfaces\LockableInterface;
use PHPUtils\Store\Map;

/**
 * Trait MetadataTrait.
 */
trait MetadataTrait
{
	protected ?Map $meta = null;

	/**
	 * Gets the metadata.
	 *
	 * @return Map
	 */
	public function getMeta(): Map
	{
		if (null === $this->meta) {
			$this->meta = new Map();
		}

		return $this->meta;
	}

	/**
	 * Merges the given metadata into the existing metadata.
	 *
	 * If the object is lockable and currently locked, a RuntimeException will be thrown.
	 *
	 * @param array|Map $meta Metadata to merge
	 *
	 * @return static
	 *
	 * @throws RuntimeException If the object is lockable and currently locked
	 */
	public function mergeMeta(array|Map $meta): static
	{
		if ($this instanceof LockableInterface) {
			$this->assertNotLocked();
		}

		$this->getMeta()->merge($meta);

		return $this;
	}

	/**
	 * Sets a metadata key to a given value.
	 *
	 * If the object is lockable and currently locked, a RuntimeException will be thrown.
	 *
	 * @param string $key   Metadata key
	 * @param mixed  $value Metadata value
	 *
	 * @return static
	 *
	 * @throws RuntimeException If the object is lockable and currently locked
	 */
	public function setMetaKey(string $key, mixed $value = null): static
	{
		if ($this instanceof LockableInterface) {
			$this->assertNotLocked();
		}

		$this->getMeta()->set($key, $value);

		return $this;
	}
}
