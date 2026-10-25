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

	#[Override]
	public function stopPropagation(): static
	{
		$this->stopped = true;

		return $this;
	}

	#[Override]
	public function isPropagationStopped(): bool
	{
		return $this->stopped;
	}

	#[Override]
	public function setPropagationStopper(callable $stopper): static
	{
		$this->stopper = $stopper;

		return $this;
	}

	#[Override]
	public function getPropagationStopper(): ?callable
	{
		return $this->stopper ?? null;
	}

	#[Override]
	public static function listen(callable $handler, int $priority = self::RUN_DEFAULT, ?string $channel = null): Closure
	{
		return EventManager::listen(static::class, $handler, $priority, $channel);
	}

	#[Override]
	public function dispatch(?callable $executor = null, ?string $channel = null): static
	{
		$this->stopped = false;

		EventManager::dispatch($this, $executor, $channel);

		return $this;
	}
}
