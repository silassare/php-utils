<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Env\Tokens;

/**
 * Class Equal.
 */
class Equal extends Token
{
	/**
	 * Equal constructor.
	 *
	 * @param int $index The token index
	 */
	public function __construct(
		public int $index = -1,
	) {
		parent::__construct('=', '=', $index, $index);
	}
}
