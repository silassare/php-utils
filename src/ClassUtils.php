<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils;

/**
 * Class ClassUtils.
 */
class ClassUtils
{
	private static array $cache = [];

	/**
	 * Checks if a class is using a given trait.
	 *
	 * @param object|string $class
	 * @param string        $trait
	 *
	 * @return bool
	 */
	public static function hasTrait(string|object $class, string $trait): bool
	{
		$traits = self::getUsedTraitsDeep($class);

		return \array_key_exists($trait, $traits);
	}

	/**
	 * Deeply gets all used trait.
	 *
	 * @param object|string $class
	 * @param bool          $autoload
	 *
	 * @return array
	 */
	public static function getUsedTraitsDeep(string|object $class, bool $autoload = true): array
	{
		$key = \is_string($class) ? $class : \get_class($class);

		if (!isset(self::$cache['deep_traits'][$key])) {
			$traits = [];

			// Get all the traits of $class and its parent classes
			do {
				$class_name = \is_object($class) ? \get_class($class) : $class;
				if (\class_exists($class_name, $autoload)) {
					$traits = \array_merge(\class_uses($class, $autoload), $traits);
				}
			} while ($class = \get_parent_class($class));

			// Get traits of all parent traits
			$traits_to_search = $traits;
			while (!empty($traits_to_search)) {
				$new_traits       = \class_uses(\array_pop($traits_to_search), $autoload);
				$traits           = \array_merge($new_traits, $traits);
				$traits_to_search = \array_merge($new_traits, $traits_to_search);
			}

			self::$cache['deep_traits'][$key] = \array_unique($traits);
		}

		return self::$cache['deep_traits'][$key];
	}
}
