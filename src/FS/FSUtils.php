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
use IteratorAggregate;
use PHPUtils\Exceptions\RuntimeException;
use Psr\Http\Message\StreamInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class FSUtils.
 *
 * @implements IteratorAggregate<mixed, \SplFileInfo>
 */
class FSUtils implements IteratorAggregate
{
	/**
	 * Directories default permissions.
	 *
	 * Owner can rwx
	 * Group can rwx
	 * Other can ---
	 */
	public const DIRECTORY_PERMISSIONS = 0770;

	/**
	 * Files default permissions.
	 *
	 * Owner can rw
	 * Group can rw
	 * Other can ---
	 */
	public const FILE_PERMISSIONS = 0660;

	/**
	 * Read chunk length.
	 *
	 * @var int
	 */
	public const READ_CHUNK_LENGTH = 1024 * 8;

	/**
	 * Current directory root.
	 *
	 * @var string
	 */
	private string $root;

	/**
	 * FilesManager constructor.
	 *
	 * @param string $root the directory root path
	 */
	public function __construct(string $root = '.')
	{
		$this->root = PathUtils::resolve(__DIR__, $root);
	}

	/**
	 * Gets current root.
	 *
	 * @return string
	 */
	public function getRoot(): string
	{
		return $this->root;
	}

	/**
	 * Gets files filter class instances.
	 *
	 * @return FilesFilter
	 */
	public function filter(): FilesFilter
	{
		return new FilesFilter($this);
	}

	/**
	 * Resolve file to current root.
	 *
	 * @param string $target the path to resolve
	 *
	 * @return string
	 */
	public function resolve(string $target): string
	{
		return PathUtils::resolve($this->root, $target);
	}

	/**
	 * Returns a recursive iterator.
	 *
	 * @param int $flags
	 *
	 * @return RecursiveIteratorIterator
	 */
	public function getIterator(
		int $flags = FilesystemIterator::KEY_AS_PATHNAME
		| FilesystemIterator::CURRENT_AS_FILEINFO
		| FilesystemIterator::FOLLOW_SYMLINKS
	): RecursiveIteratorIterator {
		$this->filter()
			->isDir()
			->assert('.');

		$directory = new RecursiveDirectoryIterator($this->root, $flags);

		return new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
	}

	/**
	 * Change the current directory root path.
	 *
	 * @param string $path        the path to set as root
	 * @param bool   $auto_create to automatically create directory
	 *
	 * @return $this
	 */
	public function cd(string $path, bool $auto_create = false): self
	{
		$abs_path = $this->resolve($path);

		if (true === $auto_create && !\file_exists($abs_path)) {
			$this->mkdir($abs_path);
		}

		$this->filter()
			->isDir()
			->assert($abs_path);

		$this->root = $abs_path;

		return $this;
	}

	/**
	 * Creates a symbolic link to a given target.
	 *
	 * @param string $target the target path
	 * @param string $name   the link name
	 *
	 * @return $this
	 */
	public function ln(string $target, string $name): self
	{
		$abs_target      = $this->resolve($target);
		$abs_destination = $this->resolve($name);

		$this->filter()
			->exists()
			->assert($abs_target);
		$this->assertDoesNotExists($abs_destination);

		\symlink($abs_target, $abs_destination);

		return $this;
	}

