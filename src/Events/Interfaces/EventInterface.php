<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Events\Interfaces;

/**
 * Interface EventInterface.
 */
interface EventInterface
{
	public const RUN_FIRST = 1;

	public const RUN_DEFAULT = 2;

	public const RUN_LAST = 3;

	/**
	 * Stop event propagation, no more listeners will be called.
	 */
	public function stopPropagation(): static;

	/**
	 * Check if event propagation is stopped.
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool;

	/**
	 * Set the event propagation stopper.
	 */
	public function setPropagationStopper(callable $stopper): static;

	/**
	 * Get the event propagation stopper.
	 */
	public function getPropagationStopper(): ?callable;

	/**
	 * Listen to this event.
	 *
	 * @param callable(static):mixed $handler  the event handler
	 * @param int                    $priority the priority at which the $callback executed
	 * @param null|string            $channel  the channel of the event to listen to
	 */
	public static function listen(callable $handler, int $priority = self::RUN_DEFAULT, ?string $channel = null): void;

	/**
	 * Dispatch this event.
	 *
	 * @param null|callable(callable, static):void $executor the executor function
	 * @param null|string                          $channel  the channel in which the event will be dispatched
	 *
	 * @return static
	 */
	public function dispatch(?callable $executor = null, ?string $channel = null): static;
}
