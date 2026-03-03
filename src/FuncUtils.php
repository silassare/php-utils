<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils;

use RuntimeException;

/**
 * Class FuncUtils.
 */
class FuncUtils
{
	/**
	 * Gets the location of the caller.
	 *
	 * Returns information about the file and line where the method that called
	 * getCallerLocation() was itself invoked.
	 *
	 * @return array{file: string, line: int}
	 *
	 * @throws RuntimeException when the stack trace is too shallow or missing file/line info
	 */
	public static function getCallerLocation(): array
	{
		$trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		if (!isset($trace[1])) {
			throw new RuntimeException('Unable to determine caller location: insufficient stack trace');
		}

		$caller = $trace[1];

		if (!isset($caller['file'], $caller['line'])) {
			throw new RuntimeException('Unable to determine caller location: missing file or line information');
		}

		return [
			'file'  => $caller['file'],
			'line'  => $caller['line'],
		];
	}
}