	/**
	 * Copy file/directory.
	 *
	 * @param string           $from             the file/directory to copy
	 * @param null|string      $to               the destination path
	 * @param null|FilesFilter $filter           the files filter
	 * @param int              $dir_permissions  the directories permissions
	 * @param int              $file_permissions the files permissions
	 *
	 * @return $this
	 */
	public function cp(
		string $from,
		?string $to = null,
		?FilesFilter $filter = null,
		int $dir_permissions = self::DIRECTORY_PERMISSIONS,
		int $file_permissions = self::FILE_PERMISSIONS
	): self {
		$to = empty($to) ? $this->root : $to;

		$abs_from = $this->resolve($from);
		$abs_to   = $this->resolve($to);

		$this->filter()
			->isReadable()
			->assert($abs_from);

		if (\is_dir($abs_from)) {
			if (\file_exists($abs_to) && !\is_dir($abs_to)) {
				throw new RuntimeException(\sprintf(
					'Cannot overwrite "%s" with "%s", the resource exists and is not a directory.',
					$abs_to,
					$abs_from
				));
			}

			$this->recursivelyCopyDirAbsPath($abs_from, $abs_to, $filter, $dir_permissions, $file_permissions);
		} else {
			if (\is_dir($abs_to)) {
				$abs_to = PathUtils::resolve($abs_to, \basename($abs_from));
			}

			$this->mkdir(\dirname($abs_to), $dir_permissions);
			\copy($abs_from, $abs_to);

			// It is better to change the file permissions with chmod() after creating the file.
			// https://www.php.net/manual/en/function.umask.php#75459
			\chmod($abs_to, $file_permissions);
		}

		return $this;
	}

	/**
	 * Copy a file from a url to a destination on the server.
	 *
	 * @param string      $url  The file url to copy
	 * @param null|string $to   The destination path
	 * @param int         $mode The file mode
	 *
	 * @return $this
	 */
	public function download(string $url, ?string $to = null, int $mode = self::FILE_PERMISSIONS): self
	{
		if (!\filter_var($url, \FILTER_VALIDATE_URL)) {
			throw new RuntimeException(\sprintf('Invalid url: "%s".', $url));
		}

		if (empty($to)) {
			$to = \basename($url);
		}

		$destination = $this->resolve($to);

		$this->assertDoesNotExists($destination);

		$remote_file = \fopen($url, 'r');

		if (!$remote_file) {
			throw new RuntimeException(\sprintf('Can\'t open file at: "%s".', $url));
		}

		$fc = \fopen($destination, 'w');

		if (!$fc) {
			throw new RuntimeException(\sprintf('Can\'t open file for writing at: "%s".', $destination));
		}

		while (($line = \fread($remote_file, self::READ_CHUNK_LENGTH)) !== false) {
			\fwrite($fc, $line);
		}

		\fclose($fc);

		\fclose($remote_file);

		// It is better to change the file permissions with chmod() after creating the file.
		// https://www.php.net/manual/en/function.umask.php#75459
		\chmod($destination, $mode);

		return $this;
	}

	/**
	 * Walks in a given directory.
	 *
	 * When the current file is a directory, the walker callable
	 * should return false in order to prevent recursion.
	 *
	 * @param string   $dir_path directory path
	 * @param callable $walker   called for each file and directory
	 *
	 * @return $this
	 */
	public function walk(string $dir_path, callable $walker): self
	{
		$abs_dir = $this->resolve($dir_path);
		$this->resolve($dir_path);

		$this->filter()
			->isDir()
			->isReadable()
			->assert($abs_dir);

		$res = \opendir($abs_dir);

		while (false !== ($file = \readdir($res))) {
			if ('.' !== $file && '..' !== $file) {
				$path = PathUtils::resolve($abs_dir, $file);

				if (\is_dir($path)) {
					$ret = $walker($file, $path, true);

					if (false !== $ret) {
						$this->walk($path, $walker);
					}
				} elseif (\is_file($path)) {
					$walker($file, $path, false);
				}
			}
		}

		return $this;
	}

	/**
	 * Gets a given directory content info.
	 *
	 * @param string $path the directory path
	 *
	 * @return array
	 */
	public function info(string $path): array
	{
		$abs_path = $this->resolve($path);

		$this->filter()
			->isDir()
			->assert($abs_path);

		$result = [];
		$files  = \scandir($abs_path);

		foreach ($files as $entry) {
			if ('.' !== $entry && '..' !== $entry) {
				$abs_entry = PathUtils::resolve($abs_path, $entry);

				$stat     = \stat($abs_entry);
				$result[] = [
					'mtime'     => $stat['mtime'],
					'size'      => $stat['size'],
					'name'      => \basename($abs_entry),
					'directory' => \is_dir($abs_entry),
					'read'      => \is_readable($abs_entry),
					'write'     => \is_writable($abs_entry),
					'execute'   => \is_executable($abs_entry),
					'delete'    => $this->canRemove($abs_entry),
				];
			}
		}

		return $result;
	}

