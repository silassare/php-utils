<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Interfaces;

use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Store\Map;
use PHPUtils\Traits\MetaCapableTrait;

/**
 * Interface MetaCapableInterface.
 *
 * Implemented by objects that carry a {@see Map} of arbitrary metadata.
 * Use {@see MetaCapableTrait} for the default implementation.
 */
interface MetaCapableInterface
{
	/**
	 * Returns the metadata map for this instance.
	 *
	 * Implementations should lazy-create the map on first call and return
	 * the same instance on every subsequent call.
	 */
	public function getMeta(): Map;

	/**
	 * Merges the given metadata into the existing metadata.
	 *
	 * @param array|Map $meta Metadata to merge
	 *
	 * @return static
	 *
	 * @throws RuntimeException if the instance is lockable and currently locked
	 */
	public function mergeMeta(array|Map $meta): static;

	/**
	 * Sets a single metadata key to a given value.
	 *
	 * @param string $key   Metadata key
	 * @param mixed  $value Metadata value
	 *
	 * @return static
	 *
	 * @throws RuntimeException if the instance is lockable and currently locked
	 */
	public function setMetaKey(string $key, mixed $value = null): static;
}
