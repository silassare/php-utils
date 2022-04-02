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
use PHPUtils\FS\FSUtils;

/**
 * Class FilesFilterTest.
 *
 * @internal
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

		static::assertSame([
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

		static::assertSame([
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

		static::assertSame([
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

		static::assertSame([
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

		static::assertFalse($fm->filter()
			->isNotWritable()
			->check($file));

		\chmod($fm->resolve($file), 0500);

		static::assertTrue($fm->filter()
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

		static::assertSame([
			$foo . '/a.txt',
			$foo . '/Bar/1.txt',
			$foo . '/Baz/empty.txt',
		], $list);
	}

	public function testNotName(): void
	{
	}

	public function testGetError(): void
	{
	}

	public function testIsExecutable(): void
	{
	}

	public function testIsWritable(): void
	{
	}

	public function testIsReadable(): void
	{
	}

	public function testIsNotWritable(): void
	{
	}

	public function testAssert(): void
	{
	}

	public function testNotPath(): void
	{
	}

	public function testPath(): void
	{
	}

	public function testIn(): void
	{
	}

	public function testIsNotExecutable(): void
	{
	}

	public function testIsFile(): void
	{
	}

	public function testCheck(): void
	{
	}

	public function testExists(): void
	{
	}

	public function testIsDir(): void
	{
	}
}
