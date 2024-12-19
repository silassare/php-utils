<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Env;

use PHPUtils\Env\Tokens\Equal;
use PHPUtils\Env\Tokens\Token;
use PHPUtils\Env\Tokens\VarName;
use PHPUtils\Env\Tokens\VarValue;
use PHPUtils\Env\Tokens\WhiteSpace;

/**
 * Class EnvEditor.
 */
class EnvEditor
{
	/**
	 * EnvEditor constructor.
	 *
	 * @param array<int, Token> $tokens
	 */
	public function __construct(protected array $tokens) {}

	/**
	 * Magic string conversion.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return \implode('', $this->tokens);
	}

	/**
	 * Returns dot env file parsed tokens.
	 *
	 * @return array<int, Token>
	 */
	public function getTokens(): array
	{
		return $this->tokens;
	}

	/**
	 * Updates the value of an existing key or adds a new key-value pair to the end of the file.
	 *
	 * @param string $key              the key to set
	 * @param string $value            the key value
	 * @param bool   $first_occurrence if true, the first occurrence of the key will be updated
	 *                                 if false, the last occurrence of the key will be updated
	 * @param bool   $quote            if true, the value will be quoted
	 *
	 * @return $this
	 */
	public function upset(string $key, string $value, bool $first_occurrence = false, bool $quote = false): self
	{
		$raw = $value;
		if ($quote) {
			$value = '"' . \addcslashes($value, '"') . '"';
		}
		$index = -1;
		// find the index of the key
		foreach ($this->tokens as $i => $item) {
			if ($item instanceof VarName && $item->value === $key) {
				$index = $i;
				if ($first_occurrence) {
					break;
				}
			}
		}

		if (-1 === $index) {
			// add the key-value pair to the end of the file
			$this->tokens[] = new WhiteSpace(EnvParser::NEW_LINE);
			$this->tokens[] = new VarName($key);
			$this->tokens[] = new Equal();
			$this->tokens[] = new VarValue($value, $raw);

			return $this;
		}

		// find the next value index

		$next_value_index = -1;
		$len              = \count($this->tokens);
		$has_equal_sign   = false;
		for ($i = $index + 1; $i < $len; ++$i) {
			$item = $this->tokens[$i];
			if ($item instanceof Equal) {
				$has_equal_sign = true;
			} elseif ($item instanceof VarValue) {
				$next_value_index = $i;

				break;
			}
		}

		if (-1 === $next_value_index) {
			// possible end of file
			$head   = \array_slice($this->tokens, 0, $index + 1);
			$tail   = \array_slice($this->tokens, $index + 1);
			$insert = $has_equal_sign ? [
				new VarValue($value, $raw),
			] : [
				new Equal(),
				new VarValue($value, $raw),
			];

			$this->tokens = [
				...$head,
				...$insert,
				...$tail,
			];
		} else {
			// update the value
			$this->tokens[$next_value_index] = new VarValue($value, $raw);
		}

		return $this;
	}
}
