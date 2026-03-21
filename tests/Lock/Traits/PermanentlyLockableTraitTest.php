<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Lock\Traits;

use PHPUnit\Framework\TestCase;
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Lock\Interfaces\LockableInterface;
use PHPUtils\Lock\Interfaces\LockInterface;
use PHPUtils\Lock\Interfaces\ReleasableLockInterface;
use PHPUtils\Lock\PermanentLock;
use PHPUtils\Lock\Traits\PermanentlyLockableTrait;

/**
 * Class PermanentlyLockableTraitTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class PermanentlyLockableTraitTest extends TestCase
{
	private LockableInterface $subject;

	protected function setUp(): void
	{
		$this->subject = new class implements LockableInterface {
			use PermanentlyLockableTrait;
		};
	}

	public function testGetLockReturnsPermanentLock(): void
	{
		self::assertInstanceOf(PermanentLock::class, $this->subject->getLock());
	}

	public function testGetLockIsNotReleasable(): void
	{
		self::assertNotInstanceOf(ReleasableLockInterface::class, $this->subject->getLock());
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
		$this->subject->lock();

		self::assertTrue($this->subject->isLocked());
	}

	public function testLockIsIrreversible(): void
	{
		$this->subject->lock();

		assertException(
			new RuntimeException(\sprintf('Lock of "%s" is permanent and cannot be released.', $this->subject::class)),
			fn () => $this->subject->unlock()
		);

		self::assertTrue($this->subject->isLocked());
	}

	public function testAssertNotLockedPassesWhenUnlocked(): void
	{
		$this->subject->assertNotLocked();
		$this->addToAssertionCount(1);
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
		$custom_lock = new PermanentLock();

		$subject = new class($custom_lock) implements LockableInterface {
			use PermanentlyLockableTrait;

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
}
