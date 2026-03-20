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
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Lock\Interfaces\LockableInterface;
use PHPUtils\Lock\LockableTrait;
use PHPUtils\Store\Map;
use PHPUtils\Traits\MetadataTrait;

/**
 * Class MetadataTraitTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class MetadataTraitTest extends TestCase
{
	public function testGetMetaReturnsMap(): void
	{
		$obj = new class {
			use MetadataTrait;
		};

		self::assertInstanceOf(Map::class, $obj->getMeta());
	}

	public function testGetMetaLazyInit(): void
	{
		$obj = new class {
			use MetadataTrait;
		};

		self::assertSame($obj->getMeta(), $obj->getMeta());
	}

	public function testSetMetaWithStringKey(): void
	{
		$obj = new class {
			use MetadataTrait;
		};

		$result = $obj->setMetaKey('foo', 'bar');

		self::assertSame('bar', $obj->getMeta()->get('foo'));
		self::assertSame($obj, $result);
	}

	public function testSetMetaWithArray(): void
	{
		$obj = new class {
			use MetadataTrait;
		};

		$obj->mergeMeta(['a' => 1, 'b' => 2]);

		self::assertSame(1, $obj->getMeta()->get('a'));
		self::assertSame(2, $obj->getMeta()->get('b'));
	}

	public function testSetMetaWithMap(): void
	{
		$obj = new class {
			use MetadataTrait;
		};

		$map = new Map();
		$map->set('x', 99);

		$obj->mergeMeta($map);

		self::assertSame(99, $obj->getMeta()->get('x'));
	}

	public function testSetMetaMergesWithExistingMeta(): void
	{
		$obj = new class {
			use MetadataTrait;
		};

		$obj->setMetaKey('existing', 'value');
		$obj->mergeMeta(['new' => 'data']);

		self::assertSame('value', $obj->getMeta()->get('existing'));
		self::assertSame('data', $obj->getMeta()->get('new'));
	}

	public function testSetMetaOverwritesKey(): void
	{
		$obj = new class {
			use MetadataTrait;
		};

		$obj->setMetaKey('key', 'first');
		$obj->setMetaKey('key', 'second');

		self::assertSame('second', $obj->getMeta()->get('key'));
	}

	public function testSetMetaKeyThrowsWhenLocked(): void
	{
		$obj = new class implements LockableInterface {
			use LockableTrait;
			use MetadataTrait;
		};

		$obj->lock();

		$this->expectException(RuntimeException::class);
		$obj->setMetaKey('foo', 'bar');
	}

	public function testMergeMetaThrowsWhenLocked(): void
	{
		$obj = new class implements LockableInterface {
			use LockableTrait;
			use MetadataTrait;
		};

		$obj->lock();

		$this->expectException(RuntimeException::class);
		$obj->mergeMeta(['foo' => 'bar']);
	}

	public function testSetMetaKeyDoesNotThrowWhenNotLocked(): void
	{
		$obj = new class implements LockableInterface {
			use LockableTrait;
			use MetadataTrait;
		};

		$obj->setMetaKey('foo', 'bar');

		self::assertSame('bar', $obj->getMeta()->get('foo'));
	}

	public function testSetMetaKeyDoesNotThrowWithoutLockTrait(): void
	{
		$obj = new class {
			use MetadataTrait;
		};

		$obj->setMetaKey('a', 1);
		$obj->setMetaKey('a', 2);

		self::assertSame(2, $obj->getMeta()->get('a'));
	}
}
