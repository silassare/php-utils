<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils;

use InvalidArgumentException;

/**
 * A string pattern that PHP (PCRE) and JavaScript read the same way.
 *
 * A pattern declared on the server and checked again in the browser must mean the same thing in both.
 * The two engines disagree on much of what PCRE accepts (possessive quantifiers, `\A`, POSIX classes,
 * `$` before a final newline, ...), so only a portable subset is accepted: the JavaScript grammar in
 * Unicode mode, without what PCRE reads differently. The form stays PHP's (`<d>body<d>flags`), so a
 * pattern already written for `preg_match()` stays valid.
 *
 * ```php
 * PortablePattern::assertPortable('~^[a-z]+$~i');       // fine
 * PortablePattern::assertPortable('~^[a-z]++$~');       // throws: possessive quantifier
 * \preg_match(PortablePattern::toPcre('~^a$~'), $value); // as JavaScript runs it
 * ```
 *
 * The contract is written once for every implementation: oliup-suite `docs/specs/patterns.md`.
 */
final class PortablePattern
{
	/** The flags a portable pattern may carry. */
	public const FLAGS = ['i', 'm', 's', 'u'];

	/** The characters an escape may name to stand for themselves, outside a class. */
	private const SYNTAX_CHARS = '^$\.*+?()[]{}|/';

	/** The escapes that are a character class or an assertion in both engines. */
	private const CLASS_ESCAPES = 'dDwWsSbB';

	/** The escapes that are a control character in both engines. */
	private const CONTROL_ESCAPES = 'tnrf';

	/** The opening brackets PHP also accepts as delimiters, refused here. */
	private const BRACKET_DELIMITERS = '([{<';

	/** The escapes PCRE has and JavaScript lacks or reads otherwise. */
	private const PCRE_ONLY_ESCAPES = 'AzZGKRhHVXQEeacCNgou';

	/** The escapes that stand for a set of characters, which cannot bound a range. */
	private const SET_ESCAPES = 'dDwWsSpP';

	/** The Unicode general categories both engines know by their short name. */
	private const CATEGORIES = [
		'L', 'Lu', 'Ll', 'Lt', 'Lm', 'Lo',
		'M', 'Mn', 'Mc', 'Me',
		'N', 'Nd', 'Nl', 'No',
		'P', 'Pc', 'Pd', 'Ps', 'Pe', 'Pi', 'Pf', 'Po',
		'S', 'Sm', 'Sc', 'Sk', 'So',
		'Z', 'Zs', 'Zl', 'Zp',
		'C', 'Cc', 'Cf', 'Co', 'Cn',
	];

	private int $pos = 0;

	private function __construct(private readonly string $body, private readonly string $delimiter)
	{
	}

	/**
	 * Throws when a pattern is not portable, saying why and where.
	 *
	 * @throws InvalidArgumentException
	 */
	public static function assertPortable(string $pattern): void
	{
		[$delimiter, $body] = self::split($pattern);

		(new self($body, $delimiter))->checkBody();

		if (false === @\preg_match(self::toPcre($pattern), '')) {
			throw new InvalidArgumentException(\sprintf('invalid regular expression: %s', $pattern));
		}
	}

	/**
	 * The pattern as PHP runs it: in Unicode mode, and with `$` at the very end of the value unless `m`
	 * is set, which is how JavaScript runs it.
	 *
	 * @throws InvalidArgumentException
	 */
	public static function toPcre(string $pattern): string
	{
		[, , $flags] = self::split($pattern);

		$extra = \str_contains($flags, 'u') ? '' : 'u';

		if (!\str_contains($flags, 'm')) {
			$extra .= 'D';
		}

		return $pattern . $extra;
	}

