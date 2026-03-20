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
use PHPUtils\Lock\Interfaces\LockInterface;
use PHPUtils\Lock\Interfaces\ReleasableLockInterface;
use PHPUtils\Lock\PermanentLock;

/**
 * Class PermanentLockTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class PermanentLockTest extends TestCase
{
	public function testImplementsLockInterface(): void
	{
		self::assertInstanceOf(LockInterface::class, new PermanentLock());
	}

	public function testNotImplementsReleasableLockInterface(): void
	{
		self::assertNotInstanceOf(ReleasableLockInterface::class, new PermanentLock());
	}

	public function testNotAcquiredByDefault(): void
	{
		$lock = new PermanentLock();

		self::assertFalse($lock->isAcquired());
	}

	public function testAcquire(): void
	{
		$lock   = new PermanentLock();
		$result = $lock->acquire();

		self::assertTrue($lock->isAcquired());
		self::assertSame($lock, $result);
	}

	public function testAcquireIsIdempotent(): void
	{
		$lock = new PermanentLock();
		$lock->acquire();
		$lock->acquire();

		self::assertTrue($lock->isAcquired());
	}

	public function testAcquireIsIrreversible(): void
	{
		$lock = new PermanentLock();
		$lock->acquire();

		// PermanentLock does not implement ReleasableLockInterface — no release() method
		self::assertFalse($lock instanceof ReleasableLockInterface);
		self::assertTrue($lock->isAcquired());
	}
}
