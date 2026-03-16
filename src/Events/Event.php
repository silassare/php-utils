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
use Override;
use PHPUtils\Events\Interfaces\EventInterface;

/**
 * Class Event.
 */
class Event implements EventInterface
{
	protected bool $stopped = false;

	/**
	 * @var null|callable
	 */
	protected $stopper;

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function stopPropagation(): static
	{
		$this->stopped = true;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function isPropagationStopped(): bool
	{
		return $this->stopped;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function setPropagationStopper(callable $stopper): static
	{
		$this->stopper = $stopper;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getPropagationStopper(): ?callable
	{
		return $this->stopper ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public static function listen(callable $handler, int $priority = self::RUN_DEFAULT, ?string $channel = null): Closure
	{
		return EventManager::listen(static::class, $handler, $priority, $channel);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function dispatch(?callable $executor = null, ?string $channel = null): static
	{
		$this->stopped = false;

		/** @psalm-suppress ArgumentTypeCoercion */
		EventManager::dispatch($this, $executor, $channel);

		return $this;
	}
}
