<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Lock\Traits;

use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Lock\Interfaces\LockableInterface;
use PHPUtils\Lock\Interfaces\LockInterface;
use PHPUtils\Lock\Interfaces\ReleasableLockInterface;
use PHPUtils\Lock\Lock;

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
	 * Whether {@see self::createLock()} is running: it is an extension point, so what it does is a
	 * consumer's code, and a `null` memo cannot say "being built".
	 */
	private bool $lock_building = false;

	public function getLock(): LockInterface
	{
		if (null === $this->lock_instance) {
			// An override that reaches back here -- directly, or through a parent whose chain the
			// consumer made cyclic -- used to find the memo still null and build again, for ever.
			if ($this->lock_building) {
				throw new RuntimeException(\sprintf(
					'The lock of "%s" was asked for while it was being created: createLock() must not'
						. ' call getLock().',
					static::class
				));
			}

			$this->lock_building = true;

			try {
				$this->lock_instance = $this->createLock();
			} finally {
				$this->lock_building = false;
			}
		}

		return $this->lock_instance;
	}

	public function lock(): static
	{
		$this->getLock()->acquire();

		return $this;
	}

	public function isLocked(): bool
	{
		return $this->getLock()->isAcquired();
	}

	public function assertNotLocked(): void
	{
		if ($this->isLocked()) {
			throw new RuntimeException(
				\sprintf('Locked "%s" instance cannot be modified.', static::class)
			);
		}
	}

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
