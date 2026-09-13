<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Store;

use ArrayAccess;
use IteratorAggregate;

/**
 * Class Map.
 *
 * @template TOf of mixed
 *
 * @extends Store<array<string, TOf>>
 *
 * @implements ArrayAccess<string, TOf>
 * @implements IteratorAggregate<string, TOf>
 */
class Map extends Store implements ArrayAccess, IteratorAggregate
{
	/**
	 * Map constructor.
	 */
	public function __construct(array &$data = [])
	{
		$this->json_empty_array_is_object = true;

		parent::__construct($data);
	}

	/**
	 * Merges metadata as {@see Map::merge()} does, without its per-key cost when nothing needs it.
	 *
	 * `Map::merge()` reads and writes every key through a dot path, which made merging metadata most of
	 * the cost of building a table: a column's metadata is merged into its type, the type's into the
	 * column, and the type's again when the column is locked. When no key could read as a path (empty,
	 * or holding a `.` or a `[`) and no value is an object, `array_replace_recursive()` gives the same
	 * result.
	 */
	public function lazyMerge(array|self $source): void
	{
		$data = $source instanceof self ? $source->getData() : $source;

		if ([] === $data) {
			return;
		}

		$current = $this->getData();

		if (\is_array($data) && \is_array($current) && self::isPlain($data) && self::isPlain($current)) {
			$this->setData(\array_replace_recursive($current, $data));

			return;
		}

		$this->merge($source);
	}

	/**
	 * Whether every key reads the same as a dot path segment, and every value is a scalar, a null or
	 * such an array.
	 */
	private static function isPlain(array $data): bool
	{
		foreach ($data as $key => $value) {
			if (\is_string($key) && ('' === $key || false !== \strpbrk($key, '.['))) {
				return false;
			}

			if (\is_array($value)) {
				if (!self::isPlain($value)) {
					return false;
				}
			} elseif (\is_object($value)) {
				return false;
			}
		}

		return true;
	}
}
