<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Events;

use PHPUtils\Events\Interfaces\EventInterface;

/**
 * Class Event.
 */
class Event implements EventInterface
{
	public const RUN_FIRST = 1;

	public const RUN_DEFAULT = 2;

	public const RUN_LAST = 3;

	protected bool $stopped = false;

	/**
	 * Indicate whether or not to stop propagating this event.
	 *
	 * @param bool $flag
	 */
	public function stopPropagation(bool $flag): void
	{
		$this->stopped = $flag;
	}

	/**
	 * Has this event indicated event propagation should stop?
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool
	{
		return $this->stopped;
	}

	/**
	 * Listen to this event.
	 *
	 * @param callable $handler
	 * @param int      $priority the priority at which the $callback executed
	 *
	 * @return bool
	 */
	public static function handle(callable $handler, int $priority = self::RUN_DEFAULT): bool
	{
		return EventManager::getInstance()
			->attach(static::class, $handler, $priority);
	}

	/**
	 * Trigger an event.
	 *
	 * @param \PHPUtils\Events\Interfaces\EventInterface $event
	 *
	 * @return \PHPUtils\Events\EventManager
	 */
	public static function trigger(EventInterface $event): EventManager
	{
		return EventManager::getInstance()
			->trigger($event);
	}
}
