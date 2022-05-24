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
 * Class EnvParser.
 */
class EnvParser
{
	public const NEW_LINE     = "\n";
	public const ESCAPE_CHAR  = '\\';
	public const DOUBLE_QUOTE = '"';
	public const SINGLE_QUOTE = '\'';
	public const COMMENT_CHAR = '#';

	private bool $eof            = false;
	private array  $envs         = [];
	private int    $cursor       = -1;
	private bool   $cast_bool    = true;
	private bool   $cast_numeric = true;

	/**
	 * EnvParser constructor.
	 *
	 * @param string $content
	 */
	public function __construct(protected string $content)
	{
	}

	/**
	 * Allow to detect and cast bool.
	 *
	 * ```
	 * FOO=true   # will be true
	 * BAR=false  # will be false
	 *
	 * BAZ='true' # will be 'true' string
	 * FIZZ="true" # will be 'true' string
	 * ```
	 *
	 * @param bool $enabled
	 *
	 * @return $this
	 */
	public function castBool(bool $enabled = true): static
	{
		$this->cast_bool = $enabled;

		return $this;
	}

	/**
	 * Allow to detect and cast numeric value.
	 *
	 * ```
	 * FOO=12   # will be int 12
	 * BAR=35.089  # will be float 35.089
	 *
	 * BAZ='12' # will be '12' string
	 * FIZZ="35.089" # will be '35.089' string
	 * ```
	 *
	 * @param bool $enabled
	 *
	 * @return $this
	 */
	public function castNumeric(bool $enabled = true): static
	{
		$this->cast_numeric = $enabled;

		return $this;
	}

	/**
	 * Creates instances from string.
	 *
	 * @param string $str
	 *
	 * @return static
	 */
	public static function fromString(string $str): self
	{
		return new self($str);
	}

	/**
	 * Creates instances with given env file path.
	 *
	 * @param string $path
	 *
	 * @return static
	 */
	public static function fromFile(string $path): self
	{
		return new self(\file_get_contents($path));
	}

	/**
	 * Returns parsed environments variables.
	 *
	 * @return array
	 */
	public function getEnvs(): array
	{
		return $this->envs;
	}

	/**
	 * Gets a given environments variable value or return default.
	 *
	 * @param string     $name
	 * @param null|mixed $default
	 *
	 * @return null|bool|float|int|string
	 */
	public function getEnv(string $name, mixed $default = null): string|int|bool|float|null
	{
		return $this->envs[$name] ?? $default;
	}

	/**
	 * Parse and merge from a given file.
	 *
	 * ```php
	 * <?php
	 *
	 * # useful when you have a base `base.env`
	 * # file and another `local.env` that overwrite some value.
	 *
	 * $parser = EnvParser::fromFile('base.env');
	 *
	 * $parser->parse()->mergeFromFile('local.env');
	 *
	 * @param string $path
	 *
	 * @return $this
	 */
	public function mergeFromFile(string $path): static
	{
		$this->content = \file_get_contents($path);

		$this->reset(false);
		$this->runParser();

		return $this;
	}

	/**
	 * Parse and merge from a given file.
	 *
	 * ```php
	 * <?php
	 *
	 * # useful when you have a base `base.env`
	 * # file and you want to overwrite some values using .
	 *
	 * $parser = EnvParser::fromFile('base.env');
	 *
	 * $parser->parse()->mergeFromString('VAR=new-value');
	 *
	 * @param string $str
	 *
	 * @return $this
	 */
	public function mergeFromString(string $str): static
	{
		$this->content = $str;

		$this->reset(false);
		$this->runParser();

		return $this;
	}

	/**
	 * Parse.
	 *
	 * @return $this
	 */
	public function parse(): static
	{
		$this->reset();
		$this->runParser();

		return $this;
	}

	private function runParser(): void
	{
		while (!$this->eof) {
			$acc = '';
			while (false !== ($c = $this->move())) {
				if ('=' === $c) {
					break;
				}

				if (self::COMMENT_CHAR === $c) {
					$this->nextComment();

					continue 2;
				}

				$acc .= $c;
			}

			$name = \trim($acc);

			if (!empty($name)) {
				$this->envs[$name] = $this->nextValue();
			}
		}
	}

	private function reset(bool $full_reset = true): void
	{
		$this->cursor = -1;
		$this->eof    = false;
		$full_reset && ($this->envs = []);
	}

	private function nextComment(): void
	{
		while (false !== ($c = $this->move())) {
			if (self::NEW_LINE === $c) {
				break;
			}
		}
	}

	private function move(): string|false
	{
		if ($this->eof) {
			return false;
		}

		$i = $this->cursor + 1;
		$c = $this->content[$i] ?? null;

		if (null === $c) {
			$this->eof = true;

			return false;
		}

		++$this->cursor;

		return $c;
	}

	private function lookForward(): string|null
	{
		if ($this->eof) {
			return null;
		}

		return $this->content[$this->cursor + 1] ?? null;
	}

	private function nextValue(): string|int|bool|float
	{
		$value             = '';
		$end               = self::NEW_LINE;
		$head              = $this->move();
		$quoted            = false;
		$try_interpolation =  false;

		if (false === $head || self::NEW_LINE === $head) {
			return $value;
		}

		if (self::DOUBLE_QUOTE === $head) {
			$end    = self::DOUBLE_QUOTE;
			$quoted = true;
		} elseif (self::SINGLE_QUOTE === $head) {
			$end    = self::SINGLE_QUOTE;
			$quoted = true;
		} elseif (self::COMMENT_CHAR === $head) {// MY_VAR=#comment start
			$this->nextComment();

			return '';
		} else {
			$value = $head;
		}

		while (false !== ($c = $this->move())) {
			if (self::ESCAPE_CHAR === $c) {
				$f = $this->lookForward();
				if ($f === $end || self::ESCAPE_CHAR === $f) {
					$this->move();
					$c = $f;
				} elseif ('t' === $f) {
					$this->move();
					$c = "\t";
				} elseif ('n' === $f) {
					$this->move();
					$c = "\n";
				} elseif ('r' === $f) {
					$this->move();
					$c = "\r";
				} elseif ('f' === $f) {
					$this->move();
					$c = "\f";
				}
			} elseif (self::DOUBLE_QUOTE === $head && '$' === $c) {
				$f = $this->lookForward();
				if ('{' === $f) {
					$try_interpolation = true;
				}
			} elseif (!$quoted && self::COMMENT_CHAR === $c) {
				$this->nextComment();

				break;
			} elseif ($c === $end) {
				break;
			}

			$value .= $c;
		}

		if (!$quoted && !empty($value)) {
			$trimmed_value = \trim($value);

			if ($this->cast_bool) {
				if ('false' === $trimmed_value) {
					return false;
				}

				if ('true' === $trimmed_value) {
					return true;
				}
			}

			if ($this->cast_numeric && \is_numeric($trimmed_value)) {
				return $trimmed_value + 0;
			}

			return $trimmed_value;
		}

		if ($try_interpolation) {
			return Str::interpolate($value, $this->envs, '${', '}');
		}

		return $value;
	}
}
