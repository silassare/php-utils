<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\FS;

use FilesystemIterator;
use Generator;
use InvalidArgumentException;
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Str;
use SplFileInfo;

/**
 * Class FilesFilter.
 */
class FilesFilter
{
	protected ?bool $should_exists = null;

	protected ?bool $file = null;

	protected ?bool $dir = null;

	protected ?bool $readable = null;

	protected ?bool $writable = null;

	protected ?bool $executable = null;

	protected array $match_names = [];

	protected array $match_paths = [];

	protected array $not_match_names = [];

	protected array $not_match_paths = [];

	protected array $in_dirs = [];

	protected array $not_in_dirs = [];

	protected FSUtils $fs;

	private ?bool $empty = null;

	private string $error = 'OK';

	/**
	 * FilesFilter constructor.
	 *
	 * @param FSUtils $fs
	 */
	public function __construct(FSUtils $fs)
	{
		$this->fs = $fs;
	}

	/**
	 * Require matching paths to exist on the filesystem.
	 *
	 * @return $this
	 */
	public function exists(): self
	{
		$this->should_exists = true;

		return $this;
	}

	/**
	 * Require matching paths to be a regular file.
	 *
	 * @return $this
	 */
	public function isFile(): self
	{
		$this->file = true;

		return $this;
	}

	/**
	 * Require matching paths to be a directory.
	 *
	 * @return $this
	 */
	public function isDir(): self
	{
		$this->dir = true;

		return $this;
	}

	/**
	 * Require matching paths to be readable.
	 *
	 * @return $this
	 */
	public function isReadable(): self
	{
		$this->readable = true;

		return $this;
	}

	/**
	 * Require matching paths to be NOT readable.
	 *
	 * @return $this
	 */
	public function isNotReadable(): self
	{
		$this->readable = false;

		return $this;
	}

	/**
	 * Require matching paths to be writable.
	 *
	 * @return $this
	 */
	public function isWritable(): self
	{
		$this->writable = true;

		return $this;
	}

	/**
	 * Require matching paths to be NOT writable.
	 *
	 * @return $this
	 */
	public function isNotWritable(): self
	{
		$this->writable = false;

		return $this;
	}

	/**
	 * Require matching paths to be executable.
	 *
	 * @return $this
	 */
	public function isExecutable(): self
	{
		$this->executable = true;

		return $this;
	}

	/**
	 * Require matching paths to be NOT executable.
	 *
	 * @return $this
	 */
	public function isNotExecutable(): self
	{
		$this->executable = false;

		return $this;
	}

	/**
	 * Filter paths that match the given filename regex pattern.
	 *
	 * Only the basename of the path is matched against the pattern.
	 *
	 * @param non-empty-string $pattern a valid PCRE regular expression (e.g. '~\.php$~')
	 *
	 * @return $this
	 */
	public function name(string $pattern): self
	{
		if (false === \preg_match($pattern, '')) {
			throw new InvalidArgumentException(\sprintf('invalid regular expression: %s', $pattern));
		}

		$this->match_names[] = $pattern;

		return $this;
	}

	/**
	 * Exclude paths whose filename matches the given regex pattern.
	 *
	 * Only the basename of the path is matched against the pattern.
	 *
	 * @param non-empty-string $pattern a valid PCRE regular expression (e.g. '~\.php$~')
	 *
	 * @return $this
	 */
	public function notName(string $pattern): self
	{
		if (false === \preg_match($pattern, '')) {
			throw new InvalidArgumentException(\sprintf('invalid regular expression: %s', $pattern));
		}

		$this->not_match_names[] = $pattern;

		return $this;
	}

	/**
	 * Filter paths whose full absolute path matches the given regex pattern.
	 *
	 * @param non-empty-string $pattern a valid PCRE regular expression
	 *
	 * @return $this
	 */
	public function path(string $pattern): self
	{
		if (false === \preg_match($pattern, '')) {
			throw new InvalidArgumentException(\sprintf('invalid regular expression: %s', $pattern));
		}

		$this->match_paths[] = $pattern;

		return $this;
	}

