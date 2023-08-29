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
use PHPUtils\Exceptions\RuntimeException;

/**
 * Class Event.
 */
class Event implements EventInterface
{
	protected bool $dispatched = false;
	protected bool $stopped    = false;

	/**
	 * @var null|callable
	 */
	protected $stopper;

	/**
	 * {@inheritDoc}
	 */
	public function stopPropagation(): static
	{
		$this->stopped = true;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isPropagationStopped(): bool
	{
		return $this->stopped;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPropagationStopper(callable $stopper): static
	{
		$this->stopper = $stopper;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPropagationStopper(): ?callable
	{
		return $this->stopper ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function listen(callable $handler, int $priority = self::RUN_DEFAULT): void
	{
		EventManager::listen(static::class, $handler, $priority);
	}

	/**
	 * {@inheritDoc}
	 */
	public function dispatch(?callable $executor = null): static
	{
		if ($this->dispatched) {
			throw (new RuntimeException(
				'Event already dispatched, you may need to create a new instance.'
			))->suspectObject($this);
		}

		$this->dispatched = true;

		EventManager::dispatch($this, $executor);

		return $this;
	}
}
