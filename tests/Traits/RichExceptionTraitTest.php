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

/**
 * @internal
 *
 * @coversNothing
 */
final class RichExceptionTraitTest extends TestCase
{
	public function testGetData(): void
	{
		$e = new RuntimeException('A sample exception.', [
			'_sensitive' => 'yes',
			'line'       => ($line = __LINE__),
		]);

		self::assertSame([
			'line' => $line,
		], $e->getData());

		self::assertSame([
			'_sensitive' => 'yes',
			'line'       => $line,
		], $e->getData(true));
	}

	public function testSetData(): void
	{
		$e = new RuntimeException('Test.', ['original' => 1, '_secret' => 'x']);

		$result = $e->setData(['replaced' => 2]);

		// fluent
		self::assertSame($e, $result);

		// original data is completely replaced
		self::assertSame(['replaced' => 2], $e->getData(true));

		// sensitive key from old data is gone
		self::assertFalse(\array_key_exists('original', $e->getData(true)));
		self::assertFalse(\array_key_exists('_secret', $e->getData(true)));
	}

	public function testMergeData(): void
	{
		$e = new RuntimeException('Test.', ['a' => 1, '_hidden' => 'h']);

		$result = $e->mergeData(['b' => 2, 'a' => 99]);

		// fluent
		self::assertSame($e, $result);

		// existing keys preserved / overwritten, new keys added
		self::assertSame(99, $e->getData(true)['a']);
		self::assertSame(2, $e->getData(true)['b']);

		// sensitive key from original data is still present
		self::assertSame('h', $e->getData(true)['_hidden']);

		// sensitive keys hidden by default
		self::assertFalse(\array_key_exists('_hidden', $e->getData()));

		// non-sensitive keys still visible
		self::assertSame(99, $e->getData()['a']);
		self::assertSame(2, $e->getData()['b']);
	}
}