	/**
	 * Exclude paths whose full absolute path matches the given regex pattern.
	 *
	 * @param non-empty-string $pattern a valid PCRE regular expression
	 *
	 * @return $this
	 */
	public function notPath(string $pattern): self
	{
		if (false === \preg_match($pattern, '')) {
			throw new InvalidArgumentException(\sprintf('invalid regular expression: %s', $pattern));
		}

		$this->not_match_paths[] = $pattern;

		return $this;
	}

	/**
	 * Restrict matches to paths located inside the given directory.
	 *
	 * The directory is resolved relative to the current FSUtils root.
	 * Multiple calls are combined with OR logic.
	 *
	 * @param string $dir the directory path (resolved relative to current root)
	 *
	 * @return $this
	 */
	public function in(string $dir): self
	{
		$this->in_dirs[] = $this->fs->resolve($dir);

		return $this;
	}

	/**
	 * Exclude paths located inside the given directory.
	 *
	 * The directory is resolved relative to the current FSUtils root.
	 * Multiple calls are combined with AND logic (each call adds another exclusion).
	 *
	 * @param string $dir the directory path (resolved relative to current root)
	 *
	 * @return $this
	 */
	public function notIn(string $dir): self
	{
		$this->not_in_dirs[] = $this->fs->resolve($dir);

		return $this;
	}

	/**
	 * Require matching paths to be empty (zero-size file or empty directory).
	 *
	 * @return $this
	 */
	public function isEmpty(): self
	{
		$this->empty = true;

		return $this;
	}

	/**
	 * Require matching paths to be NOT empty (non-zero-size file or non-empty directory).
	 *
	 * @return $this
	 */
	public function isNotEmpty(): self
	{
		$this->empty = false;

		return $this;
	}

	/**
	 * Tests whether a given path satisfies all configured filter conditions.
	 *
	 * The path is resolved relative to the current FSUtils root before evaluation.
	 * If the check fails, the failure reason is available via {@see getError()}.
	 *
	 * @param string $path the path to evaluate (resolved relative to current root)
	 *
	 * @return bool true if the path matches all conditions, false otherwise
	 */
	public function check(string $path): bool
	{
		$abs_path = $this->fs->resolve($path);
		$name     = \basename($abs_path);

		if (true === $this->should_exists && !\file_exists($abs_path)) {
			$this->error = \sprintf('"%s" does not exists.', $abs_path);

			return false;
		}

		if (true === $this->file && !\is_file($abs_path)) {
			if (\file_exists($abs_path)) {
				$this->error = \sprintf('"%s" is not a valid file.', $abs_path);
			} else {
				$this->error = \sprintf('no file found at "%s".', $abs_path);
			}

			return false;
		}

		if (true === $this->dir && !\is_dir($abs_path)) {
			if (\file_exists($abs_path)) {
				$this->error = \sprintf('"%s" is not a valid directory.', $abs_path);
			} else {
				$this->error = \sprintf('no directory found at "%s".', $abs_path);
			}

			return false;
		}

		if (true === $this->readable && !\is_readable($abs_path)) {
			$this->error = \sprintf('The resource at "%s" is not readable.', $abs_path);

			return false;
		}

		if (false === $this->readable && \is_readable($abs_path)) {
			$this->error = \sprintf('The resource at "%s" should not be readable.', $abs_path);

			return false;
		}

		if (true === $this->writable && !\is_writable($abs_path)) {
			$this->error = \sprintf('The resource at "%s" is not writeable.', $abs_path);

			return false;
		}

		if (false === $this->writable && \is_writable($abs_path)) {
			$this->error = \sprintf('The resource at "%s" should not be writeable.', $abs_path);

			return false;
		}

		if (true === $this->executable && !\is_executable($abs_path)) {
			$this->error = \sprintf('The resource at "%s" is not executable.', $abs_path);

			return false;
		}

		if (false === $this->executable && \is_executable($abs_path)) {
			$this->error = \sprintf('The resource at "%s" should not be executable.', $abs_path);

			return false;
		}

		if (true === $this->empty && !self::isPathEmpty($abs_path)) {
			$this->error = \sprintf('The resource at "%s" is not empty.', $abs_path);

			return false;
		}

		if (false === $this->empty && self::isPathEmpty($abs_path)) {
			$this->error = \sprintf('The resource at "%s" should not be empty.', $abs_path);

			return false;
		}

		if ($this->not_in_dirs) {
			foreach ($this->not_in_dirs as $dir) {
				if (Str::hasPrefix($abs_path, $dir)) {
					$this->error = \sprintf('The resource at "%s" is in excluded directory: "%s".', $abs_path, $dir);

					return false;
				}
			}
		}

		if ($this->in_dirs) {
			$inDir = false;

			foreach ($this->in_dirs as $dir) {
				if (Str::hasPrefix($abs_path, $dir)) {
					$inDir = true;
				}
			}

			if (!$inDir) {
				$this->error = \sprintf('The resource at "%s" is not in any allowed directory.', $abs_path);

				return false;
			}
		}

		if ($this->not_match_paths) {
			foreach ($this->not_match_paths as $reg) {
				if (\preg_match($reg, $abs_path)) {
					$this->error = \sprintf('The resource path "%s" should not match: %s', $abs_path, $reg);

					return false;
				}
			}
		}

		if ($this->not_match_names) {
			foreach ($this->not_match_names as $reg) {
				if (\preg_match($reg, $name)) {
					$this->error = \sprintf(
						'The resource at "%s" with name "%s" should not match: %s',
						$abs_path,
						$name,
						$reg
					);

					return false;
				}
			}
		}

		if ($this->match_paths) {
			foreach ($this->match_paths as $reg) {
				if (\preg_match($reg, $abs_path)) {
					return true;
				}
			}

			$this->error = \sprintf('The resource at "%s" does not match any given path pattern.', $abs_path);

			return false;
		}

		if ($this->match_names) {
			foreach ($this->match_names as $reg) {
				if (\preg_match($reg, $name)) {
					return true;
				}
			}

			$this->error = \sprintf('The resource at "%s" does not match any given name pattern.', $abs_path);

			return false;
		}

		return true;
	}

