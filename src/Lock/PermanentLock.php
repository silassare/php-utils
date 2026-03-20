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
use PHPUtils\Lock\Interfaces\LockInterface;

/**
 * Class PermanentLock.
 *
 * An irreversible lock implementation.
 * Once acquired, the lock cannot be released.
 *
 * To create a releasable lock, use {@see Lock} instead.
 */
class PermanentLock implements LockInterface
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
	public function isAcquired(): bool
	{
		return $this->acquired;
	}
}
