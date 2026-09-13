<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Store;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use PHPUtils\Store\Map;
use PHPUtils\Store\Store;

/**
 * Class MapTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class MapTest extends TestCase
{
	public function testExtendsStore(): void
	{
		self::assertInstanceOf(Store::class, new Map());
	}

	public function testEmptyByDefault(): void
	{
		$map = new Map();

		self::assertSame([], $map->toArray());
	}

	public function testInitWithData(): void
	{
		$data = ['x' => 10, 'y' => 20];
		$map  = new Map($data);

		self::assertSame(['x' => 10, 'y' => 20], $map->toArray());
	}

	public function testSetAndGet(): void
	{
		$map = new Map();
		$map->set('foo', 'bar');

		self::assertSame('bar', $map->get('foo'));
	}

	public function testHas(): void
	{
		$map = new Map();

		self::assertFalse($map->has('key'));

		$map->set('key', 'value');

		self::assertTrue($map->has('key'));
	}

	public function testRemove(): void
	{
		$map = new Map();
		$map->set('key', 'value');
		$map->remove('key');

		self::assertFalse($map->has('key'));
	}

	public function testMerge(): void
	{
		$map = new Map();
		$map->merge(['a' => 1, 'b' => 2]);

		self::assertSame(['a' => 1, 'b' => 2], $map->toArray());
	}

	public function testJsonSerializeEmptyIsObject(): void
	{
		$map    = new Map();
		$result = $map->jsonSerialize();

		self::assertInstanceOf(ArrayObject::class, $result);
		self::assertSame('{}', \json_encode($result));
	}

	public function testJsonSerializeWithData(): void
	{
		$map = new Map();
		$map->set('a', 1);

		self::assertSame('{"a":1}', \json_encode($map));
	}

	public function testSetReturnsFluent(): void
	{
		$map = new Map();

		self::assertSame($map, $map->set('k', 'v'));
	}

	public function testMergeReturnsFluent(): void
	{
		$map = new Map();

		self::assertSame($map, $map->merge(['k' => 'v']));
	}

	/**
	 * @dataProvider provideLazyMergesAsMapMergeDoesCases
	 */
	public function testLazyMergesAsMapMergeDoes(array $target, array $source): void
	{
		foreach ([false, true] as $source_is_map) {
			$expected_data = $target;
			$actual_data   = $target;
			$expected      = new Map($expected_data);
			$actual        = new Map($actual_data);

			$source_data = $source;
			$from        = $source_is_map ? new Map($source_data) : $source;

			$expected->merge($from);
			$actual->lazyMerge($from);

			self::assertSame($expected->getData(), $actual->getData());
		}
	}

	public static function provideLazyMergesAsMapMergeDoesCases(): iterable
	{
		return [
			'empty source'             => [['a' => 1], []],
			'empty target'             => [[], ['field' => ['label' => 'A']]],
			'nested keys'              => [
				['field' => ['label' => 'A', 'hint' => 'h'], 'x' => 1],
				['field' => ['label' => 'B'], 'api' => ['doc' => ['description' => 'd']]],
			],
			'scalar replaced by array' => [['a' => 1], ['a' => ['b' => 2]]],
			'array replaced by scalar' => [['a' => ['b' => 2]], ['a' => 3]],
			'null values'              => [['a' => null], ['a' => ['b' => null], 'c' => null]],
			'lists by index'           => [['list' => [1, 2, 3]], ['list' => [9]]],
			'integer keys'             => [[0 => 'a', 5 => 'b'], [5 => 'c', 7 => 'd']],
			'dotted key'               => [['field' => ['label' => 'A']], ['field.label' => 'B']],
			'bracket key'              => [['list' => [1, 2]], ['list[1]' => 3]],
			'object value'             => [['a' => ['b' => 1]], ['a' => new ArrayObject(['c' => 2])]],
		];
	}
}
