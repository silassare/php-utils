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
use PHPUtils\DotPath;

/**
 * Class DotPathTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class DotPathTest extends TestCase
{
	// -------------------------------------------------------------------------
	// parse() - valid paths
	// -------------------------------------------------------------------------

	public function testParsePlainSegments(): void
	{
		$p = DotPath::parse('foo');
		self::assertSame(['foo'], $p->getSegments());

		$p = DotPath::parse('foo.bar.baz');
		self::assertSame(['foo', 'bar', 'baz'], $p->getSegments());
	}

	public function testParseBracketInteger(): void
	{
		$p = DotPath::parse('foo[0]');
		self::assertSame(['foo', '0'], $p->getSegments());

		$p = DotPath::parse('foo[0].bar[42]');
		self::assertSame(['foo', '0', 'bar', '42'], $p->getSegments());
	}

	public function testParseBracketSingleQuote(): void
	{
		$p = DotPath::parse("foo['bar.baz']");
		self::assertSame(['foo', 'bar.baz'], $p->getSegments());

		$p = DotPath::parse("['it\\'s']");
		self::assertSame(["it's"], $p->getSegments());
	}

	public function testParseBracketDoubleQuote(): void
	{
		$p = DotPath::parse('foo["bar.baz"].qux');
		self::assertSame(['foo', 'bar.baz', 'qux'], $p->getSegments());

		$p = DotPath::parse('foo["say \"hi\""]');
		self::assertSame(['foo', 'say "hi"'], $p->getSegments());
	}

	public function testParseMixedNotation(): void
	{
		$p = DotPath::parse("foo[0]['bar.baz'].qux");
		self::assertSame(['foo', '0', 'bar.baz', 'qux'], $p->getSegments());
	}

	public function testParseOptionalDotAfterBracket(): void
	{
		// dot after ] is optional
		$p1 = DotPath::parse('foo[0].bar');
		$p2 = DotPath::parse('foo[0]bar');
		self::assertSame($p1->getSegments(), $p2->getSegments());
	}

	public function testParseSingleBracketAtRoot(): void
	{
		$p = DotPath::parse("['space key'].sub");
		self::assertSame(['space key', 'sub'], $p->getSegments());
	}

	// -------------------------------------------------------------------------
	// parse() - Invalid dot paths
	// -------------------------------------------------------------------------

	public function testParseEmptyPathThrows(): void
	{
		assertException(
			new InvalidArgumentException('Invalid dot path: path cannot be empty'),
			static fn () => DotPath::parse('')
		);
	}

	public function testParseTrailingDotThrows(): void
	{
		assertException(
			new InvalidArgumentException('Invalid dot path: trailing dot'),
			static fn () => DotPath::parse('foo.')
		);
	}

	public function testParseConsecutiveDotsThrow(): void
	{
		assertException(
			new InvalidArgumentException('Invalid dot path: empty segment (consecutive dots not allowed)'),
			static fn () => DotPath::parse('foo..bar')
		);
	}

	public function testParseEmptyBracketQuotedSegmentThrows(): void
	{
		assertException(
			new InvalidArgumentException('Invalid dot path: empty quoted segment is not allowed'),
			static fn () => DotPath::parse("foo['']")
		);

		assertException(
			new InvalidArgumentException('Invalid dot path: empty quoted segment is not allowed'),
			static fn () => DotPath::parse('foo[""]')
		);
	}

	public function testParsePlainSegmentWithInvalidCharsThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/plain segment.*invalid characters/');
		DotPath::parse('foo.bar baz');
	}

	public function testParseUnclosedBracketThrows(): void
	{
		assertException(
			new InvalidArgumentException('Invalid dot path: unexpected end after `[`'),
			static fn () => DotPath::parse('foo[')
		);
	}

	public function testParseMissingClosingBracketAfterQuoteThrows(): void
	{
		assertException(
			new InvalidArgumentException('Invalid dot path: missing closing `]` after quoted segment'),
			static fn () => DotPath::parse("foo['bar'")
		);
	}

	public function testParseMissingClosingBracketAfterIntThrows(): void
	{
		assertException(
			new InvalidArgumentException('Invalid dot path: missing closing `]` after integer index'),
			static fn () => DotPath::parse('foo[42')
		);
	}

	public function testParseInvalidCharAfterOpenBracketThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/expected quote or integer after/');
		DotPath::parse('foo[bar]');
	}

	// -------------------------------------------------------------------------
	// __toString()
	// -------------------------------------------------------------------------

	public function testToStringRoundtripPlain(): void
	{
		self::assertSame('foo.bar.baz', (string) DotPath::parse('foo.bar.baz'));
	}

	public function testToStringRoundtripBracket(): void
	{
		// bracket-quoted segments are emitted as ['...']
		self::assertSame("foo['bar.baz']", (string) DotPath::parse("foo['bar.baz']"));
	}

	public function testToStringEscapesSingleQuote(): void
	{
		$path = DotPath::parse("['it\\'s']");
		self::assertSame("['it\\'s']", (string) $path);
	}

	public function testToStringIntegerSegmentEmittedAsPlain(): void
	{
		// bracket-integer segments are stored as plain strings, emitted without brackets
		$p = DotPath::parse('foo[0]');
		self::assertSame('foo.0', (string) $p);
	}

	// -------------------------------------------------------------------------
	// isEmpty()
	// -------------------------------------------------------------------------

	public function testIsEmpty(): void
	{
		self::assertFalse(DotPath::parse('foo')->isEmpty());
	}
}
