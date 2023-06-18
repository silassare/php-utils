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

	/**
	 * @var array<int, array{type: 'comment'|'raw'|'value'|'var', value: string}>
	 */
	private array $file_structure          = [];
	private static string   $merge_comment = '
# ----------------------------------------
# merged content from: %s
# ----------------------------------------

';

	/**
	 * EnvParser constructor.
	 *
	 * When cast bool is enabled, the following values will be casted to bool:
	 * ```
	 * FOO=true   # will be true
	 * BAR=false  # will be false
	 *
	 * BAZ='true' # will be 'true' string
	 * FIZZ="true" # will be 'true' string
	 * ```
	 *
	 * When cast numeric is enabled, the following values will be casted to int or float:
	 * ```
	 * FOO=12   # will be int 12
	 * BAR=35.089  # will be float 35.089
	 *
	 * BAZ='12' # will be '12' string
	 * FIZZ="35.089" # will be '35.089' string
	 * ```
	 *
	 * @param string $content
	 * @param bool   $cast_bool
	 * @param bool   $cast_numeric
	 */
	public function __construct(protected string $content, protected bool $cast_bool    = true, protected bool $cast_numeric = true)
	{
		$this->parse();
	}

	/**
	 * Returns env file editor instance.
	 *
	 * @return \PHPUtils\EnvEditor
	 */
	public function edit(): EnvEditor
	{
		return new EnvEditor($this->file_structure);
	}

	/**
	 * Creates instances from string.
	 *
	 * @param string $str
	 * @param bool   $cast_bool
	 * @param bool   $cast_numeric
	 *
	 * @return static
	 */
	public static function fromString(string $str, bool $cast_bool    = true, bool $cast_numeric = true): self
	{
		return new self($str, $cast_bool, $cast_numeric);
	}

	/**
	 * Creates instances with given env file path.
	 *
	 * @param string $path
	 * @param bool   $cast_bool
	 * @param bool   $cast_numeric
	 *
	 * @return static
	 */
	public static function fromFile(string $path, bool $cast_bool    = true, bool $cast_numeric = true): self
	{
		return new self(\file_get_contents($path), $cast_bool, $cast_numeric);
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
	 * $parser->mergeFromFile('local.env');
	 *
	 * @param string $path
	 *
	 * @return $this
	 */
	public function mergeFromFile(string $path): static
	{
		$this->content = \file_get_contents($path);

		$this->file_structure[] = [
			'type'  => 'raw',
			'value' => \sprintf(self::$merge_comment, $path),
		];

		return $this->parse();
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
	 * $parser->mergeFromString('VAR=new-value');
	 *
	 * @param string $str
	 *
	 * @return $this
	 */
	public function mergeFromString(string $str): static
	{
		$this->content = $str;

		$this->file_structure[] = [
			'type'  => 'raw',
			'value' => \sprintf(self::$merge_comment, 'raw string'),
		];

		return $this->parse();
	}

	/**
	 * Parse.
	 *
	 * @return self
	 */
	public function parse(): self
	{
		$this->resetCursor();
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
				$this->file_structure[] = [
					'type'  => 'var',
					'value' => $acc,
				];
				$this->envs[$name] = $this->nextValue();
			} elseif (!empty($acc)) {
				$this->file_structure[] = [
					'type'  => 'raw',
					'value' => $acc,
				];
			}
		}

		return $this;
	}

	/**
	 * Reset the cursor.
	 */
	private function resetCursor(): void
	{
		$this->cursor = -1;
		$this->eof    = false;
	}

	/**
	 * Move to the end of a comment.
	 */
	private function nextComment(): void
	{
		$comment = '';
		while (false !== ($c = $this->move())) {
			if (self::NEW_LINE === $c) {
				break;
			}
			$comment .= $c;
		}

		$this->file_structure[] = [
			'type'  => 'comment',
			'value' => $comment,
		];
		if (self::NEW_LINE === $c) {
			$this->file_structure[] = [
				'type'  => 'raw',
				'value' => self::NEW_LINE,
			];
		}
	}

	/**
	 * Move the cursor to the next character.
	 *
	 * @return false|string
	 */
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

	/**
	 * Look forward and return the next character.
	 *
	 * @return null|string
	 */
	private function lookForward(): string|null
	{
		if ($this->eof) {
			return null;
		}

		return $this->content[$this->cursor + 1] ?? null;
	}

	/**
	 * Gets the next value.
	 *
	 * @return bool|float|int|string
	 */
	private function nextValue(): string|int|bool|float
	{
		$value             = '';
		$end               = self::NEW_LINE;
		$head              = $this->move();
		$quoted            = false;
		$try_interpolation =  false;

		if (false === $head) {// EOF
			return $value;
		}
		if (self::NEW_LINE === $head) {
			$this->file_structure[] = [
				'type'  => 'raw',
				'value' => self::NEW_LINE,
			];

			return '';
		}

		if (self::DOUBLE_QUOTE === $head) {
			$end    = self::DOUBLE_QUOTE;
			$quoted = true;
		} elseif (self::SINGLE_QUOTE === $head) {
			$end    = self::SINGLE_QUOTE;
			$quoted = true;
		} elseif (self::COMMENT_CHAR === $head) {// MY_VAR=#comment start
			$this->file_structure[] = [
				'type'  => 'value',
				'value' => '',
			];
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
				} elseif ('v' === $f) {
					$this->move();
					$c = "\v";
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
			$this->file_structure[] = [
				'type'  => 'value',
				'value' => $value,
			];
			if (self::NEW_LINE === $c) {
				$this->file_structure[] = [
					'type'  => 'raw',
					'value' => self::NEW_LINE,
				];
			}
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

		$this->file_structure[] = [
			'type'  => 'value',
			'value' => $head . $value . $end,
		];

		if ($try_interpolation) {
			return Str::interpolate($value, $this->envs, '${', '}');
		}

		return $value;
	}
}