	/**
	 * Find all file/dir that match the specified filters.
	 *
	 * The current key is the current file path name
	 * The current value is an instance of `\SplFileInfo::class`
	 *
	 * @return Generator
	 */
	public function find(): Generator
	{
		$flags = FilesystemIterator::KEY_AS_PATHNAME
				 | FilesystemIterator::CURRENT_AS_FILEINFO
				 | FilesystemIterator::FOLLOW_SYMLINKS
				 | FilesystemIterator::SKIP_DOTS;

		/** @var SplFileInfo $item */
		foreach ($this->fs->getIterator($flags) as $item) {
			if ($this->check($path = $item->getPathname())) {
				yield $path => $item;
			}
		}
	}

	/**
	 * Asserts that a given path satisfies all configured filter conditions.
	 *
	 * Like {@see check()}, but throws a RuntimeException with the failure reason
	 * if the path does not match.
	 *
	 * @param string $path the path to assert (resolved relative to current root)
	 *
	 * @return $this
	 *
	 * @throws RuntimeException when the path fails any filter condition
	 */
	public function assert(string $path): self
	{
		if (!$this->check($path)) {
			throw new RuntimeException($this->error);
		}

		return $this;
	}

	/**
	 * Returns the error message from the last failed {@see check()} or {@see assert()} call.
	 *
	 * Returns 'OK' when the last check passed.
	 *
	 * @return string
	 */
	public function getError(): string
	{
		return $this->error;
	}

	/**
	 * @param string $abs_path
	 *
	 * @return bool
	 */
	private static function isPathEmpty(string $abs_path): bool
	{
		if (!\file_exists($abs_path)) {
			return true;
		}

		if (!\is_readable($abs_path)) {
			return false; // we can't read file/dir so we suppose it's not empty
		}

		if (\is_file($abs_path)) {
			return 0 === \filesize($abs_path);
		}

		if (\is_dir($abs_path)) {
			$handle   = \opendir($abs_path);
			$is_empty = true;

			while (false !== ($entry = \readdir($handle))) {
				if ('.' !== $entry && '..' !== $entry) {
					$is_empty = false;

					break;
				}
			}

			\closedir($handle);

			return $is_empty;
		}

		return false; // we don't know what is located at the given path
	}
}
