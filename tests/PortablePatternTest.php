<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUtils\PortablePattern;

/**
 * Class PortablePatternTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class PortablePatternTest extends TestCase
{
	/**
	 * Patterns both engines read the same way, including the ones real schemas and OZone use.
	 *
	 * @return iterable<string, array{string}>
	 */
	public static function portable(): iterable
	{
		yield 'plain' => ['~^[a-z]+$~'];
		yield 'slash delimiter' => ['/^[a-z]+$/'];
		yield 'flags' => ['~^abc$~imsu'];
		yield 'educaser path' => ['~^(?:/[a-z0-9\-_]+)+/?$~i'];
		yield 'educaser name' => ['~[a-z](?:[a-z0-9_]*[a-z0-9])?~'];
		yield 'ozone phone' => ['~^\+\d{6,15}$~'];
		yield 'ozone cc2' => ['~^[a-zA-Z]{2}$~'];
		yield 'escaped delimiter' => ['~a\~b~'];
		yield 'escaped slash' => ['#^a\/b$#'];
		yield 'syntax escapes' => ['~\^\$\.\*\+\?\(\)\[\]\{\}\|\\\\~'];
		yield 'class escapes' => ['~\d\D\w\W\s\S\bx\B~'];
		yield 'controls' => ['~\t\n\r\f~'];
		yield 'hex' => ['~\x41~'];
		yield 'category' => ['~^\p{L}\P{Lu}\p{Nd}$~'];
		yield 'named group and reference' => ['~(?<word>a)\k<word>~'];
		yield 'numbered reference' => ['~(a)(b)\2\1~'];
		yield 'lookarounds' => ['~(?=a)(?!b)(?<=c)(?<!d)e~'];
		yield 'quantifiers' => ['~a*b+c?d{2}e{2,}f{2,3}g*?h+?i??j{2,3}?~'];
		yield 'alternation' => ['~^(?:a|b|)$~'];
		yield 'class with range, negation and escapes' => ['~[^a-z0-9\-\]\\\\\d_]~'];
		yield 'hyphen at the ends of a class' => ['~[-a][a-][\d-]~'];
		yield 'backspace in a class' => ['~[\b]~'];
		yield 'non-ASCII literal' => ['~^é+$~'];
		yield 'quantified group' => ['~(ab)+(?:cd){2}~'];
	}

	/**
	 * Patterns PCRE accepts and JavaScript refuses or reads otherwise, with a part of the reason.
	 *
	 * @return iterable<string, array{string, string}>
	 */
	public static function unportable(): iterable
	{
		yield 'x flag' => ['~a~x', 'flag "x"'];
		yield 'D flag' => ['~a~D', 'flag "D"'];
		yield 'flag twice' => ['~a~ii', 'given twice'];
		yield 'bracket delimiter' => ['{a}', 'bracket delimiters'];
		yield 'letter delimiter' => ['aba', 'delimiter'];
		yield 'no closing delimiter' => ['~a', 'closing delimiter'];
		yield 'possessive' => ['~a++~', 'possessive'];
		yield 'possessive range' => ['~a{2}+~', 'possessive'];
		yield 'atomic group' => ['~(?>a)~', 'atomic'];
		yield 'branch reset' => ['~(?|a)~', 'branch reset'];
		yield 'comment' => ['~(?#x)a~', 'comment'];
		yield 'conditional' => ['~(a)?(?(1)b)~', 'conditional'];
		yield 'recursion' => ['~(a(?R)?)~', 'recursion'];
		yield 'numbered recursion' => ['~(a(?1)?)~', 'recursion'];
		yield 'P group' => ['~(?P<n>a)~', '(?P'];
		yield 'quoted name' => ["~(?'n'a)~", 'quotes'];
		yield 'inline flags' => ['~(?i)a~', 'inline flags'];
		yield 'inline flags group' => ['~(?i:a)~', 'inline flags'];
		yield 'A anchor' => ['~\Aa~', '"\A" is PCRE only'];
		yield 'z anchor' => ['~a\z~', '"\z" is PCRE only'];
		yield 'K' => ['~a\Kb~', '"\K" is PCRE only'];
		yield 'R' => ['~\R~', '"\R" is PCRE only'];
		yield 'h' => ['~\h~', '"\h" is PCRE only'];
		yield 'v' => ['~\v~', 'vertical'];
		yield 'Q E' => ['~\Qa.b\E~', '"\Q" is PCRE only'];
		yield 'braced hex' => ['~\x{41}~', '\xHH'];
		yield 'u escape' => ['~\u0041~', '"\u" is PCRE only'];
		yield 'octal' => ['~\012~', 'octal'];
		yield 'zero' => ['~\0~', 'octal'];
		yield 'g reference' => ['~(a)\g{1}~', '"\g" is PCRE only'];
		yield 'identity escape' => ['~\@~', 'Unicode mode'];
		yield 'escaped hyphen outside a class' => ['~a\-b~', 'Unicode mode'];
		yield 'script property' => ['~\p{Greek}~', 'general category'];
		yield 'long property' => ['~\p{Letter}~', 'general category'];
		yield 'posix class' => ['~[[:alpha:]]~', 'POSIX'];
		yield 'bracket in a class' => ['~[a[b]~', 'inside a class'];
		yield 'empty class' => ['~[]a]~', 'empty class'];
		yield 'empty negated class' => ['~[^]a]~', 'empty class'];
		yield 'lone brace' => ['~a{~', 'lone "{"'];
		yield 'brace not a quantifier' => ['~a{x}~', 'lone "{"'];
		yield 'lone closing brace' => ['~a}~', 'lone "}"'];
		yield 'lone closing bracket' => ['~a]~', 'lone "]"'];
		yield 'range from a class escape' => ['~[\d-z]~', 'range cannot start'];
		yield 'range to a class escape' => ['~[a-\d]~', 'range cannot end'];
		yield 'quantified anchor' => ['~^*a~', 'assertion'];
		yield 'quantified lookahead' => ['~(?=a)+~', 'assertion'];
		yield 'quantified word boundary' => ['~\b+~', 'assertion'];
		yield 'nothing to quantify' => ['~*a~', 'quantifies nothing'];
		yield 'double quantifier' => ['~a{2}*~', 'cannot follow'];
		yield 'min over max' => ['~a{3,2}~', 'minimum is over'];
		yield 'unclosed group' => ['~(a~', 'not closed'];
		yield 'unmatched parenthesis' => ['~a)~', 'closes no group'];
		yield 'unclosed class' => ['~[a~', 'not closed'];
		yield 'trailing backslash' => ['~a\\~', 'lone'];
	}

	/**
	 * @dataProvider portable
	 */
	public function testAcceptsAPortablePattern(string $pattern): void
	{
		PortablePattern::assertPortable($pattern);

		// It runs, as PHP runs it: `preg_match()` answers 0 or 1, never false.
		self::assertNotFalse(\preg_match(PortablePattern::toPcre($pattern), 'a sample value'));
	}

	/**
	 * @dataProvider unportable
	 */
	public function testRefusesAnUnportablePattern(string $pattern, string $why): void
	{
		try {
			PortablePattern::assertPortable($pattern);
			self::fail(\sprintf('%s was accepted', $pattern));
		} catch (InvalidArgumentException $e) {
			self::assertStringContainsString($why, $e->getMessage());
		}
	}

	public function testTheErrorSaysWhereTheProblemIs(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('pattern ~ab\Kc~ is not portable at offset 3');

		PortablePattern::assertPortable('~ab\Kc~');
	}

	public function testTheRunFormAddsUnicodeAndEndOnlyDollar(): void
	{
		self::assertSame('~a$~uD', PortablePattern::toPcre('~a$~'));
		self::assertSame('~a$~iuD', PortablePattern::toPcre('~a$~i'));
		self::assertSame('~a$~uD', PortablePattern::toPcre('~a$~u'));
		// With `m`, `$` is the end of a line in both engines.
		self::assertSame('~a$~mu', PortablePattern::toPcre('~a$~m'));
	}
}
