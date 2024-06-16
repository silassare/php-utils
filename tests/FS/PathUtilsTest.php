<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\FS;

use PHPUnit\Framework\TestCase;
use PHPUtils\FS\PathUtils;

/**
 * Class PathUtilsTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class PathUtilsTest extends TestCase
{
	public function testNormalize(): void
	{
		static::assertSame('foo' . \DIRECTORY_SEPARATOR . 'bar', PathUtils::normalize('foo/bar'));
		static::assertSame('foo' . \DIRECTORY_SEPARATOR . 'bar', PathUtils::normalize('foo\\bar'));
	}

	public function testGetProtocol(): void
	{
		static::assertSame('https', PathUtils::getProtocol('https://foo.bar'));
		static::assertSame('file', PathUtils::getProtocol('file://foo.bar'));
		static::assertSame('C', PathUtils::getProtocol('C:\\foo.bar'));
	}

	public function testRegisterResolver(): void
	{
		$resolver = static function (string $path): string {
			return $path . '#resolved';
		};

		PathUtils::registerResolver('test', $resolver);

		static::assertSame('test://foo/.bar#resolved', PathUtils::resolve('test://foo', './.bar'));
	}

	public function testIsRelative(): void
	{
		static::assertTrue(PathUtils::isRelative('foo/bar'));
		static::assertTrue(PathUtils::isRelative('foo\\bar'));
		static::assertTrue(PathUtils::isRelative('c://foo/../bar'));
		static::assertFalse(PathUtils::isRelative('/foo/bar'));
		static::assertFalse(PathUtils::isRelative('\\foo\\bar'));
		static::assertTrue(PathUtils::isRelative('.env'));
		static::assertFalse(PathUtils::isRelative('/.env'));
	}

	public function testResolve(): void
	{
		$DS = \DIRECTORY_SEPARATOR;

		// LINUX
		static::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', 'baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', 'baz.txt'));
		static::assertSame($DS . 'baz.txt', PathUtils::resolve('/foo', '/baz.txt'));
		static::assertSame($DS . 'baz.txt', PathUtils::resolve('/foo/', '/baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', './baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', './baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', '../foo/baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', '../foo/baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', 'foo/baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', 'foo/baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', 'foo/../baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', 'foo/../baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', 'foo/./baz.txt'));
		static::assertSame($DS . 'foo' . $DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', 'foo/./baz.txt'));

		// DOS
		static::assertSame('C:\\foo\\baz.txt', PathUtils::resolve('C:\\foo', 'baz.txt'));
		static::assertSame('C:\\foo\\baz.txt', PathUtils::resolve('C:\\foo\\', 'baz.txt'));
		static::assertSame('/baz.txt', PathUtils::resolve('C:\\foo', '\\baz.txt'));
		static::assertSame('/baz.txt', PathUtils::resolve('C:\\foo\\', '\\baz.txt'));
		static::assertSame('C:\\foo\\baz.txt', PathUtils::resolve('C:\\foo', '.\\baz.txt'));
		static::assertSame('C:\\foo\\baz.txt', PathUtils::resolve('C:\\foo\\', '.\\baz.txt'));
		static::assertSame('C:\\foo\\baz.txt', PathUtils::resolve('C:\\foo', '..\\foo\\baz.txt'));
		static::assertSame('C:\\foo\\baz.txt', PathUtils::resolve('C:\\foo\\', '..\\foo\\baz.txt'));
		static::assertSame('C:\\foo\\foo\\baz.txt', PathUtils::resolve('C:\\foo', 'foo\\baz.txt'));
		static::assertSame('C:\\foo\\foo\\baz.txt', PathUtils::resolve('C:\\foo\\', 'foo\\baz.txt'));
		static::assertSame('C:\\foo\\baz.txt', PathUtils::resolve('C:\\foo', 'foo\\..\\baz.txt'));
		static::assertSame('C:\\foo\\baz.txt', PathUtils::resolve('C:\\foo\\', 'foo\\..\\baz.txt'));
		static::assertSame('C:\\foo\\foo\\baz.txt', PathUtils::resolve('C:\\foo', 'foo\\.\\baz.txt'));
		static::assertSame('C:\\foo\\foo\\baz.txt', PathUtils::resolve('C:\\foo\\', 'foo\\.\\baz.txt'));

		// HTTP(S)
		static::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo', 'baz.txt'));
		static::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo/', 'baz.txt'));
		static::assertSame('/baz.txt', PathUtils::resolve('https://foo', '/baz.txt'));
		static::assertSame('/baz.txt', PathUtils::resolve('https://foo/', '/baz.txt'));
		static::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo', './baz.txt'));
		static::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo/', './baz.txt'));
		static::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo', '../foo/baz.txt'));
		static::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo/', '../foo/baz.txt'));
	}
}
