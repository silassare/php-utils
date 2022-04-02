<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Events\Interfaces;

interface EventInterface
{
	/**
	 * Indicate whether or not to stop propagating this event.
	 *
	 * @param bool $flag
	 */
	public function stopPropagation(bool $flag): void;

	/**
	 * Has this event indicated event propagation should stop?
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool;
}
