<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests;

use PHPUnit\Framework\TestCase;
use PHPUtils\FS\PathUtils;
use Throwable;

/**
 * Class TestHelper.
 *
 * What the suite shares, as a class rather than as globals a bootstrap defines: a `define()` and a
 * global function are invisible to every other file as far as any tool can tell, so a test reading
 * them was reading something nothing declared.
 *
 * @internal
 */
final class TestHelper
{
	/**
	 * The directory the suite's asset files live in, with its trailing separator.
	 */
	public const string ASSETS_DIR = __DIR__ . PathUtils::DS . 'assets' . PathUtils::DS;

	/**
	 * Asserts that the callable raises the same exception: class, message and code.
	 */
	public static function assertException(Throwable $expected, callable $callable): void
	{
		try {
			$callable();
		} catch (Throwable $actual) {
			$a = $expected::class;
			$b = $actual::class;
			TestCase::assertSame($a, $b, \sprintf('Expected exception class to be "%s" found "%s".', $a, $b));

			$a = $expected->getMessage();
			$b = $actual->getMessage();
			TestCase::assertSame($a, $b, \sprintf('Expected exception message to be "%s" found "%s".', $a, $b));

			$a = $expected->getCode();
			$b = $actual->getCode();
			TestCase::assertSame($a, $b, \sprintf('Expected exception code to be "%s" found "%s".', $a, $b));

			return;
		}

		TestCase::fail(\sprintf('Failed asserting that exception of type "%s" is thrown..', $expected::class));
	}
}
