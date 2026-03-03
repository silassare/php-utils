<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Exceptions;

use Exception;
use PHPUnit\Framework\TestCase;
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Interfaces\RichExceptionInterface;
use stdClass;

/**
 * Class RuntimeExceptionTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class RuntimeExceptionTest extends TestCase
{
	public function testInheritsFromStandardRuntimeException(): void
	{
		$exception = new RuntimeException('Test message');

		self::assertInstanceOf(\RuntimeException::class, $exception);
		self::assertInstanceOf(RichExceptionInterface::class, $exception);
	}

	public function testConstructorWithMessageOnly(): void
	{
		$exception = new RuntimeException('Test message');

		self::assertSame('Test message', $exception->getMessage());
		self::assertSame(0, $exception->getCode());
		self::assertNull($exception->getPrevious());
		self::assertEmpty($exception->getData());
	}

	public function testConstructorWithData(): void
	{
		$data      = ['key' => 'value', 'number' => 123];
		$exception = new RuntimeException('Test message', $data);

		self::assertSame('Test message', $exception->getMessage());
		self::assertSame($data, $exception->getData());
	}

	public function testConstructorWithPrevious(): void
	{
		$previous  = new Exception('Previous exception');
		$exception = new RuntimeException('Test message', null, $previous);

		self::assertSame($previous, $exception->getPrevious());
	}

	public function testConstructorWithCode(): void
	{
		$exception = new RuntimeException('Test message', null, null, 500);

		self::assertSame(500, $exception->getCode());
	}

	public function testConstructorWithAllParameters(): void
	{
		$data      = ['error' => 'details'];
		$previous  = new Exception('Previous');
		$exception = new RuntimeException('Test message', $data, $previous, 404);

		self::assertSame('Test message', $exception->getMessage());
		self::assertSame($data, $exception->getData());
		self::assertSame($previous, $exception->getPrevious());
		self::assertSame(404, $exception->getCode());
	}

	public function testSetAndGetData(): void
	{
		$exception = new RuntimeException('Test message');
		$data      = ['new' => 'data'];

		$exception->setData($data);

		self::assertSame($data, $exception->getData());
	}

	public function testSuspectArray(): void
	{
		$exception   = new RuntimeException('Test message');
		$suspectData = ['suspect' => 'array'];

		$result = $exception->suspectArray($suspectData);

		self::assertSame($exception, $result);
		$data = $exception->getData(true);
		self::assertArrayHasKey('_suspect', $data);
		self::assertSame('array', $data['_suspect']['type']);
		self::assertSame($suspectData, $data['_suspect']['data']);
	}

	public function testSuspectArrayWithPath(): void
	{
		$exception   = new RuntimeException('Test message');
		$suspectData = ['item' => 'value'];

		$exception->suspectArray($suspectData, 'user.profile');

		$data = $exception->getData(true);
		self::assertSame('user.profile', $data['_suspect']['path']);
		self::assertSame($suspectData, $data['_suspect']['data']);
	}

	public function testSuspectObject(): void
	{
		$exception        = new RuntimeException('Test message');
		$object           = new stdClass();
		$object->property = 'value';

		$exception->suspectObject($object);

		$data = $exception->getData(true);
		self::assertArrayHasKey('_suspect', $data);
		self::assertSame('object', $data['_suspect']['type']);
	}

	public function testSuspectLocation(): void
	{
		$exception = new RuntimeException('Test message');
		$location  = ['file' => __FILE__, 'line' => __LINE__];

		$exception->suspectLocation($location);

		$data = $exception->getData(true);
		self::assertArrayHasKey('_suspect', $data);
		self::assertSame('location', $data['_suspect']['type']);
		self::assertSame(__FILE__, $data['_suspect']['location']['file']);
	}

	public function testSuspectCallable(): void
	{
		$exception = new RuntimeException('Test message');
		$callable  = static fn () => 'test';

		$exception->suspectCallable($callable);

		$data = $exception->getData(true);
		self::assertArrayHasKey('_suspect', $data);
		self::assertSame('callable', $data['_suspect']['type']);
	}

	public function testSuspect(): void
	{
		$exception = new RuntimeException('Test message');
		$source    = ['source' => 'information'];

		$exception->suspect($source);

		$data = $exception->getData(true);
		self::assertArrayHasKey('_suspect', $data);
		self::assertSame($source, $data['_suspect']);
	}

	public function testFluentInterface(): void
	{
		$exception = new RuntimeException('Test message');

		$result = $exception->suspect(['first' => 'suspect']);

		// suspectArray will override the previous suspect call
		$result2 = $result->suspectArray(['second' => 'suspect']);

		self::assertSame($exception, $result);
		self::assertSame($exception, $result2);

		$data = $exception->getData(true);
		self::assertArrayHasKey('_suspect', $data);
		// Should have the array suspect (last one set)
		self::assertSame('array', $data['_suspect']['type']);
	}

	public function testToString(): void
	{
		$exception = new RuntimeException('Test message');
		$exception->suspect(['error' => 'context']);

		$string = (string) $exception;

		self::assertStringContainsString('Test message', $string);
		self::assertStringContainsString('RuntimeException', $string);
	}

	public function testGetDataHidesSensitiveKeys(): void
	{
		$exception = new RuntimeException('Test message');
		$exception->suspect(['error' => 'context']);

		// getData() without flag hides _-prefixed keys
		$publicData = $exception->getData(false);
		self::assertArrayNotHasKey('_suspect', $publicData);

		// getData(true) exposes them
		$allData = $exception->getData(true);
		self::assertArrayHasKey('_suspect', $allData);
	}

	public function testMultipleSuspectsOverride(): void
	{
		$exception = new RuntimeException('Test message');

		$exception->suspect(['first' => 'suspect']);
		$exception->suspect(['second' => 'suspect']); // This will override the first

		$data = $exception->getData(true);
		self::assertSame(['second' => 'suspect'], $data['_suspect']);
	}
}
