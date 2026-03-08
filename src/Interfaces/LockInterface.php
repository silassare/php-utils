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

/**
 * Interface LockInterface.
 */
interface LockInterface
{
	/**
	 * Locks this instance to prevent further changes.
	 *
	 * @return $this
	 */
	public function lock(): static;

	/**
	 * Checks if this instance is locked.
	 */
	public function isLocked(): bool;

	/**
	 * Asserts that this instance is not locked.
	 *
	 * @throws RuntimeException when already locked
	 */
	public function assertNotLocked(): void;
}
