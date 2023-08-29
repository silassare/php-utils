<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Events;

use Closure;
use InvalidArgumentException;
use PHPUtils\Events\Interfaces\EventInterface;

/**
 * Class EventManager.
 */
class EventManager
{
	private static array $listeners = [];

	/**
	 * Attaches a listener to an event.
	 *
	 * @param class-string<EventInterface> $event    the event to be listened
	 * @param callable                     $callback a callable function
	 * @param int                          $priority the priority at which the listener is executed
	 *
	 * @return Closure a closure that can be used to detach the listener
	 */
	public static function listen(string $event, callable $callback, int $priority = EventInterface::RUN_DEFAULT): Closure
	{
		if (
			EventInterface::RUN_DEFAULT === $priority
			|| EventInterface::RUN_FIRST === $priority
			|| EventInterface::RUN_LAST === $priority
		) {
			self::$listeners[$event][$priority][] = $callback;

			return static fn () => self::detach($event, $priority, $callback);
		}

		throw new InvalidArgumentException(
			\sprintf(
				'Invalid priority "%s" set for hook receiver callable. Allowed value are %s::RUN_* constants.',
				$priority,
				self::class
			)
		);
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
	 * Dispatch an event.
	 *
	 * @param EventInterface                               $event    the event to trigger
	 * @param null|callable(callable, EventInterface):void $executor the executor, is responsible for calling
	 *                                                               the listeners it will receive the listener
	 *                                                               and the event as arguments
	 */
	public static function dispatch(EventInterface $event, ?callable $executor = null): void
	{
		$name = $event::class;

		if (isset(self::$listeners[$name])) {
			$map[] = self::$listeners[$name][Event::RUN_FIRST] ?? [];
			$map[] = self::$listeners[$name][Event::RUN_DEFAULT] ?? [];
			$map[] = \array_reverse(self::$listeners[$name][Event::RUN_LAST] ?? []);

			foreach ($map as /* $priority => */ $listeners) {
				foreach ($listeners as /* $index => */ $listener) {
					if ($executor) {
						$executor($listener, $event);
					} else {
						$listener($event);
					}

					if ($event->isPropagationStopped()) {
						// set the stopper, only if it's not already set
						// this is to avoid overriding the stopper set by the executor
						// the reason behind setting propagation stopper is for debugging purpose
						if (null === $event->getPropagationStopper()) {
							$event->setPropagationStopper($listener);
						}

						break 2;
					}
				}
			}
		}
	}

	/**
	 * Used internally to detach a listener.
	 *
	 * @param class-string<EventInterface> $event
	 * @param int                          $priority
	 * @param callable                     $listener
	 *
	 * @return bool true on success false on failure
	 */
	private static function detach(string $event, int $priority, callable $listener): bool
	{
		$success = false;

		if (isset(self::$listeners[$event][$priority])) {
			foreach (self::$listeners[$event][$priority] as $index => $fn) {
				if ($listener === $fn) {
					$success = true;
					unset(self::$listeners[$event][$priority][$index]);
				}
			}
		}

		return $success;
	}
}
