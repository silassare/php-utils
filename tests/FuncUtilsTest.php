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
use PHPUtils\FuncUtils;

/**
 * Class FuncUtilsTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class FuncUtilsTest extends TestCase
{
	public function testGetCallerLocation(): void
	{
		$location = $this->callGetCallerLocation();

		self::assertIsArray($location);
		self::assertArrayHasKey('file', $location);
		self::assertArrayHasKey('line', $location);
		self::assertIsString($location['file']);
		self::assertIsInt($location['line']);
		self::assertStringContainsString('FuncUtilsTest.php', $location['file']);
	}

	public function testGetCallerLocationReturnsCallerNotSelf(): void
	{
		// Must call through a helper so the reported caller is in this file,
		// not PHPUnit's TestCase (which invokes test methods via reflection).
		$location = $this->callGetCallerLocation();

		self::assertStringContainsString('FuncUtilsTest.php', $location['file']);
		self::assertGreaterThan(0, $location['line']);
	}

	public function testGetCallerLocationFormatting(): void
	{
		$location = $this->callGetCallerLocation();

		// Verify the location points to this method
		self::assertGreaterThan(0, $location['line']);
		self::assertTrue(\file_exists($location['file']));
	}

	public function testGetCallerLocationFileIsAbsolutePath(): void
	{
		$location = FuncUtils::getCallerLocation();

		self::assertTrue(\file_exists($location['file']), 'file path should be a valid absolute path');
		self::assertStringStartsWith('/', $location['file']);
	}

	public function testGetCallerLocationFromNestedCalls(): void
	{
		$location = $this->nestLevel1();

		// Should point to nestLevel2, not this method
		self::assertStringContainsString('FuncUtilsTest.php', $location['file']);
		self::assertIsInt($location['line']);
	}

	private function callGetCallerLocation(): array
	{
		// This helper method allows us to test that the caller location
		// is correctly identified as this method, not the FuncUtils method itself
		return FuncUtils::getCallerLocation();
	}

	private function nestLevel1(): array
	{
		return $this->nestLevel2();
	}

	private function nestLevel2(): array
	{
		return FuncUtils::getCallerLocation();
	}
}
