<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

if (!\defined('DS')) {
	\define('DS', \DIRECTORY_SEPARATOR);
}

function assertException(Throwable $expected, callable $callable): void
{
	try {
		$callable();
	} catch (Throwable $actual) {
		$a = \get_class($expected);
		$b = \get_class($actual);
		TestCase::assertSame($a, $b, \sprintf('Expected exception class to be "%s" found "%s".', $a, $b));

		$a = $expected->getMessage();
		$b = $actual->getMessage();
		TestCase::assertSame($a, $b, \sprintf('Expected exception message to be "%s" found "%s".', $a, $b));

		$a = $expected->getCode();
		$b = $actual->getCode();
		TestCase::assertSame($a, $b, \sprintf('Expected exception code to be "%s" found "%s".', $a, $b));

		return;
	}

	TestCase::fail(\sprintf('Failed asserting that exception of type "%s" is thrown..', \get_class($expected)));
}
