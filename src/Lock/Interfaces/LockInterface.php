<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Lock\Interfaces;

use PHPUtils\Lock\PermanentLock;

/**
 * Interface LockInterface.
 *
 * Represents an acquirable lock token.
 * Implementations may or may not support releasing the lock.
 * For a releasable lock, see {@see ReleasableLockInterface}.
 * For an irreversible lock, see {@see PermanentLock}.
 */
interface LockInterface
{
	/**
	 * Acquires the lock.
	 *
	 * Idempotent — calling this multiple times has the same effect as calling it once.
	 *
	 * @return static
	 */
	public function acquire(): static;

	/**
	 * Checks whether the lock has been acquired.
	 */
	public function isAcquired(): bool;
}
