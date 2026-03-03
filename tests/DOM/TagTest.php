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
use PHPUtils\DOM\Tag;
use PHPUtils\DOM\TextNode;

/**
 * Class TagTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class TagTest extends TestCase
{
	public function testConstructor(): void
	{
		$tag = new Tag('div');

		self::assertSame('div', $tag->getName());
		self::assertFalse($tag->isSelfClosing());
		self::assertSame('<div></div>', (string) $tag);
	}

	public function testSelfClosingTag(): void
	{
		$tag = new Tag('img', true);

		self::assertSame('img', $tag->getName());
		self::assertTrue($tag->isSelfClosing());
		self::assertSame('<img />', (string) $tag);
	}

	public function testSetAttribute(): void
	{
		$tag = new Tag('div');
		$tag->setAttribute('class', 'container');

		self::assertSame('container', $tag->getAttribute('class'));
		self::assertSame('<div class="container"></div>', (string) $tag);
	}

	public function testMultipleAttributes(): void
	{
		$tag = new Tag('input', true);
		$tag->setAttribute('type', 'text')
			->setAttribute('name', 'username')
			->setAttribute('required', '');

		$attributes = $tag->getAttributes();
		self::assertSame('text', $attributes['type']);
		self::assertSame('username', $attributes['name']);
		self::assertSame('', $attributes['required']);

		$output = (string) $tag;
		self::assertStringContainsString('type="text"', $output);
		self::assertStringContainsString('name="username"', $output);
		self::assertStringContainsString('required />', $output);
	}

	public function testAttributeWithSpecialCharacters(): void
	{
		$tag = new Tag('div');
		$tag->setAttribute('data-value', '<script>"xss"</script>');

		$output = (string) $tag;
		self::assertStringContainsString('data-value="&lt;script&gt;&quot;xss&quot;&lt;/script&gt;"', $output);
	}

	public function testGetNonExistentAttribute(): void
	{
		$tag = new Tag('div');

		self::assertNull($tag->getAttribute('nonexistent'));
	}

	public function testAddChild(): void
	{
		$tag      = new Tag('div');
		$textNode = new TextNode('Hello');

		$tag->addChild($textNode);

		$children = $tag->getChildren();
		self::assertCount(1, $children);
		self::assertSame($textNode, $children[0]);
	}

	public function testAddTextNode(): void
	{
		$tag = new Tag('p');
		$tag->addTextNode('Hello World');

		$children = $tag->getChildren();
		self::assertCount(1, $children);

		$n = $children[0];
		self::assertInstanceOf(TextNode::class, $n);
		self::assertSame(
			'Hello World',
			$n->getContent()
		);

		$output = (string) $tag;
		self::assertStringContainsString('Hello World', $output);
	}

	public function testAddCommentNode(): void
	{
		$tag = new Tag('div');
		$tag->addCommentNode('This is a comment');

		$children = $tag->getChildren();
		self::assertCount(1, $children);
		$n = $children[0];
		self::assertInstanceOf(CommentNode::class, $n);

		$output = (string) $tag;
		self::assertStringContainsString('<!-- This is a comment -->', $output);
	}

	public function testComplexNestedStructure(): void
	{
		$html  = new Tag('html');
		$head  = new Tag('head');
		$title = new Tag('title');
		$title->addTextNode('Test Page');

		$head->addChild($title);
		$html->addChild($head);

		$body = new Tag('body');
		$body->setAttribute('class', 'main');
		$body->addCommentNode('Body content starts here');
		$body->addTextNode('Hello World');

		$html->addChild($body);

		$output = (string) $html;

		self::assertStringContainsString('<html>', $output);
		self::assertStringContainsString('<head>', $output);
		self::assertStringContainsString('Test Page', $output); // Check content instead of exact format
		self::assertStringContainsString('<body class="main">', $output);
		self::assertStringContainsString('<!-- Body content starts here -->', $output);
		self::assertStringContainsString('Hello World', $output);
		self::assertStringContainsString('</html>', $output);
	}

	public function testEmptyTag(): void
	{
		$tag = new Tag('div');

		self::assertEmpty($tag->getChildren());
		self::assertEmpty($tag->getAttributes());
		self::assertSame('<div></div>', (string) $tag);
	}

	public function testFluentInterface(): void
	{
		$tag    = new Tag('div');
		$result = $tag->setAttribute('class', 'container')
			->addTextNode('Content')
			->addCommentNode('Comment');

		self::assertSame($tag, $result);
		self::assertCount(2, $tag->getChildren());
		self::assertSame('container', $tag->getAttribute('class'));
	}
}
