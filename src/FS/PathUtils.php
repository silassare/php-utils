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
	 * @var array<string,callable(string):(false|string)>
	 */
	private static array $resolvers = [];

	/**
	 * Register a new resolver.
	 *
	 * @param callable(string):(false|string) $resolver
	 */
	public static function registerResolver(string $protocol, callable $resolver): void
	{
		self::$resolvers[$protocol] = $resolver;
	}

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
			if ((self::DS === '/' && '/' === $path[0]) || \preg_match('~^\w+:~', $path)) {
				// path start form the root or has a protocol
				// UNIX	-> /
				// DOS	-> D:
				// PROTOCOL -> http: or https:

				$full_path = $path;
			} else {
				$full_path = $root . self::DS . $path;
			}

			$path = self::job($full_path);
		}

		$protocol = self::getProtocol($path);

		if ($protocol) {
			// when there is a protocol and the protocol is a windows drive
			// we replace / with \
			// otherwise we replace \ with /
			if (\preg_match('~^[a-zA-Z]$~', $protocol)) {
				$path = \str_replace('/', '\\', $path);
			} else {
				$path = \str_replace('\\', '/', $path);
				// fix protocol part "https:/foo" become "https://foo"
				$path = \preg_replace('~^(\w+):/([^/])~', '$1://$2', $path);
			}

			if (isset(self::$resolvers[$protocol])) {
				$resolver = self::$resolvers[$protocol];
				$resolved = $resolver($path);

				if ($resolved) {
					return $resolved;
				}
			}
		}

		return $path;
	}

	/**
	 * Normalize a given path according to OS specific directory separator.
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
		return \preg_match('~^\.{1,2}[/\\\]?~', $path)
			|| \preg_match('~[/\\\]\.{1,2}[/\\\]~', $path)
			|| \preg_match('~[/\\\]\.{1,2}$~', $path)
			|| \preg_match('~^[a-zA-Z0-9_.][^:]*$~', $path);
	}

	/**
	 * Extract the protocol from a path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public static function getProtocol(string $path): string
	{
		if (\preg_match('~^(\w+):~', $path, $matches)) {
			return $matches[1];
		}

		return '';
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
