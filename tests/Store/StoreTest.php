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
 * @coversNothing
 */
final class StoreTest extends TestCase
{
	private Store            $store;
	private DataClass        $data;

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

		static::assertTrue($s->has('a'));
		static::assertTrue($s->has('foo.bar'));
		static::assertTrue(isset($s['foo.bar']));
		static::assertFalse(isset($s['foo.bar.boz']));
		static::assertFalse($s->has('foo.bar.0'));
		static::assertFalse($s->has('__unset__'));
		static::assertTrue($s->has('public_property'));
		static::assertTrue($s->has('static_property'));
		static::assertTrue($s->has('PUBLIC_CONST'));
	}

	public function testParentOf(): void
	{
		$s    = $this->store;
		$data = $this->data;

		$key    = 'a';
		$parent = $s->parentOf($key);

		static::assertSame($data, $parent->getData());

		$key    = 'foo.bar';
		$parent = $s->parentOf($key, $access_key);

		static::assertSame('bar', $access_key);
		static::assertSame($data['foo'], $parent->getData());

		$key    = 'foo.bar.baz';
		$parent = $s->parentOf($key, $access_key);

		static::assertSame('baz', $access_key);
		static::assertSame($data['foo']->bar, $parent->getData());

		$key    = 'public_property';
		$parent = $s->parentOf($key);

		static::assertSame($data, $parent->getData());

		$key    = 'static_property';
		$parent = $s->parentOf($key);

		static::assertSame($data, $parent->getData());

		$key    = 'PUBLIC_CONST';
		$parent = $s->parentOf($key);

		static::assertSame($data, $parent->getData());
	}

	public function testGet(): void
	{
		$s    = $this->store;
		$data = $this->data->data;

		static::assertSame($data['a'], $s->get('a'));
		static::assertSame($data['a'], $s['a']);
		static::assertSame($data['b'], $s->get('b'));
		static::assertSame($data['c'], $s->get('c'));

		static::assertSame($data['foo']->bar['baz'], $s->get('foo.bar.baz'));
		static::assertSame($data['foo']->bar['baz'], $s['foo.bar.baz']);

		static::assertSame(8, $s->get('d', 8));
		static::assertNull($s->get('f'));

		static::assertSame('def', $s->get('foo.bar.bazzz', 'def'));

		$data = $this->data;

		$v = $s->get('public_property');
		static::assertSame($data->public_property, $v);

		$v = $s->get('static_property');
		static::assertSame($data::$static_property, $v);

		$v = $s->get('PUBLIC_CONST');
		static::assertSame($data::PUBLIC_CONST, $v);
	}

	public function testSet(): void
	{
		$s = $this->store;

		$s->set('a', 5);

		static::assertSame(5, $s->get('a'));

		$s->set('foo.bar.baz', 0);

		static::assertSame(0, $s->get('foo.bar.baz'));

		$s['foo.bar.baz'] = 4;

		static::assertSame(4, $s->get('foo.bar.baz'));

		$s->set('foo.bar', 'foo_bar');

		static::assertSame('foo_bar', $s->get('foo.bar'));
		static::assertNull($s->get('foo.bar.baz'));

		$s->set('public_property', ['bar' => [null, 'baz']]);

		static::assertSame('baz', $s->get('public_property.bar.1'));

		$s->set('static_property', ['bar' => [null, 'baz_z_z']]);

		static::assertSame('baz_z_z', $s->get('static_property.bar.1'));

		$s->set('foo', new stdClass());

		$s->set('foo.8.', 25);

		static::assertSame(25, $s->get('foo.8.'));
		static::assertIsArray($s->get('foo.8'));

		// this will not overwrite DataClass::PUBLIC_CONST constant
		// otherwise as DataClass implements ArrayAccess it will create a new entry as if we did
		// $d = new DataClass([]);
		// $d['PUBLIC_CONST'] = ['bar' => [null, 'boz']];
		$s->set('PUBLIC_CONST', ['bar' => [null, 'boz']]);

		static::assertSame(DataClass::PUBLIC_CONST, $this->data::PUBLIC_CONST);
		static::assertSame('boz', $s->get('PUBLIC_CONST.bar.1'));
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

		static::assertTrue($s->get('foo.bar.baz.3'));
		static::assertFalse($s->get('a'));

		$s->merge([
			'foo.bar.baz' => [
				3 => false,
			],
		]);

		static::assertFalse($s->get('foo.bar.baz.3'));
	}

	public function testRemove(): void
	{
		$s = $this->store;

		$s->remove('a');

		static::assertNull($s->get('a'));

		$s->set('foo.bar.boz', [
			'o'  => 85,
			'df' => ['lorem'],
		]);

		unset($s['foo.bar.boz.o']);

		static::assertSame(['df' => ['lorem']], $s->get('foo.bar.boz'));

		$s->set('public_property', ['bar' => [null, 'baz', 8]]);

		$s->remove('public_property.bar.1');

		static::assertSame([null, 2 => 8], $s->get('public_property.bar'));

		$s->set('static_property', ['bar' => [null, 'baz', 9]]);

		$s->remove('static_property.bar.1');

		static::assertSame([null, 2 => 9], $s->get('static_property.bar'));

		$s->set('PUBLIC_CONST', ['bar' => [null, 'baz', 10]]);

		$s->remove('PUBLIC_CONST.bar.1');

		static::assertSame([null, 'baz', 10], $s->get('PUBLIC_CONST.bar'));

		// this is to track if it is possible to remove a class constant
		$s->remove('PUBLIC_CONST');

		static::assertSame(DataClass::PUBLIC_CONST, $s->get('PUBLIC_CONST'));
	}

	public function testSetData(): void
	{
		$s = $this->store;

		$s->setData([]);

		$s->set('foo.bar', ['lorem']);

		static::assertSame([
			'foo' => [
				'bar' => ['lorem'],
			],
		], $s->getData());
	}
}