	/**
	 * Splits a pattern into its delimiter, body and flags.
	 *
	 * @return array{string, string, string}
	 *
	 * @throws InvalidArgumentException
	 */
	private static function split(string $pattern): array
	{
		$delimiter = $pattern[0] ?? '';

		if ('' === $delimiter || \ctype_alnum($delimiter) || '\\' === $delimiter || \ctype_space($delimiter)) {
			throw self::error(
				$pattern,
				0,
				'a pattern starts with a delimiter that is not a letter, a digit, a backslash or a space'
			);
		}

		if (\str_contains(self::BRACKET_DELIMITERS, $delimiter)) {
			throw self::error($pattern, 0, 'bracket delimiters are not accepted, use one character such as "~" or "/"');
		}

		$end = \strrpos($pattern, $delimiter);

		if (false === $end || 0 === $end) {
			throw self::error($pattern, 0, \sprintf('no closing delimiter "%s"', $delimiter));
		}

		$flags = \substr($pattern, $end + 1);

		foreach (\str_split($flags) as $i => $flag) {
			if ('' === $flag) {
				continue;
			}

			if (!\in_array($flag, self::FLAGS, true)) {
				throw self::error(
					$pattern,
					$end + 1 + $i,
					\sprintf('the flag "%s" is not portable, only "i", "m", "s" and "u" are', $flag)
				);
			}

			if (\substr_count($flags, $flag) > 1) {
				throw self::error(
					$pattern,
					$end + 1 + $i,
					\sprintf('the flag "%s" is given twice', $flag)
				);
			}
		}

		return [$delimiter, \substr($pattern, 1, $end - 1), $flags];
	}

