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
 * Class TextNode.
 */
class TextNode extends Node
{
	protected string $content = '';

	/**
	 * TextNode constructor.
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
		return $this->content;
	}

	/**
	 * @param string $content
	 */
	public function setContent(string $content): void
	{
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->content;
	}
}
