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
use RuntimeException;

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

	public function testMissingMethod(): void
	{
		$recorder = new class {
			use RecordableTrait;
		};

		$target = new class {};

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Method "nonExistentMethod" does not exist on "' . \get_class($target) . '".');
		$recorder->nonExistentMethod('arg1', 'arg2');

		$recorder->play($target);
	}

	public function testErrorOnMethodCall(): void
	{
		$recorder = new class {
			use RecordableTrait;
		};

		$target = new class {
			public function faultyMethod(): void
			{
				throw new RuntimeException('An error occurred in faultyMethod.');
			}
		};

		$recorder->faultyMethod();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Error calling method "faultyMethod" on "' . \get_class($target) . '".');

		$recorder->play($target);
	}
}
