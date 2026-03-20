<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Lock;

use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Lock\Interfaces\LockableInterface;
use PHPUtils\Lock\Interfaces\LockInterface;
use PHPUtils\Lock\Interfaces\ReleasableLockInterface;

/**
 * Trait LockableTrait.
 *
 * Default implementation of {@see LockableInterface}.
 *
 * The lock is lazy-created on the first call to {@see getLock()} via the
 * protected factory method {@see createLock()}.
 * Override {@see createLock()} to inject a custom {@see LockInterface}
 * implementation (e.g. a shared lock, a conditional lock, etc.).
 *
 * Example — inject a shared lock:
 *
 * ```php
 * class MyEntity implements LockableInterface
 * {
 *     use LockableTrait;
 *
 *     public function __construct(private readonly LockInterface $shared_lock) {}
 *
 *     protected function createLock(): LockInterface
 *     {
 *         return $this->shared_lock;
 *     }
 * }
 * ```
 */
trait LockableTrait
{
	private ?LockInterface $lock_instance = null;

	/**
	 * {@inheritDoc}
	 */
	public function getLock(): LockInterface
	{
		if (null === $this->lock_instance) {
			$this->lock_instance = $this->createLock();
		}

		return $this->lock_instance;
	}

	/**
	 * {@inheritDoc}
	 */
	public function lock(): static
	{
		$this->getLock()->acquire();

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isLocked(): bool
	{
		return $this->getLock()->isAcquired();
	}

	/**
	 * {@inheritDoc}
	 */
	public function assertNotLocked(): void
	{
		if ($this->isLocked()) {
			throw new RuntimeException(
				\sprintf('Locked "%s" instance cannot be modified.', static::class)
			);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function unlock(): static
	{
		$lock = $this->getLock();

		if (!$lock instanceof ReleasableLockInterface) {
			throw new RuntimeException(
				\sprintf('Lock of "%s" is permanent and cannot be released.', static::class)
			);
		}

		$lock->release();

		return $this;
	}

	/**
	 * Creates the {@see LockInterface} instance for this lockable entity.
	 *
	 * Override this method to return a custom lock implementation.
	 * The return value is cached — this method is called at most once per instance.
	 */
	protected function createLock(): LockInterface
	{
		return new Lock();
	}
}
