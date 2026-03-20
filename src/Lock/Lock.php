<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Lock;

use Override;
use PHPUtils\Lock\Interfaces\ReleasableLockInterface;

/**
 * Class Lock.
 *
 * Default in-memory, releasable lock implementation.
 *
 * To create an irreversible lock, use {@see PermanentLock} instead.
 */
class Lock implements ReleasableLockInterface
{
	private bool $acquired = false;

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function acquire(): static
	{
		$this->acquired = true;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function release(): static
	{
		$this->acquired = false;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function isAcquired(): bool
	{
		return $this->acquired;
	}
}
