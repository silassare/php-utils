<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\DOM;

use PHPUnit\Framework\TestCase;
use PHPUtils\DOM\TextNode;

/**
 * Class TextNodeTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class TextNodeTest extends TestCase
{
	public function testConstructor(): void
	{
		$content  = 'Hello World';
		$textNode = new TextNode($content);

		self::assertSame('Hello World', $textNode->getContent());
		self::assertSame('Hello World', (string) $textNode);
	}

	public function testConstructorWithHtmlEntities(): void
	{
		$content  = '<script>alert("xss")</script>';
		$textNode = new TextNode($content);

		// Should be HTML-escaped
		self::assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $textNode->getContent());
		self::assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', (string) $textNode);
	}

	public function testSetContent(): void
	{
		$textNode = new TextNode('Initial');
		$textNode->setContent('Updated content');

		self::assertSame('Updated content', $textNode->getContent());
		self::assertSame('Updated content', (string) $textNode);
	}

	public function testSetContentWithSpecialCharacters(): void
	{
		$textNode = new TextNode('Initial');
		$textNode->setContent('<b>Bold</b> & "quoted"');

		// setContent doesn't escape, only constructor does
		self::assertSame('<b>Bold</b> & "quoted"', $textNode->getContent());
	}

	public function testToString(): void
	{
		$textNode = new TextNode('Test content');

		self::assertSame('Test content', (string) $textNode);
	}

	public function testEmptyContent(): void
	{
		$textNode = new TextNode('');

		self::assertSame('', $textNode->getContent());
		self::assertSame('', (string) $textNode);
	}
}
