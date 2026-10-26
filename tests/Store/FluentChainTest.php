<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Store;

use PHPUnit\Framework\TestCase;

/**
 * Class FluentChainTest.
 *
 * Store is a generic class, and its fluent methods return `static`. A subclass must therefore
 * come back out of them, at runtime and to a static analyser alike: psalm reads a generic
 * class's native `static` as the class that declares it unless the method also says
 * `@return static`, so dropping that tag turns every chain below into an undefined method.
 * psalm reads this file (`psalm.xml`), which is the half of the guard a test cannot be.
 *
 * @internal
 *
 * @coversNothing
 */
final class FluentChainTest extends TestCase
{
	public function testEachFluentMethodGivesTheSubclassBack(): void
	{
		$store = new ChainableStore(['a' => 1]);

		self::assertSame('own', $store->set('b', 2)->own());
		self::assertSame('own', $store->merge(['c' => 3])->own());
		self::assertSame('own', $store->remove('a')->own());
		self::assertSame('own', $store->setData(['d' => 4])->own());
	}

	public function testAChainStillWritesThroughToTheData(): void
	{
		$store = new ChainableStore([]);

		$store->set('one.two', 'v')
			->merge(['three' => 'w']);

		self::assertSame('v', $store->get('one.two'));
		self::assertSame('w', $store->get('three'));
	}
}
