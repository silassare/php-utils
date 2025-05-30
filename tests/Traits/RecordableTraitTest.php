<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Traits;

use PHPUnit\Framework\TestCase;
use PHPUtils\Traits\RecordableTrait;

/**
 * @internal
 *
 * @coversNothing
 */
final class RecordableTraitTest extends TestCase
{
	public function testRecordable(): void
	{
		$recorder = new class {
			use RecordableTrait;
		};

		$target = new class {
			protected int $value    = 0;
			protected string $text  = '';

			public function getText(): string
			{
				return $this->text;
			}

			public function setText(string $text): void
			{
				$this->text = $text;
			}

			public function getValue(): int
			{
				return $this->value;
			}

			public function add(int $num): void
			{
				$this->value += $num;
			}

			public function subtract(int $num): void
			{
				$this->value -= $num;
			}
		};

		$recorder->add(10);
		$recorder->setText('Hello!');
		$recorder->subtract(2);

		$recorder->play($target);

		self::assertSame($target->getValue(), 8);
		self::assertSame($target->getText(), 'Hello!');
	}
}
