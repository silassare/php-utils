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
}
