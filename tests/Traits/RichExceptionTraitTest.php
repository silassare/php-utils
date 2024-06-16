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
use PHPUtils\Exceptions\RuntimeException;

/**
 * @internal
 *
 * @coversNothing
 */
final class RichExceptionTraitTest extends TestCase
{
	public function testGetData(): void
	{
		$e = new RuntimeException('A sample exception.', [
			'_sensitive' => 'yes',
			'line'       => ($line = __LINE__),
		]);

		self::assertSame([
			'line' => $line,
		], $e->getData());

		self::assertSame([
			'_sensitive' => 'yes',
			'line'       => $line,
		], $e->getData(true));
	}
}
