<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Traits;

use JsonException;
use PHPUnit\Framework\TestCase;
use PHPUtils\Interfaces\ArrayCapableInterface;
use PHPUtils\Traits\ArrayCapableTrait;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArrayCapableTraitTest extends TestCase
{
	public static array $arr = [
		'foo' => [
			'bar' => 'baz',
		],
	];

	private ArrayCapableInterface $foo;

	protected function setUp(): void
	{
		parent::setUp();

		$this->foo = new class() implements ArrayCapableInterface {
			use ArrayCapableTrait;

			/**
			 * {@inheritDoc}
			 */
			public function toArray(): array
			{
				return ArrayCapableTraitTest::$arr;
			}
		};
	}

	public function testAsArray(): void
	{
		static::assertSame(self::$arr, $this->foo->toArray());
	}

	/**
	 * @throws JsonException
	 */
	public function testJsonSerialize(): void
	{
		static::assertSame(
			\json_encode(self::$arr, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT),
			\json_encode($this->foo, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT)
		);
	}
}
