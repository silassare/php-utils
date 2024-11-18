<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Traits;

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

		$this->foo = new class implements ArrayCapableInterface {
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

	public function testJsonSerialize(): void
	{
		self::assertSame(
			\json_encode(self::$arr, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT),
			\json_encode($this->foo, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT)
		);

		$empty_array = new class implements ArrayCapableInterface {
			use ArrayCapableTrait;

			/**
			 * {@inheritDoc}
			 */
			public function toArray(): array
			{
				return [];
			}
		};
		$empty_object = new class implements ArrayCapableInterface {
			use ArrayCapableTrait;

			public function __construct()
			{
				$this->json_empty_array_is_object = true;
			}

			/**
			 * {@inheritDoc}
			 */
			public function toArray(): array
			{
				return [];
			}
		};

		self::assertSame('[]', \json_encode($empty_array));
		self::assertSame('{}', \json_encode($empty_object));
	}

	public function testAsArray(): void
	{
		self::assertSame(self::$arr, $this->foo->toArray());
	}
}
