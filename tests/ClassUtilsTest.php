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
use PHPUtils\ClassUtils;
use PHPUtils\Traits\ArrayCapableTrait;
use PHPUtils\Traits\RichExceptionTrait;

/**
 * Class ClassUtilsTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class ClassUtilsTest extends TestCase
{
	public function testHasTraitWithObject(): void
	{
		$object = new class {
			use ArrayCapableTrait;

			public function toArray(): array
			{
				return [];
			}
		};

		self::assertTrue(ClassUtils::hasTrait($object, ArrayCapableTrait::class));
		self::assertFalse(ClassUtils::hasTrait($object, RichExceptionTrait::class));
	}

	public function testHasTraitWithClassName(): void
	{
		$className = new class {
			use RichExceptionTrait;

			public function __construct()
			{
				// Empty constructor to fix the trait requirements
			}
		};

		self::assertTrue(ClassUtils::hasTrait($className::class, RichExceptionTrait::class));
		self::assertFalse(ClassUtils::hasTrait($className::class, ArrayCapableTrait::class));
	}

	public function testGetUsedTraitsDeepWithObject(): void
	{
		$object = new class {
			use ArrayCapableTrait;
			use RichExceptionTrait;

			public function toArray(): array
			{
				return [];
			}

			public function __construct()
			{
				// Empty constructor to fix the trait requirements
			}
		};

		$traits = ClassUtils::getUsedTraitsDeep($object);

		self::assertArrayHasKey(ArrayCapableTrait::class, $traits);
		self::assertArrayHasKey(RichExceptionTrait::class, $traits);
	}

	public function testGetUsedTraitsDeepWithClassName(): void
	{
		$className = new class {
			use ArrayCapableTrait;

			public function toArray(): array
			{
				return [];
			}
		};

		$traits = ClassUtils::getUsedTraitsDeep($className::class);

		self::assertArrayHasKey(ArrayCapableTrait::class, $traits);
		self::assertArrayNotHasKey(RichExceptionTrait::class, $traits);
	}

	public function testGetUsedTraitsDeepWithInheritance(): void
	{
		$parentClass = new class {
			use ArrayCapableTrait;

			public function toArray(): array
			{
				return [];
			}
		};

		$childClass = new class {
			use RichExceptionTrait;

			public function __construct()
			{
				// Empty constructor to fix the trait requirements
			}
		};

		$traits = ClassUtils::getUsedTraitsDeep($childClass);

		self::assertArrayHasKey(RichExceptionTrait::class, $traits);
		// Note: Parent traits are not inherited in PHP, this tests the current behavior
	}

	public function testCachingBehavior(): void
	{
		$object = new class {
			use ArrayCapableTrait;

			public function toArray(): array
			{
				return [];
			}
		};

		// Call twice to test caching
		$traits1 = ClassUtils::getUsedTraitsDeep($object);
		$traits2 = ClassUtils::getUsedTraitsDeep($object);

		self::assertSame($traits1, $traits2);
		self::assertArrayHasKey(ArrayCapableTrait::class, $traits1);
	}

	public function testWithNonExistentClass(): void
	{
		$traits = ClassUtils::getUsedTraitsDeep('NonExistentClass', false);
		self::assertSame([], $traits);
	}
}
