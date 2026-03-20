<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Lock\Interfaces;

use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Lock\LockableTrait;

/**
 * Interface LockableInterface.
 *
 * A lockable entity holds a {@see LockInterface} and exposes convenience
 * methods to lock itself and guard mutation points.
 *
 * The separation between the lock token ({@see LockInterface}) and the
 * lockable entity allows injecting or swapping lock implementations without
 * changing the entity class (see {@see LockableTrait::createLock()}).
 */
interface LockableInterface
{
	/**
	 * Returns the lock associated with this instance.
	 *
	 * Implementations should lazy-create the lock on first call and return
	 * the same instance on every subsequent call.
	 */
	public function getLock(): LockInterface;

	/**
	 * Locks this instance by acquiring the underlying {@see LockInterface}.
	 *
	 * @return static
	 */
	public function lock(): static;

	/**
	 * Checks whether this instance is locked.
	 */
	public function isLocked(): bool;

	/**
	 * Asserts that this instance is not locked.
	 *
	 * @throws RuntimeException when this instance is already locked
	 */
	public function assertNotLocked(): void;

	/**
	 * Unlocks this instance by releasing the underlying lock.
	 *
	 * @return static
	 *
	 * @throws RuntimeException when the underlying lock does not implement {@see ReleasableLockInterface}
	 */
	public function unlock(): static;
}
