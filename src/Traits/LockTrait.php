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

/**
 * Trait LockTrait.
 *
 * Provides a basic locking mechanism for classes implementing LockInterface.
 */
trait LockTrait
{
	protected bool $locked = false;

	/**
	 * {@inheritDoc}
	 */
	public function lock(): static
	{
		$this->locked = true;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isLocked(): bool
	{
		return $this->locked;
	}

	/**
	 * {@inheritDoc}
	 */
	public function assertNotLocked(): void
	{
		if ($this->locked) {
			throw new RuntimeException(
				\sprintf('Locked "%s" instance cannot be modified.', static::class)
			);
		}
	}
}
