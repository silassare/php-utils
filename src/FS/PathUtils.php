<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\FS;

use InvalidArgumentException;

/**
 * Class PathUtils.
 *
 * original sources: https://gist.github.com/silassare/e048711c92ca9de0eca77e8e6d08a004
 */
class PathUtils
{
	public const DS = \DIRECTORY_SEPARATOR;

	/**
	 * resolve a given path according to a given root.
	 *
	 * @param string $root the root path
	 * @param string $path the path to resolve
	 *
	 * @return string the absolute path
	 */
	public static function resolve(string $root, string $path): string
	{
		$root = self::normalize($root);
		$path = self::normalize($path);

		if (self::isRelative($path)) {
			if ((self::DS === '/' && '/' === $path[0]) || \preg_match('~^[\\w]+:~', $path)) {
				// path start form the root
				// UNIX	-> /
				// DOS	-> D:

				$full_path = $path;
			} else {
				$full_path = $root . self::DS . $path;
			}

			$path = \preg_replace('~^(https?):[/]([^/])~', '$1://$2', self::job($full_path));
		}

		return $path;
	}

	/**
	 * normalize a given path according to OS specific directory separator.
	 *
	 * @param string $path the path to normalize
	 *
	 * @return string the normalized path
	 */
	public static function normalize(string $path): string
	{
		if (self::DS === '\\') {
			return \str_replace('/', '\\', $path);
		}

		return \str_replace('\\', '/', $path);
	}

	/**
	 * Checks if a given path is relative or not.
	 *
	 * @param string $path the path
	 *
	 * @return bool true if it is a relative path, false otherwise
	 */
	public static function isRelative(string $path): bool
	{
		return \preg_match('~^\\.{1,2}[/\\\\]?~', $path)
			   || \preg_match('~[/\\\\]\\.{1,2}[/\\\\]~', $path)
			   || \preg_match('~[/\\\\]\\.{1,2}$~', $path)
			   || \preg_match('~^[a-zA-Z0-9_.][^:]*$~', $path);
	}

	/**
	 * where the resolve job is done.
	 *
	 * @param string $path the path to normalize
	 *
	 * @return string the resolved path
	 */
	private static function job(string $path): string
	{
		$in  = \explode(self::DS, $path);
		$out = [];

		// preserve linux root first char '/' like in: /root/path/to/
		if (self::DS === $path[0]) {
			$out[] = '';
		}

		foreach ($in as $part) {
			// tmp part that have no value
			if (empty($part) || '.' === $part) {
				continue;
			}

			if ('..' !== $part) {
				// cool we found a new part
				$out[] = $part;
			} elseif (\count($out) > 0) {
				// going back up? sure
				\array_pop($out);
			} else {
				// now here we don't like
				throw new InvalidArgumentException(\sprintf('Climbing above root is dangerous: %s', $path));
			}
		}

		if (!\count($out)) {
			return self::DS;
		}

		if (1 === \count($out)) {
			$out[] = null;
		}

		return \implode(self::DS, $out);
	}
}