	private static function error(string $pattern, int $at, string $why): InvalidArgumentException
	{
		return new InvalidArgumentException(
			\sprintf('pattern %s is not portable at offset %d: %s', $pattern, $at, $why)
		);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function checkBody(): void
	{
		$this->alternation(false);

		if ($this->pos < \strlen($this->body)) {
			throw $this->fail('")" closes no group');
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function alternation(bool $in_group): void
	{
		$length = \strlen($this->body);

		while ($this->pos < $length) {
			$c = $this->body[$this->pos];

			if (')' === $c) {
				if (!$in_group) {
					throw $this->fail('")" closes no group');
				}

				return;
			}

			if ('|' === $c) {
				++$this->pos;

				continue;
			}

			$quantifiable = $this->atom();

			$this->quantifier($quantifiable);
		}

		if ($in_group) {
			throw $this->fail('a group is not closed');
		}
	}

	/**
	 * Reads one atom; answers whether a quantifier may follow it.
	 *
	 * @throws InvalidArgumentException
	 */
	private function atom(): bool
	{
		$c = $this->body[$this->pos];

		switch ($c) {
			case '^':
			case '$':
				++$this->pos;

				return false;

			case '(':
				return $this->group();

			case '[':
				$this->characterClass();

				return true;

			case '\\':
				return $this->escape(false);

			case '{':
				throw $this->fail('a lone "{" is not portable, escape it as "\{"');

			case '}':
				throw $this->fail('a lone "}" is not portable, escape it as "\}"');

			case ']':
				throw $this->fail('a lone "]" is not portable, escape it as "\]"');

			case '*':
			case '+':
			case '?':
				throw $this->fail(\sprintf('"%s" quantifies nothing', $c));

			default:
				++$this->pos;

				return true;
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function group(): bool
	{
		$rest = \substr($this->body, $this->pos);

		$lookbehind = \str_starts_with($rest, '(?<=') || \str_starts_with($rest, '(?<!');

		if ($lookbehind) {
			$this->pos += 4;
			$quantifiable = false;
		} elseif (\str_starts_with($rest, '(?=') || \str_starts_with($rest, '(?!')) {
			$this->pos += 3;
			$quantifiable = false;
		} elseif (\str_starts_with($rest, '(?:')) {
			$this->pos += 3;
			$quantifiable = true;
		} elseif (\preg_match('~^\(\?<([A-Za-z_][A-Za-z0-9_]*)>~', $rest, $m)) {
			$this->pos += \strlen($m[0]);
			$quantifiable = true;
		} elseif (\str_starts_with($rest, '(?')) {
			throw $this->fail($this->unportableGroup($rest));
		} else {
			++$this->pos;
			$quantifiable = true;
		}

		$this->alternation(true);

		// The ")" that alternation() stopped on.
		++$this->pos;

		return $quantifiable;
	}

	private function unportableGroup(string $rest): string
	{
		return match (true) {
			\str_starts_with($rest, '(?>')  => 'an atomic group "(?>" is PCRE only',
			\str_starts_with($rest, '(?|')  => 'a branch reset "(?|" is PCRE only',
			\str_starts_with($rest, '(?#')  => 'a comment "(?#" is PCRE only',
			\str_starts_with($rest, '(?(')  => 'a conditional "(?(" is PCRE only',
			\str_starts_with($rest, '(?P')  => 'the "(?P" syntax is PCRE only, write "(?<name>" and "\k<name>"',
			\str_starts_with($rest, "(?'") => 'a group named with quotes is PCRE only, write "(?<name>"',
			\str_starts_with($rest, '(?&')
			|| \str_starts_with($rest, '(?R')
			|| 1 === \preg_match('~^\(\?[+-]?\d~', $rest)
											 => 'recursion is PCRE only',
			\str_starts_with($rest, '(?<')  => 'a group name is letters, digits and "_", not starting with a digit',
			default                          => 'inline flags such as "(?i)" are not portable, use the pattern flags',
		};
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function quantifier(bool $quantifiable): void
	{
		$c = $this->body[$this->pos] ?? '';

		if ('*' === $c || '+' === $c || '?' === $c) {
			$length = 1;
		} elseif ('{' === $c && 1 === \preg_match('~^\{\d+(,\d*)?\}~', \substr($this->body, $this->pos), $m)) {
			$length = \strlen($m[0]);

			if (1 === \preg_match('~^\{(\d+),(\d+)\}$~', $m[0], $bounds) && (int) $bounds[1] > (int) $bounds[2]) {
				throw $this->fail('a quantifier whose minimum is over its maximum');
			}
		} else {
			return;
		}

		if (!$quantifiable) {
			throw $this->fail('an assertion cannot be quantified in both engines');
		}

		$this->pos += $length;

		$next = $this->body[$this->pos] ?? '';

		if ('?' === $next) {
			++$this->pos;
		} elseif ('+' === $next) {
			throw $this->fail('a possessive quantifier is PCRE only');
		}

		$after = $this->body[$this->pos] ?? '';

		$braces = '{' === $after && 1 === \preg_match('~^\{\d+(,\d*)?\}~', \substr($this->body, $this->pos));

		if ('*' === $after || '+' === $after || '?' === $after || $braces) {
			throw $this->fail('a quantifier cannot follow a quantifier');
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function characterClass(): void
	{
		$start = $this->pos;
		++$this->pos;

		if ('^' === ($this->body[$this->pos] ?? '')) {
			++$this->pos;
		}

		if (']' === ($this->body[$this->pos] ?? '')) {
			throw $this->fail('an empty class "[]" or "[^]" is read differently by PCRE and JavaScript');
		}

		$length = \strlen($this->body);

		while ($this->pos < $length) {
			$c = $this->body[$this->pos];

			if (']' === $c) {
				++$this->pos;

				return;
			}

			if ('[' === $c) {
				$next = $this->body[$this->pos + 1] ?? '';

				if (':' === $next || '.' === $next || '=' === $next) {
					throw $this->fail('POSIX classes such as "[:alpha:]" are PCRE only');
				}

				throw $this->fail('a "[" inside a class is not portable, escape it as "\["');
			}

			if ('\\' === $c) {
				$escape_at = $this->pos;

				$this->escape(true);

				$ranges = '-' === ($this->body[$this->pos] ?? '') && ']' !== ($this->body[$this->pos + 1] ?? ']');

				if ($ranges && $this->isClassEscapeAt($escape_at)) {
					throw $this->fail(
						'a range cannot start at a class escape such as "\\d": '
						. 'PCRE reads "-" as a literal, JavaScript refuses it'
					);
				}

				continue;
			}

			$previous = $this->body[$this->pos - 1] ?? '';

			if ('-' === $c && '[' !== $previous && '^' !== $previous && $this->isClassEscapeAt($this->pos + 1)) {
				throw $this->fail(
					'a range cannot end at a class escape such as "\\d": '
					. 'PCRE reads "-" as a literal, JavaScript refuses it'
				);
			}

			++$this->pos;
		}

		$this->pos = $start;

		throw $this->fail('a class is not closed');
	}

	/**
	 * Reads an escape; answers whether a quantifier may follow it.
	 *
	 * @throws InvalidArgumentException
	 */
	private function escape(bool $in_class): bool
	{
		$c = $this->body[$this->pos + 1] ?? '';

		if ('' === $c) {
			throw $this->fail('the pattern ends with a lone "\"');
		}

		if (\str_contains(self::CLASS_ESCAPES, $c)) {
			$this->pos += 2;

			// `\b` and `\B` are assertions, except in a class where `\b` is a backspace.
			return $in_class || ('b' !== $c && 'B' !== $c);
		}

		if (\str_contains(self::CONTROL_ESCAPES, $c) || \str_contains(self::SYNTAX_CHARS, $c)) {
			$this->pos += 2;

			return true;
		}

		if ($in_class && '-' === $c) {
			$this->pos += 2;

			return true;
		}

		if ($c === $this->delimiter) {
			$this->pos += 2;

			return true;
		}

		if ('x' === $c) {
			if (1 !== \preg_match('~^\\\x[0-9A-Fa-f]{2}~', \substr($this->body, $this->pos))) {
				throw $this->fail('write a code unit as "\xHH", two hex digits ("\x{...}" is PCRE only)');
			}

			$this->pos += 4;

			return true;
		}

		if ('p' === $c || 'P' === $c) {
			if (
				1 !== \preg_match('~^\\\[pP]\{([A-Za-z]{1,2})\}~', \substr($this->body, $this->pos), $m)
				|| !\in_array($m[1], self::CATEGORIES, true)
			) {
				throw $this->fail('only a Unicode general category such as "\p{L}" or "\p{Lu}" is portable');
			}

			$this->pos += \strlen($m[0]);

			return true;
		}

		if ('k' === $c && !$in_class) {
			if (1 !== \preg_match('~^\\\k<[A-Za-z_][A-Za-z0-9_]*>~', \substr($this->body, $this->pos), $m)) {
				throw $this->fail('a named back-reference is written "\k<name>"');
			}

			$this->pos += \strlen($m[0]);

			return true;
		}

		if (\ctype_digit($c)) {
			if ($in_class || '0' === $c || \ctype_digit($this->body[$this->pos + 2] ?? '')) {
				throw $this->fail('"\0" and octal escapes are not portable, write "\xHH"');
			}

			$this->pos += 2;

			return true;
		}

		if ('v' === $c) {
			throw $this->fail('"\v" is vertical whitespace in PCRE and a vertical tab in JavaScript');
		}

		if (\str_contains(self::PCRE_ONLY_ESCAPES, $c)) {
			throw $this->fail(\sprintf('"\%s" is PCRE only', $c));
		}

		throw $this->fail(\sprintf(
			'"\%s" is an error in JavaScript\'s Unicode mode: escape only a syntax character',
			$c
		));
	}

	/** Whether a class escape (`\d`, `\w`, `\s`, `\p{...}` and their negations) starts at an offset. */
	private function isClassEscapeAt(int $at): bool
	{
		return '\\' === ($this->body[$at] ?? '') && \str_contains(self::SET_ESCAPES, $this->body[$at + 1] ?? '#');
	}

	private function fail(string $why): InvalidArgumentException
	{
		return self::error($this->delimiter . $this->body . $this->delimiter, $this->pos + 1, $why);
	}
}
