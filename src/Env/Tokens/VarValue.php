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
 * Class VarValue.
 */
class VarValue extends Token
{
	/**
	 * {@inheritDoc}
	 */
	public function __toString(): string
	{
		$value = $this->raw;
		$quote = '';
		$head  = $value[0] ?? '';
		$end   = \substr($value, -1);
		if ($head === $end && \in_array($head, ['"', "'"], true)) {
			$quote = $head;
			$value = \substr($value, 1, -1);
		}

		$value = \addcslashes($value, "\n\r\t\v\f\\" . $quote);

		return $quote . $value . $quote;
	}
}
