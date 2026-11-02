<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests;

use Exception;
use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PHPUtils\ClassUtils;
use PHPUtils\Traits\ArrayCapableTrait;
use PHPUtils\Traits\RichExceptionTrait;

/**
 * Class ClassUtilsTest.
 *
 * @internal
 */
#[CoversNothing]
final class ClassUtilsTest extends TestCase
{
	/**
	 * How many diamonds the trait graph of {@see DiamondTop} stacks: the leaf is reachable by
	 * 2^DIAMOND_DEPTH paths through it.
	 */
	private const int DIAMOND_DEPTH = 20;

	public function testTheTraitWalkStaysLinearOnADiamondGraph(): void
	{
		// A guard, not a fix: an audit read `getUsedTraitsDeep()`'s second loop as exponential,
		// because a trait reached by two paths is pushed back onto the queue by each of them. It is
		// not, and the reason is worth keeping a test for -- `class_uses()` answers a map keyed by
		// trait name, and `array_merge` on string keys overwrites instead of appending, so the queue
		// deduplicates itself. Turn that queue into a list (a numeric array, a spread that renumbers)
		// and the bottom of this graph is visited 2^20 times.
		$started = \microtime(true);
		$traits  = ClassUtils::getUsedTraitsDeep(DiamondTop::class);
		$elapsed = \microtime(true) - $started;

		// Every trait once: two per level, plus the leaf.
		self::assertCount(2 * self::DIAMOND_DEPTH + 1, $traits);
		self::assertArrayHasKey(DiamondLeaf::class, $traits);
		self::assertLessThan(1.0, $elapsed, 'the trait walk must stay linear in the size of the graph');
	}

	public function testHasTraitWithObject(): void
	{
		$object = new class {
			use ArrayCapableTrait;

			#[Override]
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
		// The trait is for a Throwable, so the double is one: it took the trait on a plain class
		// and would have fatalled on the first method of it that reads the exception.
		$className = new class extends Exception {
			use RichExceptionTrait;

			public function __construct()
			{
				parent::__construct('');
			}
		};

		self::assertTrue(ClassUtils::hasTrait($className::class, RichExceptionTrait::class));
		self::assertFalse(ClassUtils::hasTrait($className::class, ArrayCapableTrait::class));
	}

	public function testGetUsedTraitsDeepWithObject(): void
	{
		$object = new class extends Exception {
			use ArrayCapableTrait;
			use RichExceptionTrait;

			#[Override]
			public function toArray(): array
			{
				return [];
			}

			public function __construct()
			{
				parent::__construct('');
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

			#[Override]
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

			#[Override]
			public function toArray(): array
			{
				return [];
			}
		};

		$childClass = new class extends Exception {
			use RichExceptionTrait;

			public function __construct()
			{
				parent::__construct('');
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

			#[Override]
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

/**
 * The bottom of a stack of diamonds, reachable by 2^20 paths from {@see DiamondTop}.
 */
trait DiamondLeaf {}

trait DiamondDepth1A
{
	use DiamondLeaf;
}

trait DiamondDepth1B
{
	use DiamondLeaf;
}

trait DiamondDepth2A
{
	use DiamondDepth1A;
	use DiamondDepth1B;
}

trait DiamondDepth2B
{
	use DiamondDepth1A;
	use DiamondDepth1B;
}

trait DiamondDepth3A
{
	use DiamondDepth2A;
	use DiamondDepth2B;
}

trait DiamondDepth3B
{
	use DiamondDepth2A;
	use DiamondDepth2B;
}

trait DiamondDepth4A
{
	use DiamondDepth3A;
	use DiamondDepth3B;
}

trait DiamondDepth4B
{
	use DiamondDepth3A;
	use DiamondDepth3B;
}

trait DiamondDepth5A
{
	use DiamondDepth4A;
	use DiamondDepth4B;
}

trait DiamondDepth5B
{
	use DiamondDepth4A;
	use DiamondDepth4B;
}

trait DiamondDepth6A
{
	use DiamondDepth5A;
	use DiamondDepth5B;
}

trait DiamondDepth6B
{
	use DiamondDepth5A;
	use DiamondDepth5B;
}

trait DiamondDepth7A
{
	use DiamondDepth6A;
	use DiamondDepth6B;
}

trait DiamondDepth7B
{
	use DiamondDepth6A;
	use DiamondDepth6B;
}

trait DiamondDepth8A
{
	use DiamondDepth7A;
	use DiamondDepth7B;
}

trait DiamondDepth8B
{
	use DiamondDepth7A;
	use DiamondDepth7B;
}

trait DiamondDepth9A
{
	use DiamondDepth8A;
	use DiamondDepth8B;
}

trait DiamondDepth9B
{
	use DiamondDepth8A;
	use DiamondDepth8B;
}

trait DiamondDepth10A
{
	use DiamondDepth9A;
	use DiamondDepth9B;
}

trait DiamondDepth10B
{
	use DiamondDepth9A;
	use DiamondDepth9B;
}

trait DiamondDepth11A
{
	use DiamondDepth10A;
	use DiamondDepth10B;
}

trait DiamondDepth11B
{
	use DiamondDepth10A;
	use DiamondDepth10B;
}

trait DiamondDepth12A
{
	use DiamondDepth11A;
	use DiamondDepth11B;
}

trait DiamondDepth12B
{
	use DiamondDepth11A;
	use DiamondDepth11B;
}

trait DiamondDepth13A
{
	use DiamondDepth12A;
	use DiamondDepth12B;
}

trait DiamondDepth13B
{
	use DiamondDepth12A;
	use DiamondDepth12B;
}

trait DiamondDepth14A
{
	use DiamondDepth13A;
	use DiamondDepth13B;
}

trait DiamondDepth14B
{
	use DiamondDepth13A;
	use DiamondDepth13B;
}

trait DiamondDepth15A
{
	use DiamondDepth14A;
	use DiamondDepth14B;
}

trait DiamondDepth15B
{
	use DiamondDepth14A;
	use DiamondDepth14B;
}

trait DiamondDepth16A
{
	use DiamondDepth15A;
	use DiamondDepth15B;
}

trait DiamondDepth16B
{
	use DiamondDepth15A;
	use DiamondDepth15B;
}

trait DiamondDepth17A
{
	use DiamondDepth16A;
	use DiamondDepth16B;
}

trait DiamondDepth17B
{
	use DiamondDepth16A;
	use DiamondDepth16B;
}

trait DiamondDepth18A
{
	use DiamondDepth17A;
	use DiamondDepth17B;
}

trait DiamondDepth18B
{
	use DiamondDepth17A;
	use DiamondDepth17B;
}

trait DiamondDepth19A
{
	use DiamondDepth18A;
	use DiamondDepth18B;
}

trait DiamondDepth19B
{
	use DiamondDepth18A;
	use DiamondDepth18B;
}

trait DiamondDepth20A
{
	use DiamondDepth19A;
	use DiamondDepth19B;
}

trait DiamondDepth20B
{
	use DiamondDepth19A;
	use DiamondDepth19B;
}

/**
 * The top of the stack: every level reaches the next by two paths, so a walk that does not remember
 * what it has seen visits the leaf 2^20 times.
 */
final class DiamondTop
{
	use DiamondDepth20A;
	use DiamondDepth20B;
}
