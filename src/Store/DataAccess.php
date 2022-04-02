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

/**
 * Class DataAccess.
 *
 * @internal
 */
final class DataAccess
{
	private array|object $data;
	private bool $is_array;
	private bool $is_array_like;
	private bool $editable;

	/**
	 * DataAccess constructor.
	 *
	 * @param array|object $data
	 * @param bool         $editable
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
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function typeOk(mixed $value): bool
	{
		return \is_array($value) || \is_object($value);
	}

	/**
	 * @return null|array|object
	 */
	public function getData(): object|array|null
	{
		if (!$this->editable) {
			return null;
		}

		return $this->data;
	}

	/**
	 * Checks if a given key is in the store.
	 *
	 * @param mixed $key
	 *
	 * @return bool
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
	 *
	 * @param mixed $key
	 * @param mixed $default
	 *
	 * @return mixed
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
	 * Sets value of a given key.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 *
	 * @return $this
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
	 * @param null|string $key
	 *
	 * @return $this
	 */
	public function remove(mixed $key): static
	{
		if (!$this->editable) {
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
	 * Gets next store.
	 *
	 * @param mixed $key
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
}
