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
 *
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

		self::assertTrue($ne->has('foo.bar'));
		self::assertSame('baz', $ne->get('foo.bar'));

		$key    = 'foo.bar';
		$parent = $ne->parentOf($key);

		$parent->remove('foo');

		self::assertTrue($ne->has('foo.bar'));

		$parent->set('foo.bar', 'zzz');

		self::assertSame('baz', $ne->get('foo.bar'));

		$this->expectException(RuntimeException::class);

		$ne['foo.bar'] = 85;

		unset($ne['foo.bar']);
	}

	/**
	 * Regression: same parentOf() single-segment bracket fix applies to
	 * StoreNotEditable since it shares StoreTrait.
	 */
	public function testBracketQuotedSingleSegment(): void
	{
		$ne = new StoreNotEditable([
			'my.key'   => 'dot-value',
			'key-dash' => 'dash-value',
		]);

		self::assertTrue($ne->has("['my.key']"));
		self::assertTrue($ne->has("['key-dash']"));
		self::assertFalse($ne->has("['nonexistent']"));

		self::assertSame('dot-value', $ne->get("['my.key']"));
		self::assertSame('dash-value', $ne->get("['key-dash']"));
		self::assertNull($ne->get("['missing']"));
	}
}
