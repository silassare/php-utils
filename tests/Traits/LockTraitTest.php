<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Traits;

use PHPUnit\Framework\TestCase;
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Interfaces\LockInterface;
use PHPUtils\Traits\LockTrait;

/**
 * Class LockTraitTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class LockTraitTest extends TestCase
{
	private LockInterface $subject;

	protected function setUp(): void
	{
		$this->subject = new class implements LockInterface {
			use LockTrait;
		};
	}

	public function testNotLockedByDefault(): void
	{
		self::assertFalse($this->subject->isLocked());
	}

	public function testLock(): void
	{
		$result = $this->subject->lock();

		self::assertTrue($this->subject->isLocked());
		// fluent interface
		self::assertSame($this->subject, $result);
	}

	public function testAssertNotLockedPassesWhenUnlocked(): void
	{
		// should not throw
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

	public function testLockIsIrreversible(): void
	{
		$this->subject->lock();
		$this->subject->lock(); // second call is a no-op, no throw

		self::assertTrue($this->subject->isLocked());
	}
}
