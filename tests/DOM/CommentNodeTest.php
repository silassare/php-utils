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
use PHPUtils\DOM\CommentNode;

/**
 * Class CommentNodeTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class CommentNodeTest extends TestCase
{
	public function testConstructor(): void
	{
		$content     = 'This is a comment';
		$commentNode = new CommentNode($content);

		self::assertSame('This is a comment', $commentNode->getContent());
		self::assertSame('<!-- This is a comment -->', (string) $commentNode);
	}

	public function testConstructorWithHtmlEntities(): void
	{
		$content     = '<script>alert("xss")</script>';
		$commentNode = new CommentNode($content);

		// Should be HTML-escaped
		self::assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $commentNode->getContent());
		self::assertSame('<!-- &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; -->', (string) $commentNode);
	}

	public function testSetContent(): void
	{
		$commentNode = new CommentNode('Initial comment');
		$commentNode->setContent('Updated comment');

		self::assertSame('Updated comment', $commentNode->getContent());
		self::assertSame('<!-- Updated comment -->', (string) $commentNode);
	}

	public function testToString(): void
	{
		$commentNode = new CommentNode('Test comment');

		self::assertSame('<!-- Test comment -->', (string) $commentNode);
	}

	public function testEmptyComment(): void
	{
		$commentNode = new CommentNode('');

		self::assertSame('', $commentNode->getContent());
		self::assertSame('<!--  -->', (string) $commentNode);
	}

	public function testCommentWithSpecialCharacters(): void
	{
		$commentNode = new CommentNode('Comment with -- double dashes');

		self::assertStringContainsString('-- double dashes', $commentNode->getContent());
		self::assertStringContainsString('<!-- ', (string) $commentNode);
		self::assertStringContainsString(' -->', (string) $commentNode);
	}
}
