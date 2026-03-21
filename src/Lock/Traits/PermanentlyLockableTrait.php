<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Lock\Traits;

use PHPUtils\Lock\Interfaces\LockInterface;
use PHPUtils\Lock\PermanentLock;

/**
 * Trait PermanentlyLockableTrait.
 *
 * A variant of {@see LockableTrait} that uses a {@see PermanentLock} by default,
 * making the lock irreversible. Calling {@see unlock()} on an entity that uses
 * this trait will always throw a RuntimeException.
 *
 * Use this trait when an entity must be permanently frozen after locking
 * and unlocking should never be allowed.
 *
 * Example:
 *
 * ```php
 * class FrozenConfig implements LockableInterface
 * {
 *     use PermanentlyLockableTrait;
 *
 *     private array $data = [];
 *
 *     public function set(string $key, mixed $value): static
 *     {
 *         $this->assertNotLocked();
 *         $this->data[$key] = $value;
 *         return $this;
 *     }
 * }
 *
 * $config = new FrozenConfig();
 * $config->set('env', 'prod');
 * $config->lock();
 * $config->set('env', 'dev'); // throws RuntimeException
 * $config->unlock();          // throws RuntimeException — permanently locked
 * ```
 */
trait PermanentlyLockableTrait
{
	use LockableTrait;

	/**
	 * {@inheritDoc}
	 *
	 * Returns a {@see PermanentLock}, making the lock irreversible.
	 */
	protected function createLock(): LockInterface
	{
		return new PermanentLock();
	}
}
