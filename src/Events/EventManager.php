<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Events;

use InvalidArgumentException;
use PHPUtils\Events\Interfaces\EventInterface;

/**
 * Class EventManager.
 */
class EventManager
{
	private static array $listeners = [];

	private static ?self $instance = null;

	/**
	 * EventManager constructor. (Singleton pattern).
	 */
	private function __construct()
	{
	}

	/**
	 * Gets event manager instance. (Singleton pattern).
	 *
	 * @return EventManager
	 */
	public static function getInstance(): self
	{
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Attaches a listener to an event.
	 *
	 * @param string   $event    the event to attach too
	 * @param callable $callback a callable function
	 * @param int      $priority the priority at which the $callback executed
	 *
	 * @return bool true on success false on failure
	 */
	public function attach(string $event, callable $callback, int $priority = Event::RUN_DEFAULT): bool
	{
		if (
			Event::RUN_DEFAULT === $priority
			|| Event::RUN_FIRST === $priority
			|| Event::RUN_LAST === $priority
		) {
			self::$listeners[$event][$priority][] = $callback;
		} else {
			throw new InvalidArgumentException(
				\sprintf(
					'Invalid priority "%s" set for hook receiver callable. Allowed value are %s::RUN_* constants.',
					$priority,
					self::class
				)
			);
		}

		return true;
	}

	/**
	 * Detaches a listener from an event.
	 *
	 * @param string   $event    the event to attach too
	 * @param callable $callback a callable function
	 *
	 * @return bool true on success false on failure
	 */
	public function detach(string $event, callable $callback): bool
	{
		$success = false;

		if (isset(self::$listeners[$event])) {
			foreach (self::$listeners[$event] as $priority => $listeners) {
				foreach ($listeners as $index => $listener) {
					if ($listener === $callback) {
						$success = true;
						unset(self::$listeners[$event][$priority][$index]);
					}
				}
			}
		}

		return $success;
	}

	/**
	 * Clear all listeners for a given event.
	 *
	 * @param string $event
	 *
	 * @return $this
	 */
	public function clearListeners(string $event): self
	{
		if (isset(self::$listeners[$event])) {
			unset(self::$listeners[$event]);
		}

		return $this;
	}

	/**
	 * Trigger an event.
	 *
	 * @param \PHPUtils\Events\Interfaces\EventInterface $event
	 *
	 * @return $this
	 */
	public function trigger(EventInterface $event): self
	{
		$name = $event::class;

		if (isset(self::$listeners[$name])) {
			$map[] = self::$listeners[$name][Event::RUN_FIRST] ?? [];
			$map[] = self::$listeners[$name][Event::RUN_DEFAULT] ?? [];
			$map[] = \array_reverse(self::$listeners[$name][Event::RUN_LAST] ?? []);

			foreach ($map as /* $priority => */ $listeners) {
				foreach ($listeners as /* $index => */ $listener) {
					$listener($event);

					if ($event->isPropagationStopped()) {
						$event->setPropagationStopper($listener);

						break 2;
					}
				}
			}
		}

		return $this;
	}
}
