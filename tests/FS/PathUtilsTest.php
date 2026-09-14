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
		self::assertSame('foo' . \DIRECTORY_SEPARATOR . 'bar', PathUtils::normalize('foo/bar'));
		self::assertSame('foo' . \DIRECTORY_SEPARATOR . 'bar', PathUtils::normalize('foo\bar'));
	}

	public function testGetProtocol(): void
	{
		self::assertSame('https', PathUtils::getProtocol('https://foo.bar'));
		self::assertSame('file', PathUtils::getProtocol('file://foo.bar'));
		self::assertSame('C', PathUtils::getProtocol('C:\foo.bar'));
	}

	public function testRegisterResolver(): void
	{
		$resolver = static function (string $path): string {
			return $path . '#resolved';
		};

		PathUtils::registerResolver('test', $resolver);

		self::assertSame('test://foo/.bar#resolved', PathUtils::resolve('test://foo', './.bar'));
	}

	public function testIsRelative(): void
	{
		self::assertTrue(PathUtils::isRelative('foo/bar'));
		self::assertTrue(PathUtils::isRelative('foo\bar'));
		self::assertTrue(PathUtils::isRelative('c://foo/../bar'));
		self::assertFalse(PathUtils::isRelative('/foo/bar'));
		self::assertFalse(PathUtils::isRelative('\foo\bar'));
		self::assertTrue(PathUtils::isRelative('.env'));
		self::assertFalse(PathUtils::isRelative('/.env'));
	}

	public function testResolveReturnsACleanAbsolutePathAsItIs(): void
	{
		if ('/' !== \DIRECTORY_SEPARATOR) {
			self::markTestSkipped('Unix paths.');
		}

		self::assertSame('/var/www/app/', PathUtils::resolve('/foo', '/var/www/app/'));
		self::assertSame('/var/www//app', PathUtils::resolve('/foo', '/var/www//app'));
		self::assertSame('/var/.hidden/app', PathUtils::resolve('/foo', '/var/.hidden/app'));

		// dot segments are still resolved
		self::assertSame('/var/app', PathUtils::resolve('/foo', '/var/./app'));
		self::assertSame('/app', PathUtils::resolve('/foo', '/var/../app'));
		self::assertSame('/foo/app', PathUtils::resolve('/foo', 'app'));
	}

	public function testResolve(): void
	{
		$DS = \DIRECTORY_SEPARATOR;

		// LINUX
		self::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', 'baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', 'baz.txt'));
		self::assertSame($DS . 'baz.txt', PathUtils::resolve('/foo', '/baz.txt'));
		self::assertSame($DS . 'baz.txt', PathUtils::resolve('/foo/', '/baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', './baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', './baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', '../foo/baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', '../foo/baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', 'foo/baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', 'foo/baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', 'foo/../baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', 'foo/../baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo', 'foo/./baz.txt'));
		self::assertSame($DS . 'foo' . $DS . 'foo' . $DS . 'baz.txt', PathUtils::resolve('/foo/', 'foo/./baz.txt'));

		// DOS
		self::assertSame('C:\foo\baz.txt', PathUtils::resolve('C:\foo', 'baz.txt'));
		self::assertSame('C:\foo\baz.txt', PathUtils::resolve('C:\foo\\', 'baz.txt'));
		self::assertSame('/baz.txt', PathUtils::resolve('C:\foo', '\baz.txt'));
		self::assertSame('/baz.txt', PathUtils::resolve('C:\foo\\', '\baz.txt'));
		self::assertSame('C:\foo\baz.txt', PathUtils::resolve('C:\foo', '.\baz.txt'));
		self::assertSame('C:\foo\baz.txt', PathUtils::resolve('C:\foo\\', '.\baz.txt'));
		self::assertSame('C:\foo\baz.txt', PathUtils::resolve('C:\foo', '..\foo\baz.txt'));
		self::assertSame('C:\foo\baz.txt', PathUtils::resolve('C:\foo\\', '..\foo\baz.txt'));
		self::assertSame('C:\foo\foo\baz.txt', PathUtils::resolve('C:\foo', 'foo\baz.txt'));
		self::assertSame('C:\foo\foo\baz.txt', PathUtils::resolve('C:\foo\\', 'foo\baz.txt'));
		self::assertSame('C:\foo\baz.txt', PathUtils::resolve('C:\foo', 'foo\..\baz.txt'));
		self::assertSame('C:\foo\baz.txt', PathUtils::resolve('C:\foo\\', 'foo\..\baz.txt'));
		self::assertSame('C:\foo\foo\baz.txt', PathUtils::resolve('C:\foo', 'foo\.\baz.txt'));
		self::assertSame('C:\foo\foo\baz.txt', PathUtils::resolve('C:\foo\\', 'foo\.\baz.txt'));

		// HTTP(S)
		self::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo', 'baz.txt'));
		self::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo/', 'baz.txt'));
		self::assertSame('/baz.txt', PathUtils::resolve('https://foo', '/baz.txt'));
		self::assertSame('/baz.txt', PathUtils::resolve('https://foo/', '/baz.txt'));
		self::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo', './baz.txt'));
		self::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo/', './baz.txt'));
		self::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo', '../foo/baz.txt'));
		self::assertSame('https://foo/baz.txt', PathUtils::resolve('https://foo/', '../foo/baz.txt'));
	}
}
