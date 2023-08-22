<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Env\Tokens;

use PHPUtils\Interfaces\ArrayCapableInterface;

/**
 * Class Token.
 */
abstract class Token implements ArrayCapableInterface
{
	/**
	 * Token constructor.
	 *
	 * @param null|bool|float|int|string $value The token value
	 * @param string                     $raw   The raw token value
	 * @param int                        $start The start position of the token
	 * @param int                        $end   The end position of the token
	 */
	public function __construct(
		public null|int|float|bool|string $value,
		public string $raw = '',
		public int $start = -1,
		public int $end = -1,
	) {
	}

	/**
	 * String representation of the token.
	 */
	public function __toString(): string
	{
		return $this->raw;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'type'  => static::class,
			'start' => $this->start,
			'end'   => $this->end,
			'value' => $this->value,
			'raw'   => $this->raw,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
