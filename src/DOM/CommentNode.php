<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\DOM;

/**
 * Class CommentNode.
 */
class CommentNode extends Node
{
	protected string $content = '';

	/**
	 * CommentNode constructor.
	 *
	 * @param string $content
	 */
	public function __construct(string $content)
	{
		$this->content = \htmlspecialchars($content, \ENT_QUOTES, null, false);
	}

	/**
	 * To string magic method.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return '<!-- ' . $this->content . ' -->';
	}

	/**
	 * Sets the comment content. The provided value is stored as-is (not HTML-escaped).
	 *
	 * @param string $content the new comment content
	 */
	public function setContent(string $content): void
	{
		$this->content = $content;
	}

	/**
	 * Gets the comment content.
	 *
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->content;
	}
}
