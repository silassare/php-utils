<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\FS;

use FilesystemIterator;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUtils\FS\FSUtils;
use PHPUtils\FS\PathUtils;
use SplFileInfo;

/**
 * What a symlink means to a recursive walk, and who closes a directory handle.
 *
 * A symlink is not the directory it names, and `is_dir()` answers for the target: a walk that reads
 * it as a directory leaves the tree it was given, and one pointing at an ancestor never ends. The
 * cost is not only time -- each turn opens a directory, so it ends at the process's descriptor
 * limit.
 *
 * @internal
 */
#[CoversClass(FSUtils::class)]
final class FSUtilsTest extends TestCase
{
	private string $root;

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->root = \sys_get_temp_dir() . PathUtils::DS . \uniqid('oz_fs_', true);

		\mkdir($this->root, 0o775, true);
	}

	#[Override]
	protected function tearDown(): void
	{
		// By hand, and not with the class under test: a test must not clean up with the code it is
		// there to doubt.
		if (\is_dir($this->root)) {
			\exec('rm -rf ' . \escapeshellarg($this->root));
		}

		parent::tearDown();
	}

	public function testRemovingADirectoryNeverFollowsASymlinkOutOfIt(): void
	{
		// What a release directory holds: a link to state that lives elsewhere and must survive the
		// release being deleted. Reading the link as a directory deleted what was on the other side.
		$outside = $this->root . '/outside';
		$tree    = $this->root . '/tree';

		\mkdir($outside, 0o775, true);
		\mkdir($tree, 0o775, true);
		\file_put_contents($outside . '/keep.txt', 'this must survive');
		\file_put_contents($tree . '/own.txt', 'this must go');
		\symlink($outside, $tree . '/link');

		new FSUtils($this->root)->rmdir('tree');

		self::assertDirectoryDoesNotExist($tree);
		self::assertFileExists($outside . '/keep.txt');
		self::assertSame('this must survive', \file_get_contents($outside . '/keep.txt'));
	}

	public function testRemovingADirectoryWithASymlinkInItRaisesNothing(): void
	{
		// The same fault, where it is observable without a victim outside the tree: the walk
		// descended through the link, came back to a directory it had already emptied, and then
		// tried to `rmdir()` a symlink -- "Not a directory" -- which left the entry in place and the
		// parent not empty. It got there in the end, noisily, which is why the first assertion of
		// this test used to be that the tree was gone: true before the fix as well, and therefore
		// worth nothing.
		$tree = $this->root . '/tree';

		\mkdir($tree . '/deep', 0o775, true);
		\file_put_contents($tree . '/deep/a.txt', 'a');
		\symlink($tree, $tree . '/deep/loop');

		$raised = [];

		\set_error_handler(static function (int $code, string $message) use (&$raised): bool {
			$raised[] = $message;

			return true;
		});

		try {
			new FSUtils($this->root)->rmdir('tree');
		} finally {
			\restore_error_handler();
		}

		self::assertSame([], $raised, 'removing a tree holding a symlink must raise nothing');
		self::assertDirectoryDoesNotExist($tree);
	}

	public function testWalkingClosesEveryDirectoryItOpens(): void
	{
		$tree = $this->root . '/tree';

		\mkdir($tree . '/a/b/c', 0o775, true);
		\file_put_contents($tree . '/a/b/c/leaf.txt', 'leaf');

		$before = self::openDescriptors();
		$seen   = [];

		new FSUtils($this->root)->walk('tree', static function (string $name, string $path, bool $is_dir) use (&$seen): void {
			$seen[] = ($is_dir ? 'dir:' : 'file:') . $name;
		});

		self::assertContains('dir:a', $seen);
		self::assertContains('file:leaf.txt', $seen);

		// One handle per level was held for the whole walk and never released: the function opened a
		// directory and nothing closed it.
		self::assertSame($before, self::openDescriptors(), 'the walk must not leave a handle open');
	}

	public function testWalkingDoesNotFollowASymlinkedDirectory(): void
	{
		$tree = $this->root . '/tree';

		\mkdir($tree . '/real', 0o775, true);
		\file_put_contents($tree . '/real/inside.txt', 'inside');
		\symlink($tree, $tree . '/real/loop');

		$walks = 0;
		$links = [];

		new FSUtils($this->root)->walk(
			'tree',
			static function (string $name, string $path, bool $is_dir) use (&$walks, &$links): void {
				++$walks;

				if ('loop' === $name) {
					$links[] = $is_dir;
				}
			}
		);

		// It terminates, the link was reported, and it was not reported as a directory to descend.
		self::assertLessThan(20, $walks);
		self::assertSame([false], $links);
	}

	public function testCopyingKeepsASymlinkAsALinkInsteadOfWalkingIt(): void
	{
		$from = $this->root . '/from';

		\mkdir($from . '/real', 0o775, true);
		\file_put_contents($from . '/real/a.txt', 'a');
		\symlink($from, $from . '/real/loop');

		new FSUtils($this->root)->cp('from', $this->root . '/to');

		self::assertFileExists($this->root . '/to/real/a.txt');
		self::assertTrue(\is_link($this->root . '/to/real/loop'), 'the link must be copied as a link');
		self::assertSame($from, \readlink($this->root . '/to/real/loop'));
	}

	public function testTheIteratorListsASymlinkWithoutDescendingIntoIt(): void
	{
		// `canRemove()` reads `FilesFilter::find()`, which reads this iterator: a check that cannot
		// return is worse than no check, and neither SPL class keeps a visited set.
		$tree = $this->root . '/tree';

		\mkdir($tree . '/real', 0o775, true);
		\file_put_contents($tree . '/real/a.txt', 'a');
		\symlink($tree, $tree . '/real/loop');

		$paths = [];

		/** @var SplFileInfo $item */
		foreach (new FSUtils($tree)->getIterator() as $item) {
			$paths[] = $item->getPathname();

			if (\count($paths) > 50) {
				self::fail('the iterator followed the symlink and did not end');
			}
		}

		self::assertContains($tree . '/real/a.txt', $paths);
		self::assertContains($tree . '/real/loop', $paths);

		// Nothing below the link, and no dot entries either.
		self::assertNotContains($tree . '/real/loop/real', $paths);
		self::assertSame([], \array_filter($paths, static fn (string $p): bool => \str_ends_with($p, '.')));
	}

	public function testACallerThatWantsTheTargetsStillGetsThem(): void
	{
		$tree = $this->root . '/tree';

		\mkdir($tree . '/real', 0o775, true);
		\file_put_contents($tree . '/real/a.txt', 'a');
		\symlink($tree . '/real', $tree . '/mirror');

		$flags = FilesystemIterator::KEY_AS_PATHNAME
			| FilesystemIterator::CURRENT_AS_FILEINFO
			| FilesystemIterator::SKIP_DOTS
			| FilesystemIterator::FOLLOW_SYMLINKS;

		$paths = [];

		/** @var SplFileInfo $item */
		foreach (new FSUtils($tree)->getIterator($flags) as $item) {
			$paths[] = $item->getPathname();
		}

		self::assertContains($tree . '/mirror/a.txt', $paths);
	}

	/**
	 * The descriptors this process holds, as the only portable-enough place says.
	 */
	private static function openDescriptors(): int
	{
		$open = @\scandir('/proc/self/fd');

		if (false === $open) {
			self::markTestSkipped('A process counts its own descriptors through /proc, which this platform has not.');
		}

		// Less "." and "..", and less the handle this very count reads through.
		return \count($open) - 3;
	}
}
