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
}