	/**
	 * Get full path info.
	 *
	 * Thanks to: https://www.php.net/manual/en/function.stat.php#87241
	 *
	 * @param $path
	 *
	 * @return array|false
	 */
	public function fullPathInfo($path): array|false
	{
		$abs_path = $this->resolve($path);

		\clearstatcache();

		$ss = \stat($abs_path);

		if (!$ss) {
			return false;
		} // Couldn't stat file

		$ts = [
			0010000 => 'pfifo',
			0020000 => 'cchar',
			0040000 => 'ddir',
			0060000 => 'bblock',
			0100000 => '-file',
			0120000 => 'llink',
			0140000 => 'ssocket',
		];

		$p = $ss['mode'];
		$t = \decoct($p & 0170000); // File Encoding Bit

		/** @var int $t_dec */
		$t_dec  = \octdec($t);
		$str    = (\array_key_exists($t_dec, $ts)) ? $ts[$t_dec][0] : 'u';
		$str .= (($p & 0x0100) ? 'r' : '-') . (($p & 0x0080) ? 'w' : '-');
		$str .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x') : (($p & 0x0800) ? 'S' : '-'));
		$str .= (($p & 0x0020) ? 'r' : '-') . (($p & 0x0010) ? 'w' : '-');
		$str .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x') : (($p & 0x0400) ? 'S' : '-'));
		$str .= (($p & 0x0004) ? 'r' : '-') . (($p & 0x0002) ? 'w' : '-');
		$str .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x') : (($p & 0x0200) ? 'T' : '-'));

		$type = \substr($ts[$t_dec], 1);
		$s    = [
			'perms' => [
				'umask'     => \sprintf('%04o', \umask()),
				'human'     => $str,
				'octal1'    => \sprintf('%o', $p & 000777),
				'octal2'    => \sprintf('0%o', 0777 & $p),
				'decimal'   => \sprintf('%04o', $p),
				'fileperms' => \fileperms($abs_path),
				'mode'      => $p,
			],

			'owner' => [
				'fileowner' => $ss['uid'],
				'filegroup' => $ss['gid'],
				'owner'     => (\function_exists('posix_getpwuid')) ?
					\posix_getpwuid($ss['uid']) : '',
				'group'     => (\function_exists('posix_getgrgid')) ?
					\posix_getgrgid($ss['gid']) : '',
			],

			'file' => [
				'filename' => $abs_path,
				'realpath' => (\realpath($abs_path) !== $abs_path) ? \realpath($abs_path) : '',
				'dirname'  => \dirname($abs_path),
				'basename' => \basename($abs_path),
			],

			'filetype' => [
				'type'          => $type,
				'type_octal'    => \sprintf('%07o', $t_dec),
				'is_file'       => \is_file($abs_path),
				'is_dir'        => \is_dir($abs_path),
				'is_link'       => \is_link($abs_path),
				'is_readable'   => \is_readable($abs_path),
				'is_writable'   => \is_writable($abs_path),
				'is_executable' => \is_executable($abs_path),
			],

			'size' => [
				'size'       => $ss['size'], // Size of file, in bytes.
				'blocks'     => $ss['blocks'], // Number 512-byte blocks allocated
				'block_size' => $ss['blksize'], // Optimal block size for I/O.
			],

			'time' => [
				'mtime' => $ss['mtime'], // Time of last modification
				'atime' => $ss['atime'], // Time of last access
				'ctime' => $ss['ctime'], // Time of last status change
			],

			'device' => [
				'device'        => $ss['dev'], // Device
				'device_number' => $ss['rdev'], // Device number, if device.
				'inode'         => $ss['ino'], // File serial number
				'link_count'    => $ss['nlink'], // link count
				'link_to'       => 'link' === $type ? \readlink($abs_path) : '',
			],
		];

		\clearstatcache();

		return $s;
	}

