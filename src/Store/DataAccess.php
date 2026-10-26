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
use ArrayIterator;
use IteratorAggregate;
use Override;

/**
 * Low-level single-key accessor used internally by Store and StoreNotEditable.
 *
 * Holds the underlying array/object by reference; no path parsing happens here.
 *
 * Object lookup priority (differs per method — both orders exist by design):
 *   has()   : ArrayAccess -> instance property -> static property -> constant
 *   get()   : instance property -> static property -> constant -> ArrayAccess
 *   set()   : instance property -> static property -> ArrayAccess -> new property
 *   next()  : instance property -> static property -> constant -> ArrayAccess
 *
 * When editable=false, set() and remove() are silent no-ops.
 *
 * @internal
 *
 * @template T of array|object
 *
 * @implements IteratorAggregate<int|string, mixed>
 */
final class DataAccess implements IteratorAggregate
{
	/**
	 * @var T
	 */
	private array|object $data;
	private bool $is_array;
	private bool $is_array_like;
	private bool $editable;

	/**
	 * DataAccess constructor.
	 *
	 * @param T $data
	 */
	public function __construct(array|object &$data = [], bool $editable = false)
	{
		$this->is_array      = \is_array($data);
		$this->is_array_like = $data instanceof ArrayAccess;
		$this->data          = &$data;
		$this->editable      = $editable;
	}

	/**
	 * DataAccess destructor.
	 */
	public function __destruct()
	{
		unset($this->data);
	}

	/**
	 * Checks if a given value can be used as data source.
	 */
	public static function typeOk(mixed $value): bool
	{
		return \is_array($value) || \is_object($value);
	}

	/**
	 * Returns the underlying data structure.
	 *
	 * @return T
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Checks whether $key exists in the underlying data.
	 *
	 * For objects, ArrayAccess is checked first (before instance properties).
	 * This differs from get()/set() which check instance properties first.
	 */
	public function has(mixed $key): bool
	{
		// array

		if ($this->is_array) {
			return isset($this->data[$key]) || \array_key_exists($key, $this->data);
		}

		// objects

		if ($this->is_array_like && isset($this->data[$key])) {
			return true;
		}

		if (isset($this->data->{$key}) || isset($this->data::${$key})) {
			return true;
		}

		return \defined($this->data::class . '::' . $key);
	}

	/**
	 * Gets the given key value.
	 */
	public function get(mixed $key, mixed $default = null): mixed
	{
		// array

		if ($this->is_array) {
			if (isset($this->data[$key]) || \array_key_exists($key, $this->data)) {
				return $this->data[$key];
			}

			return $default;
		}

		// objects

		if (isset($this->data->{$key})) {
			return $this->data->{$key};
		}

		if (isset($this->data::${$key})) {
			return $this->data::${$key};
		}

		$const = $this->data::class . '::' . $key;
		if (\defined($const)) {
			return \constant($const) ?? $default;
		}

		if ($this->is_array_like && isset($this->data[$key])) {
			return $this->data[$key];
		}

		return $default;
	}

	/**
	 * Sets $key to $value in the underlying data.
	 *
	 * Write priority for objects: existing instance property -> existing static
	 * property -> ArrayAccess storage -> new dynamic property (fallback).
	 * Silent no-op when editable=false.
	 *
	 * @return static enables fluent chaining; returns $this even when the write is a no-op
	 */
	public function set(mixed $key, mixed $value): static
	{
		if (!$this->editable) {
			return $this;
		}

		// array

		if ($this->is_array) {
			$this->data[$key] = $value;

			return $this;
		}

		// objects

		if (isset($this->data->{$key})) {
			$this->data->{$key} = $value;

			return $this;
		}

		if (isset($this->data::${$key})) {
			$this->data::${$key} = $value;

			return $this;
		}

		if ($this->is_array_like) {
			$this->data[$key] = $value;

			return $this;
		}

		// otherwise we add the property
		$this->data->{$key} = $value;

		return $this;
	}

	/**
	 * Removes a given key value from the store.
	 *
	 * In some conditions this will fails because of the way unset work:
	 * see: https://www.php.net/manual/en/function.unset.php
	 * can't remove class/object constants etc.
	 *
	 * @param mixed $key the key to remove; null is a no-op
	 *
	 * @return static enables fluent chaining; returns $this even when the write is a no-op
	 */
	public function remove(mixed $key): static
	{
		if (!$this->editable) {
			return $this;
		}

		if (null === $key) {
			return $this;
		}

		// array

		if ($this->is_array) {
			unset($this->data[$key]);

			return $this;
		}

		// objects

		if (isset($this->data->{$key})) {
			unset($this->data->{$key});

			return $this;
		}

		if ($this->is_array_like && isset($this->data[$key])) {
			unset($this->data[$key]);
		}

		return $this;
	}

	/**
	 * Returns a DataAccess wrapping the value at $key, or null if the key does
	 * not exist or its value cannot be traversed (not an array/object).
	 *
	 * @return null|$this
	 */
	public function next(mixed $key): ?self
	{
		// array

		if ($this->is_array) {
			if (isset($this->data[$key]) && self::typeOk($this->data[$key])) {
				return new self($this->data[$key], $this->editable);
			}

			return null;
		}

		// objects

		if (isset($this->data->{$key}) && self::typeOk($this->data->{$key})) {
			return new self($this->data->{$key}, $this->editable);
		}

		if (isset($this->data::${$key}) && self::typeOk($this->data::${$key})) {
			return new self($this->data::${$key}, $this->editable);
		}

		$const = $this->data::class . '::' . $key;

		if (\defined($const)) {
			$v = \constant($const);

			if (self::typeOk($v)) {
				return new self($v, $this->editable);
			}
		}

		if ($this->is_array_like && isset($this->data[$key]) && self::typeOk($this->data[$key])) {
			// we do this to prevent PHP complain about
			// indirect modification of overloaded element of ...
			$v = $this->data[$key];

			return new self($v, $this->editable);
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Iteration is a read-only snapshot, for an object as for an array: writing through the iterator
	 * reaches neither. Writing goes through `set()` and `remove()`, which reach the caller's own data
	 * at any depth and let PHP refuse what it must -- a private or a readonly property raises there.
	 * Handing the object itself to `ArrayIterator` used to write through, and bypassed exactly those
	 * guards: it overwrote a readonly property on a store that was not even editable, and dropped a
	 * write to a private one without a word. That is what PHP deprecated the construct for.
	 *
	 * An object contributes the properties visible from here, which is what `ArrayIterator` exposed.
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator(
			\is_object($this->data) ? \get_object_vars($this->data) : $this->data
		);
	}
}
