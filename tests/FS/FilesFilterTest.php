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
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\FS\FSUtils;

/**
 * Class FilesFilterTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class FilesFilterTest extends TestCase
{
	private string $root;

	protected function setUp(): void
	{
		parent::setUp();

		$root = \sys_get_temp_dir() . DS . \uniqid('test', true);
		$fm   = new FSUtils($root);

		$fm->cd('Foo', true)
			->wf('a.txt', 'contains a')
			->cd('Baz', true)
			->wf('empty.txt')
			->wf('script.js', '// test js code')
			->cd('../Bar', true)
			->wf('1.txt', '1');

		$this->root = $root;
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		$fm = new FSUtils($this->root);

		$fm->rmdir('.');
	}

	public function testFind(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/a.txt',
			$foo . '/Bar',
			$foo . '/Bar/1.txt',
			$foo . '/Baz',
			$foo . '/Baz/empty.txt',
			$foo . '/Baz/script.js',
		], $list);
	}

	public function testNotIn(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->notIn('Baz')
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/a.txt',
			$foo . '/Bar',
			$foo . '/Bar/1.txt',
		], $list);
	}

	public function testIsEmpty(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->isEmpty()
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSame([
			$foo . '/Baz/empty.txt',
		], $list);
	}

	public function testIsNotEmpty(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->isNotEmpty()
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/a.txt',
			$foo . '/Bar',
			$foo . '/Bar/1.txt',
			$foo . '/Baz',
			$foo . '/Baz/script.js',
		], $list);
	}

	public function testIsNotReadable(): void
	{
		$fm = new FSUtils($this->root);

		$fm->wf($file = 'unreadable-file.txt', 'lorem ipsum');

		self::assertFalse($fm->filter()
			->isNotWritable()
			->check($file));

		\chmod($fm->resolve($file), 0500);

		self::assertTrue($fm->filter()
			->isNotWritable()
			->check($file));
	}

	public function testName(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->name('~\.txt~')
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/a.txt',
			$foo . '/Bar/1.txt',
			$foo . '/Baz/empty.txt',
		], $list);
	}

	public function testNotName(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->notName('~\.txt~')
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/Bar',
			$foo . '/Baz',
			$foo . '/Baz/script.js',
		], $list);
	}

	public function testGetError(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm     = new FSUtils($foo);
		$reg    = '~Bar~';
		$path   = $foo . '/Bar/1.txt';
		$filter = $fm->filter();

		self::assertFalse($filter
			->notPath($reg)
			->check($path));

		self::assertSame(\sprintf('The resource path "%s" should not match: %s', $path, $reg), $filter->getError());
	}

	public function testIsExecutable(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);

		self::assertTrue($fm->filter()
			->isExecutable()
			->check('Bar'));

		self::assertFalse($fm->filter()
			->isExecutable()
			->check('Baz/script.js'));
	}

	public function testIsWritable(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);

		self::assertTrue($fm->filter()
			->isWritable()
			->check('Bar'));

		$target = 'not-writable';
		$fm->mkdir($target, 0500);

		self::assertFalse($fm->filter()
			->isWritable()
			->check($target));

		\chmod($fm->resolve($target), 0700);
	}

	public function testIsReadable(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);

		self::assertTrue($fm->filter()
			->isReadable()
			->check('Bar'));

		$target = 'not-readable';
		$fm->mkdir($target, 0300);

		self::assertFalse($fm->filter()
			->isReadable()
			->check($target));

		\chmod($fm->resolve($target), 0700);
	}

	public function testIsNotWritable(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);

		self::assertFalse($fm->filter()
			->isNotWritable()
			->check('Bar'));

		$target = 'not-writable';
		$fm->mkdir($target, 0500);

		self::assertTrue($fm->filter()
			->isNotWritable()
			->check($target));

		\chmod($fm->resolve($target), 0700);
	}

	public function testAssert(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$reg  = '~Bar~';
		$path = $foo . '/Bar/1.txt';

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage(\sprintf('The resource path "%s" should not match: %s', $path, $reg));
		$fm->filter()
			->notPath($reg)
			->assert($path);
	}

	public function testNotPath(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->notPath('~Baz~')
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/Bar',
			$foo . '/Bar/1.txt',
			$foo . '/a.txt',
		], $list);
	}

	public function testPath(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->path('~Bar~')
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/Bar',
			$foo . '/Bar/1.txt',
		], $list);
	}

	public function testIn(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->in('Bar')
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/Bar',
			$foo . '/Bar/1.txt',
		], $list);
	}

	public function testIsNotExecutable(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);

		self::assertFalse($fm->filter()
			->isNotExecutable()
			->check('Bar'));

		$target = 'not-executable';
		$fm->mkdir($target, 0600);

		self::assertTrue($fm->filter()
			->isNotExecutable()
			->check($target));

		\chmod($fm->resolve($target), 0700);
	}

	public function testIsFile(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->isFile()
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/Bar/1.txt',
			$foo . '/Baz/empty.txt',
			$foo . '/Baz/script.js',
			$foo . '/a.txt',
		], $list);
	}

	public function testCheck(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);

		self::assertTrue($fm->filter()->exists()
			->check('Bar'));
		self::assertFalse($fm->filter()->exists()
			->check('Fizz'));
	}

	public function testExists(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm = new FSUtils($foo);

		self::assertTrue($fm->filter()
			->exists()
			->check('../Foo/../Foo/a.txt'));
		self::assertFalse($fm->filter()
			->exists()
			->check('../Foo/../Foo/b.txt'));
	}

	public function testIsDir(): void
	{
		$foo = $this->root . DS . 'Foo';

		$fm   = new FSUtils($foo);
		$list = [];
		foreach ($fm->filter()
			->isDir()
			->find() as $item) {
			$list[] = $item->getPathname();
		}

		self::assertSameArrayIgnoreOrder([
			$foo . '/Bar',
			$foo . '/Baz',
		], $list);
	}

	/**
	 * Checks if two array are same ignoring the content value order.
	 *
	 * This was added as file order depends on system and configuration.
	 *
	 * @param array $expected
	 * @param array $actual
	 */
	public static function assertSameArrayIgnoreOrder(array $expected, array $actual): void
	{
		\sort($expected);
		\sort($actual);

		self::assertSame($expected, $actual);
	}
}