	/**
	 * Write content to file.
	 *
	 * @param string                 $path    the file path
	 * @param StreamInterface|string $content the content
	 * @param string                 $mode    php file write mode
	 *
	 * @return $this
	 */
	public function wf(string $path, StreamInterface|string $content = '', string $mode = 'wb'): self
	{
		$abs_path = $this->resolve($path);

		$f = \fopen($abs_path, $mode);

		if (!$f) {
			throw new RuntimeException(\sprintf('Can\'t open file for writing at: "%s".', $abs_path));
		}

		if ($content instanceof StreamInterface) {
			$content->rewind();

			while (!$content->eof()) {
				\fwrite($f, $content->read(self::READ_CHUNK_LENGTH));
			}
		} else {
			\fwrite($f, $content);
		}

		\fclose($f);

		return $this;
	}

	/**
	 * Appends data to file.
	 *
	 * @param string                 $path
	 * @param StreamInterface|string $data
	 *
	 * @return $this
	 */
	public function append(string $path, StreamInterface|string $data): self
	{
		return $this->wf($path, $data, 'ab');
	}

	/**
	 * Prepends data to file.
	 *
	 * @param string                 $path
	 * @param StreamInterface|string $data
	 *
	 * @return $this
	 */
	public function prepend(string $path, StreamInterface|string $data): self
	{
		$abs_path = $this->resolve($path);

		$this->filter()
			->isFile()
			->isReadable()
			->isWritable()
			->assert($abs_path);

		$temp_path = \tempnam(\sys_get_temp_dir(), 'pu_fs_');

		$fr      = \fopen($abs_path, 'r');
		$temp_fw = \fopen($temp_path, 'w');

		if (!$fr) {
			throw new RuntimeException(\sprintf('Can\'t open file for reading at: "%s".', $abs_path));
		}
		if (!$temp_fw) {
			throw new RuntimeException(\sprintf('Can\'t open file for writing at: "%s".', $temp_path));
		}

		if ($data instanceof StreamInterface) {
			$data->rewind();

			while (!$data->eof()) {
				\fwrite($temp_fw, $data->read(self::READ_CHUNK_LENGTH));
			}
		} else {
			\fwrite($temp_fw, $data);
		}

		while (($line = \fread($fr, self::READ_CHUNK_LENGTH)) !== false) {
			\fwrite($temp_fw, $line);
		}

		\fclose($fr);
		\fclose($temp_fw);

		\rename($temp_path, $abs_path);

		return $this;
	}

	/**
	 * Creates a directory at a given path with all parents directories.
	 *
	 * @param string $path The directory path
	 * @param int    $mode The mode
	 *
	 * @return $this
	 */
	public function mkdir(string $path, int $mode = self::DIRECTORY_PERMISSIONS): self
	{
		$abs_path = $this->resolve($path);

		if (\file_exists($abs_path) && !\is_dir($abs_path)) {
			throw new RuntimeException(\sprintf(
				'Cannot overwrite "%s", the resource exists and is not a directory.',
				$abs_path
			));
		}

		if (!\is_dir($abs_path) && false === @\mkdir($abs_path, $mode, true) && !\is_dir($abs_path)) {
			throw new RuntimeException(\sprintf('Cannot create directory "%s".', $abs_path));
		}

		// It is better to change the file permissions with chmod() after creating the file.
		// https://www.php.net/manual/en/function.umask.php#75459
		\chmod($abs_path, $mode);

		return $this;
	}

	/**
	 * Removes regular file at a given path.
	 *
	 * @param string $path the file path
	 *
	 * @return $this
	 */
	public function rm(string $path): self
	{
		$abs_path = $this->resolve($path);

		$this->filter()
			->isFile()
			->isReadable()
			->isWritable()
			->assert($abs_path);

		\unlink($abs_path);

		return $this;
	}

	/**
	 * Removes directory at a given path.
	 *
	 * If a filter is provided only files that apply to the filter will be removed.
	 *
	 * @param string           $path   the directory path
	 * @param null|FilesFilter $filter the files filter
	 *
	 * @return $this
	 */
	public function rmdir(string $path, ?FilesFilter $filter = null): self
	{
		$abs_path = $this->resolve($path);

		$this->filter()
			->isDir()
			->isReadable()
			->isWritable()
			->assert($abs_path);

		$this->recursivelyRemoveDirAbsPath($abs_path, $filter);

		return $this;
	}

