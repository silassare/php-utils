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
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Store\StoreNotEditable;

/**
 * Class StoreNotEditableTest.
 *
 * @internal
 * @coversNothing
 */
final class StoreNotEditableTest extends TestCase
{
	private StoreNotEditable $store_not_editable;

	protected function setUp(): void
	{
		$this->store_not_editable = new StoreNotEditable([
			'foo' => [
				'bar' => 'baz',
			],
		]);

		parent::setUp();
	}

	public function testNotEditable(): void
	{
		$ne = $this->store_not_editable;

		static::assertTrue($ne->has('foo.bar'));
		static::assertSame('baz', $ne->get('foo.bar'));

		$key    = 'foo.bar';
		$parent = $ne->parentOf($key);

		$parent->remove('foo');

		static::assertTrue($ne->has('foo.bar'));

		$parent->set('foo.bar', 'zzz');

		static::assertSame('baz', $ne->get('foo.bar'));

		$data = $parent->getData();

		static::assertNull($data);

		$this->expectException(RuntimeException::class);

		$ne['foo.bar'] = 85;

		unset($ne['foo.bar']);
	}
}
