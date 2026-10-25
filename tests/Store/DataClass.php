<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Store;

use ArrayAccess;

/**
 * Class DataClass.
 *
 * @internal
 */
final class DataClass implements ArrayAccess
{
	use ArrayAccessTrait;

	public const string PUBLIC_CONST = 'PUBLIC_CONST';

	public static array $static_property = [
		'bar' => 'bar',
	];

	public array $public_property = [
		'foo' => 'foo',
	];

	/**
	 * DataClass constructor.
	 */
	public function __construct(public array $data = []) {}

	public function publicMethod(): array
	{
		return [];
	}

	public static function staticMethod(): array
	{
		return [];
	}
}
