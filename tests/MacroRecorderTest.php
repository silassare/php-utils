<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests;

use PHPUnit\Framework\TestCase;
use PHPUtils\Macro\MacroRecorder;

/**
 * Class MacroRecorderTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class MacroRecorderTest extends TestCase
{
	public function testRecord(): void
	{
		/** @var MacroRecorder<SampleChainable> $recorder */
		$recorder = new MacroRecorder();

		$proxy = $recorder->start();

		$proxy->sayHello('John')
			->writeAge(25);

		self::assertSame([
			[
				'method' => 'sayHello',
				'args'   => ['John'],
				'loc'    => [
					'file' => __FILE__,
					'line' => 33,
				],
			],
			[
				'method' => 'writeAge',
				'args'   => [25],
				'loc'    => [
					'file' => __FILE__,
					'line' => 34,
				],
			],
		], $recorder->getRecords());

		$target = new SampleChainable();

		$recorder->run($target);

		self::assertSame("Hello John!\nYou are 25 years old.", $target->getOutput());
	}
}
