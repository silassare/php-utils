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
 * Interface ReleasableLockInterface.
 *
 * Extends {@see LockInterface} with the ability to release the lock.
 * Implementations allow the lock to be acquired and released multiple times.
 *
 * For an irreversible lock, use {@see PermanentLock} or any plain
 * {@see LockInterface} implementation that does not implement this interface.
 */
interface ReleasableLockInterface extends LockInterface
{
	/**
	 * Releases the lock.
	 *
	 * Idempotent — calling this on an already-released lock has no effect.
	 *
	 * @return static
	 */
	public function release(): static;
}
