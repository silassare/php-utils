<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Env;

use InvalidArgumentException;
use PHPUtils\Env\Tokens\Comment;
use PHPUtils\Env\Tokens\Equal;
use PHPUtils\Env\Tokens\VarName;
use PHPUtils\Env\Tokens\VarValue;
use PHPUtils\Env\Tokens\WhiteSpace;
use PHPUtils\Str;

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

	private bool  $eof    = false;
	private array $envs   = [];
	private int   $cursor = -1;

	/**
	 * @var array<int, \PHPUtils\Env\Tokens\Token>
	 */
	private array         $tokens        = [];
	private static string $merge_comment = '
# ----------------------------------------
# merged content from: %s
# ----------------------------------------

';

	/**
	 * EnvParser constructor.
	 *
	 * When cast bool is enabled, the following values will be casted to bool:
	 *
	 * ```
	 * FOO=true   # will be true
	 * BAR=false  # will be false
	 *
	 * BAZ='true' # will be 'true' string
	 * FIZZ="true" # will be 'true' string
	 * ```
	 *
	 * When cast numeric is enabled, the following values will be casted to int or float:
	 *
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
	public function __construct(protected string $content, protected bool $cast_bool = true, protected bool $cast_numeric = true)
	{
		$this->parse();
	}

	/**
	 * Returns env file editor instance.
	 *
	 * @return \PHPUtils\Env\EnvEditor
	 */
	public function edit(): EnvEditor
	{
		return new EnvEditor($this->tokens);
	}

	/**
	 * Creates instance from string.
	 *
	 * @param string $str
	 * @param bool   $cast_bool
	 * @param bool   $cast_numeric
	 *
	 * @return static
	 */
	public static function fromString(string $str, bool $cast_bool = true, bool $cast_numeric = true): self
	{
		return new self($str, $cast_bool, $cast_numeric);
	}

	/**
	 * Creates instance with given env file path.
	 *
	 * @param string $path
	 * @param bool   $cast_bool
	 * @param bool   $cast_numeric
	 *
	 * @return static
	 */
	public static function fromFile(string $path, bool $cast_bool = true, bool $cast_numeric = true): self
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
	 * ```
	 *
	 * @param string $path
	 *
	 * @return $this
	 */
	public function mergeFromFile(string $path): static
	{
		$this->content .= \sprintf(self::$merge_comment, $path) . \file_get_contents($path);

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
	 *```
	 *
	 * @param string $str
	 *
	 * @return $this
	 */
	public function mergeFromString(string $str): static
	{
		$this->content .= \sprintf(self::$merge_comment, 'raw string') . $str;

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
			$c = $this->lookForward();
			if (null === $c) {
				break;
			}

			if (self::COMMENT_CHAR === $c) {
				$this->tokens[] = $this->nextComment();
			} elseif ($name_token = $this->nextVarName()) {
				$this->tokens[] = $name_token;
				$this->ignoreWhiteSpace();
				$this->assertNextIsChar('=');
				$this->tokens[]    = new Equal($this->cursor);
				$value_token       = $this->nextValue();
				$this->tokens[]    = $value_token;
				$name              = (string) $name_token->value;
				$this->envs[$name] = $value_token->value;
			} elseif (!$this->ignoreWhiteSpace()) {
				throw new InvalidArgumentException(\sprintf('Unexpected character "%s" at position %d.', $c, $this->cursor));
			}
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	private function ignoreWhiteSpace(): bool
	{
		$white_space = $this->nextWhiteSpace();
		if ($white_space) {
			$this->tokens[] = $white_space;

			return true;
		}

		return false;
	}

	/**
	 * Ensure the next character is a given character.
	 */
	private function assertNextIsChar(string $c): void
	{
		$found = $this->move();

		if (false === $found) {
			throw new InvalidArgumentException(\sprintf('Unexpected end of file while expecting "%s".', $c));
		}
		if ($found !== $c) {
			throw new InvalidArgumentException(\sprintf('Unexpected character "%s" at position %d while expecting "%s".', $found, $this->cursor, $c));
		}
	}

	/**
	 * Reset the cursor.
	 */
	private function resetCursor(): void
	{
		$this->cursor = -1;
		$this->eof    = false;
		$this->tokens = [];
	}

	/**
	 * Read the next comment.
	 */
	private function nextComment(): Comment
	{
		$start   = $this->cursor;
		$comment = '';
		while (!$this->eof) {
			$c = $this->lookForward();

			if (null === $c || self::NEW_LINE === $c) {
				break;
			}

			$this->move();
			$comment .= $c;
		}

		return new Comment($comment, $comment, $start, $this->cursor);
	}

	/**
	 * Read the next var name.
	 */
	private function nextVarName(): ?VarName
	{
		$start = $this->cursor;
		$acc   = '';

		while (null !== ($c = $this->lookForward())) {
			if (!$this->isNameChar($c, empty($acc))) {
				break;
			}
			$this->move();
			$acc .= $c;
		}

		if (empty($acc)) {
			return null;
		}

		return new VarName($acc, \trim($acc), $start, $this->cursor);
	}

	/**
	 * Check if a given character is a valid name character.
	 */
	private function isNameChar(string $c, bool $start): bool
	{
		if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || '_' === $c) {
			return true;
		}

		return !$start && $c >= '0' && $c <= '9';
	}

	/**
	 * Casts a given value.
	 *
	 * @param string $value
	 *
	 * @return bool|float|int|string
	 */
	private function cast(string $value): float|bool|int|string
	{
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

	/**
	 * Gets the next value.
	 *
	 * @return VarValue
	 */
	private function nextValue(): VarValue
	{
		$head              = $this->lookForward();
		$value             = '';
		$try_interpolation = false;
		$quoted            = false;

		if (null === $head) {
			return new VarValue(null, '');
		}

		if (self::NEW_LINE === $head || self::COMMENT_CHAR === $head) {
			$start = $this->cursor;
		} elseif (self::DOUBLE_QUOTE === $head || self::SINGLE_QUOTE === $head) {
			$this->move();
			$start  = $this->cursor;
			$quoted = true;
			$value  = $this->readValue($head, true, $try_interpolation);
		} else {
			// unquoted value
			$start = $this->cursor;
			$value = $this->readValue(self::NEW_LINE, false);
		}

		$raw = $quoted ? $head . $value . $head : $value;

		if (!$quoted) {
			if (!empty($value)) {
				$value = $this->cast($value);
			}
		} elseif ($try_interpolation) {
			$value = Str::interpolate($value, $this->envs, '${', '}');
		}

		return new VarValue($value, $raw, $start, $this->cursor);
	}

	/**
	 * Value reader.
	 *
	 * @param string $end
	 * @param bool   $quoted
	 * @param bool   $try_interpolation
	 *
	 * @return string
	 */
	private function readValue(string $end, bool $quoted, bool &$try_interpolation = false): string
	{
		$acc = '';
		while (null !== ($c = $this->lookForward())) {
			if (!$quoted && self::COMMENT_CHAR === $c) {
				break;
			}

			if ($quoted && self::ESCAPE_CHAR === $c) {
				$this->move();
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
			} elseif (self::DOUBLE_QUOTE === $end && '$' === $c) {
				$this->move();
				$f = $this->lookForward();
				if ('{' === $f) {
					$try_interpolation = true;
				}
			} elseif ($c === $end) {
				if ($quoted) {
					$this->move();
				}

				break;
			} else {
				$this->move();
			}

			$acc .= $c;
		}

		return $acc;
	}

	/**
	 * Read the next whitespace.
	 */
	private function nextWhiteSpace(): ?WhiteSpace
	{
		$start = $this->cursor;
		$acc   = '';

		while (null !== ($c = $this->lookForward())) {
			if (!$this->isWhiteSpace($c)) {
				break;
			}

			$this->move();
			$acc .= $c;
		}
		if (empty($acc)) {
			return null;
		}

		return new WhiteSpace($acc, $acc, $start, $this->cursor);
	}

	/**
	 * Check if a given character is a whitespace.
	 *
	 * @param string $c
	 *
	 * @return bool
	 */
	private function isWhiteSpace(string $c): bool
	{
		return ' ' === $c || "\t" === $c || "\r" === $c || "\n" === $c || "\v" === $c || "\f" === $c;
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
}
