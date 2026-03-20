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
use PHPUtils\Lock\Lock;

/**
 * Class LockTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class LockTest extends TestCase
{
	public function testImplementsLockInterface(): void
	{
		self::assertInstanceOf(LockInterface::class, new Lock());
	}

	public function testImplementsReleasableLockInterface(): void
	{
		self::assertInstanceOf(ReleasableLockInterface::class, new Lock());
	}

	public function testNotAcquiredByDefault(): void
	{
		$lock = new Lock();

		self::assertFalse($lock->isAcquired());
	}

	public function testAcquire(): void
	{
		$lock   = new Lock();
		$result = $lock->acquire();

		self::assertTrue($lock->isAcquired());
		self::assertSame($lock, $result);
	}

	public function testAcquireIsIdempotent(): void
	{
		$lock = new Lock();
		$lock->acquire();
		$lock->acquire(); // second call is a no-op

		self::assertTrue($lock->isAcquired());
	}

	public function testRelease(): void
	{
		$lock   = new Lock();
		$lock->acquire();
		$result = $lock->release();

		self::assertFalse($lock->isAcquired());
		self::assertSame($lock, $result);
	}

	public function testReleaseWhenNotAcquired(): void
	{
		$lock = new Lock();
		$lock->release(); // idempotent — no-op on a not-acquired lock

		self::assertFalse($lock->isAcquired());
	}

	public function testAcquireAfterRelease(): void
	{
		$lock = new Lock();
		$lock->acquire();
		$lock->release();
		$lock->acquire();

		self::assertTrue($lock->isAcquired());
	}
}