	/**
	 * Checks if a path can be removed.
	 *
	 * Removable means
	 *  - It is a regular file and it's readable and writeable
	 *  - Or it is a directory and all it's subdirectories or files ar readable and writeable
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function canRemove(string $path): bool
	{
		$path = $this->resolve($path);

		if (\is_file($path)) {
			return \is_readable($path) && \is_writable($path);
		}

		return null === $this->cd($path)
			->filter()
			->isNotReadable()
			->isNotWritable()
			->find()
			->current();
	}

	/**
	 * Checks if the current root is equal to a given path.
	 *
	 * @param string $path the path to check
	 *
	 * @return bool
	 */
	public function isSelf(string $path): bool
	{
		$path = $this->resolve($path);

		return $path === $this->root;
	}

	/**
	 * Checks if the current root is parent of a given path.
	 *
	 * @param string $path the path to check
	 *
	 * @return bool
	 */
	public function isParentOf(string $path): bool
	{
		$path = $this->resolve($path);

		if ($path === $this->root) {
			return false;
		}

		return \str_starts_with($path, $this->root);
	}

	/**
	 * Asserts the path existence.
	 *
	 * @param string $path the resource path
	 */
	public function assertExists(string $path): void
	{
		$this->filter()
			->exists()
			->assert($this->resolve($path));
	}

	/**
	 * Asserts the path does not exists.
	 *
	 * @param string $abs_path the resource absolute path
	 */
	public function assertDoesNotExists(string $abs_path): void
	{
		if (\file_exists($abs_path)) {
			throw new RuntimeException(\sprintf('Cannot overwrite existing resource: %s', $abs_path));
		}
	}

	/**
	 * Removes directory recursively.
	 *
	 * @param string       $path   the directory path
	 * @param ?FilesFilter $filter the files filter
	 */
	private function recursivelyRemoveDirAbsPath(string $path, ?FilesFilter $filter = null): void
	{
		$files = \scandir($path);

		foreach ($files as $file) {
			if (!('.' === $file || '..' === $file)) {
				$src = $path . \DIRECTORY_SEPARATOR . $file;

				if ($filter && !$filter->check($src)) {
					continue;
				}

				if (\is_dir($src)) {
					$this->recursivelyRemoveDirAbsPath($src, $filter);
				} else {
					\unlink($src);
				}
			}
		}

		if (!$filter || $filter->check($path)) {
			\rmdir($path);
		}
	}

	/**
	 * Copy directory recursively.
	 *
	 * @param string           $source
	 * @param string           $destination
	 * @param null|FilesFilter $filter
	 * @param int              $dir_permissions
	 * @param int              $file_permissions
	 */
	private function recursivelyCopyDirAbsPath(
		string $source,
		string $destination,
		?FilesFilter $filter = null,
		int $dir_permissions = self::DIRECTORY_PERMISSIONS,
		int $file_permissions = self::FILE_PERMISSIONS
	): void {
		$this->mkdir($destination, $dir_permissions);

		$res = \opendir($source);

		while (false !== ($file = \readdir($res))) {
			if ('.' !== $file && '..' !== $file) {
				$from = $source . \DIRECTORY_SEPARATOR . $file;
				$to   = $destination . \DIRECTORY_SEPARATOR . $file;

				if ($filter && !$filter->check($from)) {
					continue;
				}

				if (\is_dir($from)) {
					$this->recursivelyCopyDirAbsPath($from, $to, $filter, $dir_permissions, $file_permissions);
				} else {
					if (\file_exists($to) && !\is_file($to)) {
						throw new RuntimeException(\sprintf(
							'Cannot overwrite "%s" the resource exists and is not a file.',
							$to
						));
					}

					$this->cp($from, $to, $filter, $dir_permissions, $file_permissions);
				}
			}
		}

		\closedir($res);
	}
}
