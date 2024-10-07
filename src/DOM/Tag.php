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
 * Class Tag.
 */
class Tag extends Node
{
	/**
	 * Tag attributes.
	 *
	 * @var array<string, string>
	 */
	protected array $attributes = [];

	/**
	 * @var array<Node>
	 */
	protected array $children = [];

	/**
	 * The tag name.
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * Is self closing tag ?
	 */
	protected bool $self_closing = false;

	/**
	 * Tag constructor.
	 *
	 * @param string $name         the tag name
	 * @param bool   $self_closing is self closing tag ?
	 */
	public function __construct(
		string $name,
		bool $self_closing = false
	) {
		$this->name         = $name;
		$this->self_closing = $self_closing;
	}

	/**
	 * To string magic method.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		$attributes = '';

		foreach ($this->attributes as $key => $value) {
			$attributes .= ' ' . $key;

			if (!empty($value)) {
				$attributes .= '="' . \htmlspecialchars($value, \ENT_QUOTES, null, false) . '"';
			}
		}

		if ($this->self_closing) {
			return '<' . $this->name . $attributes . ' />';
		}

		$children_str = \implode(\PHP_EOL, $this->children);

		if (!empty($children_str)) {
			$children_str = \PHP_EOL . $children_str . \PHP_EOL;
		}

		return '<' . $this->name . $attributes . '>' . $children_str . '</' . $this->name . '>';
	}

	/**
	 * Get the tag name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Is self closing tag ?
	 *
	 * @return bool
	 */
	public function isSelfClosing(): bool
	{
		return $this->self_closing;
	}

	/**
	 * Set the tag attribute.
	 */
	public function setAttribute(string $name, string $value): static
	{
		$this->attributes[$name] = $value;

		return $this;
	}

	/**
	 * Get the tag attributes.
	 *
	 * @return array<string, string>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * Get the tag attribute by name.
	 */
	public function getAttribute(string $name): ?string
	{
		return $this->attributes[$name] ?? null;
	}

	/**
	 * Add a child to the tag.
	 */
	public function addChild(Node $child): static
	{
		$this->children[] = $child;

		return $this;
	}

	/**
	 * Add a text node to the tag.
	 */
	public function addTextNode(string $text): static
	{
		$this->children[] = new TextNode($text);

		return $this;
	}

	/**
	 * Add a comment node to the tag.
	 */
	public function addCommentNode(string $comment): static
	{
		$this->children[] = new CommentNode($comment);

		return $this;
	}

	/**
	 * Get the tag children.
	 *
	 * @return array<Node>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}
}
