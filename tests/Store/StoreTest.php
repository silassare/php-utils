<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Store;

use PHPUnit\Framework\TestCase;
use PHPUtils\Store\Store;
use stdClass;

/**
 * Class StoreTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class StoreTest extends TestCase
{
	private Store $store;
	private DataClass $data;

	protected function setUp(): void
	{
		$foo      = new stdClass();
		$foo->bar = [
			'baz' => 'foo_bar_baz',
		];

		$data                     = [
			'foo' => $foo,
			'a'   => 1,
			'b'   => '2',
			'c'   => false,
		];
		$this->data               = new DataClass($data);
		$this->store              = new Store($this->data);

		parent::setUp();
	}

	public function testHas(): void
	{
		$s = $this->store;

		self::assertTrue($s->has('a'));
		self::assertTrue($s->has('foo.bar'));
		self::assertTrue(isset($s['foo.bar']));
		self::assertFalse(isset($s['foo.bar.boz']));
		self::assertFalse($s->has('foo.bar.0'));
		self::assertFalse($s->has('__unset__'));
		self::assertTrue($s->has('public_property'));
		self::assertTrue($s->has('static_property'));
		self::assertTrue($s->has('PUBLIC_CONST'));
	}

	public function testParentOf(): void
	{
		$s    = $this->store;
		$data = $this->data;

		$key    = 'a';
		$parent = $s->parentOf($key);

		self::assertSame($data, $parent->getData());

		$key    = 'foo.bar';
		$parent = $s->parentOf($key, $access_key);

		self::assertSame('bar', $access_key);
		self::assertSame($data['foo'], $parent->getData());

		$key    = 'foo.bar.baz';
		$parent = $s->parentOf($key, $access_key);

		self::assertSame('baz', $access_key);
		self::assertSame($data['foo']->bar, $parent->getData());

		$key    = 'public_property';
		$parent = $s->parentOf($key);

		self::assertSame($data, $parent->getData());

		$key    = 'static_property';
		$parent = $s->parentOf($key);

		self::assertSame($data, $parent->getData());

		$key    = 'PUBLIC_CONST';
		$parent = $s->parentOf($key);

		self::assertSame($data, $parent->getData());
	}

	public function testGet(): void
	{
		$s    = $this->store;
		$data = $this->data->data;

		self::assertSame($data['a'], $s->get('a'));
		self::assertSame($data['a'], $s['a']);
		self::assertSame($data['b'], $s->get('b'));
		self::assertSame($data['c'], $s->get('c'));

		self::assertSame($data['foo']->bar['baz'], $s->get('foo.bar.baz'));
		self::assertSame($data['foo']->bar['baz'], $s['foo.bar.baz']);

		self::assertSame(8, $s->get('d', 8));
		self::assertNull($s->get('f'));

		self::assertSame('def', $s->get('foo.bar.bazzz', 'def'));

		$data = $this->data;

		$v = $s->get('public_property');
		self::assertSame($data->public_property, $v);

		$v = $s->get('static_property');
		self::assertSame($data::$static_property, $v);

		$v = $s->get('PUBLIC_CONST');
		self::assertSame($data::PUBLIC_CONST, $v);
	}

	public function testSet(): void
	{
		$s = $this->store;

		$s->set('a', 5);

		self::assertSame(5, $s->get('a'));

		$s->set('foo.bar.baz', 0);

		self::assertSame(0, $s->get('foo.bar.baz'));

		$s['foo.bar.baz'] = 4;

		self::assertSame(4, $s->get('foo.bar.baz'));

		$s->set('foo.bar', 'foo_bar');

		self::assertSame('foo_bar', $s->get('foo.bar'));
		self::assertNull($s->get('foo.bar.baz'));

		$s->set('public_property', ['bar' => [null, 'baz']]);

		self::assertSame('baz', $s->get('public_property.bar.1'));

		$s->set('static_property', ['bar' => [null, 'baz_z_z']]);

		self::assertSame('baz_z_z', $s->get('static_property.bar.1'));

		$s->set('foo', new stdClass());

		$s->set('foo.8.', 25);

		self::assertSame(25, $s->get('foo.8.'));
		self::assertIsArray($s->get('foo.8'));

		// this will not overwrite DataClass::PUBLIC_CONST constant
		// otherwise as DataClass implements ArrayAccess it will create a new entry as if we did
		// $d = new DataClass([]);
		// $d['PUBLIC_CONST'] = ['bar' => [null, 'boz']];
		$s->set('PUBLIC_CONST', ['bar' => [null, 'boz']]);

		self::assertSame(DataClass::PUBLIC_CONST, $this->data::PUBLIC_CONST);
		self::assertSame('boz', $s->get('PUBLIC_CONST.bar.1'));
	}

	public function testMerge(): void
	{
		$s = $this->store;

		$s->merge([
			'a'           => false,
			'foo.bar.baz' => [
				1 => false,
				3 => true,
			],
		]);

		self::assertTrue($s->get('foo.bar.baz.3'));
		self::assertFalse($s->get('a'));

		$s->merge(new Store([
			'knock' => 33,
		]));

		self::assertSame(33, $s->get('knock'));
	}

	public function testIterable(): void
	{
		$s = $this->store;
		self::assertIsIterable($s);

		$keys = [];
		foreach ($s as $key => $value) {
			$keys[] = $key;
			self::assertSame($s->get($key), $value);
		}

		self::assertSame(['public_property', 'data'], $keys);
	}

	public function testRemove(): void
	{
		$s = $this->store;

		$s->remove('a');

		self::assertNull($s->get('a'));

		$s->set('foo.bar.boz', [
			'o'  => 85,
			'df' => ['lorem'],
		]);

		unset($s['foo.bar.boz.o']);

		self::assertSame(['df' => ['lorem']], $s->get('foo.bar.boz'));

		$s->set('public_property', ['bar' => [null, 'baz', 8]]);

		$s->remove('public_property.bar.1');

		self::assertSame([null, 2 => 8], $s->get('public_property.bar'));

		$s->set('static_property', ['bar' => [null, 'baz', 9]]);

		$s->remove('static_property.bar.1');

		self::assertSame([null, 2 => 9], $s->get('static_property.bar'));

		$s->set('PUBLIC_CONST', ['bar' => [null, 'baz', 10]]);

		$s->remove('PUBLIC_CONST.bar.1');

		self::assertSame([null, 'baz', 10], $s->get('PUBLIC_CONST.bar'));

		// this is to track if it is possible to remove a class constant
		$s->remove('PUBLIC_CONST');

		self::assertSame(DataClass::PUBLIC_CONST, $s->get('PUBLIC_CONST'));
	}

	public function testSetData(): void
	{
		$s = $this->store;

		$s->setData([]);

		$s->set('foo.bar', ['lorem']);

		self::assertSame([
			'foo' => [
				'bar' => ['lorem'],
			],
		], $s->getData());
	}
}
