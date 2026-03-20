<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Lock;

use PHPUnit\Framework\TestCase;
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Lock\Interfaces\LockableInterface;
use PHPUtils\Lock\Interfaces\LockInterface;
use PHPUtils\Lock\Interfaces\ReleasableLockInterface;
use PHPUtils\Lock\Lock;
use PHPUtils\Lock\PermanentLock;
use PHPUtils\Lock\Traits\LockableTrait;

/**
 * Class LockableTraitTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class LockableTraitTest extends TestCase
{
	private LockableInterface $subject;

	protected function setUp(): void
	{
		$this->subject = new class implements LockableInterface {
			use LockableTrait;
		};
	}

	public function testGetLockReturnsLockInterface(): void
	{
		self::assertInstanceOf(LockInterface::class, $this->subject->getLock());
	}

	public function testGetLockDefaultIsLockInstance(): void
	{
		self::assertInstanceOf(Lock::class, $this->subject->getLock());
	}

	public function testGetLockDefaultIsReleasable(): void
	{
		self::assertInstanceOf(ReleasableLockInterface::class, $this->subject->getLock());
	}

	public function testGetLockReturnsSameInstance(): void
	{
		self::assertSame($this->subject->getLock(), $this->subject->getLock());
	}

	public function testNotLockedByDefault(): void
	{
		self::assertFalse($this->subject->isLocked());
	}

	public function testLock(): void
	{
		$result = $this->subject->lock();

		self::assertTrue($this->subject->isLocked());
		self::assertSame($this->subject, $result);
	}

	public function testLockIsIdempotent(): void
	{
		$this->subject->lock();
		$this->subject->lock(); // second call is a no-op

		self::assertTrue($this->subject->isLocked());
	}

	public function testAssertNotLockedPassesWhenUnlocked(): void
	{
		$this->subject->assertNotLocked();
		$this->addToAssertionCount(1);
	}

	public function testUnlock(): void
	{
		$this->subject->lock();
		$result = $this->subject->unlock();

		self::assertFalse($this->subject->isLocked());
		self::assertSame($this->subject, $result);
	}

	public function testUnlockWhenNotLocked(): void
	{
		$result = $this->subject->unlock(); // idempotent — no-op when not locked

		self::assertFalse($this->subject->isLocked());
		self::assertSame($this->subject, $result);
	}

	public function testUnlockThrowsWhenPermanentLock(): void
	{
		$subject = new class implements LockableInterface {
			use LockableTrait;

			protected function createLock(): LockInterface
			{
				return new PermanentLock();
			}
		};

		$subject->lock();

		assertException(
			new RuntimeException(\sprintf('Lock of "%s" is permanent and cannot be released.', $subject::class)),
			static fn () => $subject->unlock()
		);
	}

	public function testAssertNotLockedThrowsWhenLocked(): void
	{
		$this->subject->lock();

		assertException(
			new RuntimeException(\sprintf('Locked "%s" instance cannot be modified.', $this->subject::class)),
			fn () => $this->subject->assertNotLocked()
		);
	}

	public function testIsLockedReflectsDirectLockAcquire(): void
	{
		$this->subject->getLock()->acquire();

		self::assertTrue($this->subject->isLocked());
	}

	public function testCreateLockCanBeOverridden(): void
	{
		$custom_lock = new Lock();

		$subject = new class($custom_lock) implements LockableInterface {
			use LockableTrait;

			public function __construct(private readonly LockInterface $injected) {}

			protected function createLock(): LockInterface
			{
				return $this->injected;
			}
		};

		self::assertSame($custom_lock, $subject->getLock());

		$custom_lock->acquire();

		self::assertTrue($subject->isLocked());
	}

	public function testSharedLockAcrossInstances(): void
	{
		$shared_lock = new Lock();

		$a = new class($shared_lock) implements LockableInterface {
			use LockableTrait;

			public function __construct(private readonly LockInterface $shared) {}

			protected function createLock(): LockInterface
			{
				return $this->shared;
			}
		};

		$b = new class($shared_lock) implements LockableInterface {
			use LockableTrait;

			public function __construct(private readonly LockInterface $shared) {}

			protected function createLock(): LockInterface
			{
				return $this->shared;
			}
		};

		self::assertFalse($a->isLocked());
		self::assertFalse($b->isLocked());

		$a->lock();

		self::assertTrue($a->isLocked());
		self::assertTrue($b->isLocked());
	}
}
