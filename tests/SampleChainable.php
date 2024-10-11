<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests;

/**
 * Class SampleChainable.
 */
class SampleChainable
{
	/**
	 * @var string[]
	 */
	protected array $prompt = [];

	public function sayHello(string $name): static
	{
		$this->prompt[]  = "Hello {$name}!";

		return $this;
	}

	public function writeAge(int $age): static
	{
		$this->prompt[] = "You are {$age} years old.";

		return $this;
	}

	public function getOutput(): string
	{
		return \implode(\PHP_EOL, $this->prompt);
	}
}
