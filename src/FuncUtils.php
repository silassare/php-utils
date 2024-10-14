<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils;

/**
 * Class FuncUtils.
 */
class FuncUtils
{
	/**
	 * Gets the location of the caller.
	 *
	 * @return array{file: string, line: int }
	 */
	public static function getCallerLocation(): array
	{
		$trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		$caller = $trace[1];

		return [
			'file'  => $caller['file'],
			'line'  => $caller['line'],
		];
	}
}
