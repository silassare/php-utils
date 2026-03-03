<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Events;

use Closure;
use PHPUnit\Framework\TestCase;
use PHPUtils\Events\Event;
use PHPUtils\Events\EventManager;
use PHPUtils\Events\Interfaces\EventInterface;
use ReflectionClass;

/**
 * Class EventTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class EventTest extends TestCase
{
	protected function setUp(): void
	{
		// Clear any static state between tests
		$reflection        = new ReflectionClass(EventManager::class);
		$listenersProperty = $reflection->getProperty('listeners');
		$listenersProperty->setAccessible(true);
		$listenersProperty->setValue(null, []);
	}

	public function testStopPropagation(): void
	{
		$event = new Event();

		self::assertFalse($event->isPropagationStopped());

		$result = $event->stopPropagation();

		self::assertTrue($event->isPropagationStopped());
		self::assertSame($event, $result); // Test fluent interface
	}

	public function testSetAndGetPropagationStopper(): void
	{
		$event   = new Event();
		$stopper = static fn () => null;

		self::assertNull($event->getPropagationStopper());

		$result = $event->setPropagationStopper($stopper);

		self::assertSame($stopper, $event->getPropagationStopper());
		self::assertSame($event, $result); // Test fluent interface
	}

	public function testDispatchResetsStoppedState(): void
	{
		$event = new Event();
		$event->stopPropagation();

		self::assertTrue($event->isPropagationStopped());

		$event->dispatch();

		self::assertFalse($event->isPropagationStopped());
	}

	public function testListenAndDispatch(): void
	{
		$called        = false;
		$capturedEvent = null;

		$detacher = Event::listen(static function ($event) use (&$called, &$capturedEvent) {
			$called        = true;
			$capturedEvent = $event;
		});

		self::assertInstanceOf(Closure::class, $detacher);

		$event = new Event();
		$event->dispatch();

		self::assertTrue($called);
		self::assertSame($event, $capturedEvent);
	}

	public function testListenWithPriorities(): void
	{
		$order = [];

		Event::listen(static function () use (&$order) {
			$order[] = 'default';
		}, EventInterface::RUN_DEFAULT);

		Event::listen(static function () use (&$order) {
			$order[] = 'first';
		}, EventInterface::RUN_FIRST);

		Event::listen(static function () use (&$order) {
			$order[] = 'last';
		}, EventInterface::RUN_LAST);

		$event = new Event();
		$event->dispatch();

		self::assertSame(['first', 'default', 'last'], $order);
	}

	public function testListenWithChannel(): void
	{
		$called = false;

		Event::listen(static function () use (&$called) {
			$called = true;
		}, EventInterface::RUN_DEFAULT, 'test-channel');

		$event = new Event();

		// Dispatch without channel - should not trigger
		$event->dispatch();
		self::assertFalse($called);

		// Dispatch with channel - should trigger
		$event->dispatch(null, 'test-channel');
		self::assertTrue($called);
	}

	public function testStopPropagationDuringDispatch(): void
	{
		$firstCalled  = false;
		$secondCalled = false;

		Event::listen(static function ($event) use (&$firstCalled) {
			$firstCalled = true;
			$event->stopPropagation();
		}, EventInterface::RUN_FIRST);

		Event::listen(static function () use (&$secondCalled) {
			$secondCalled = true;
		}, EventInterface::RUN_DEFAULT);

		$event = new Event();
		$event->dispatch();

		self::assertTrue($firstCalled);
		self::assertFalse($secondCalled);
		self::assertTrue($event->isPropagationStopped());
	}

	public function testCustomExecutor(): void
	{
		$executorCalled = false;
		$listenerCalled = false;

		Event::listen(static function () use (&$listenerCalled) {
			$listenerCalled = true;
		});

		$executor = static function ($listener, $event) use (&$executorCalled) {
			$executorCalled = true;
			$listener($event);
		};

		$event = new Event();
		$event->dispatch($executor);

		self::assertTrue($executorCalled);
		self::assertTrue($listenerCalled);
	}

	public function testDetachListener(): void
	{
		$called = false;

		$detacher = Event::listen(static function () use (&$called) {
			$called = true;
		});

		// First dispatch should trigger
		$event = new Event();
		$event->dispatch();
		self::assertTrue($called);

		// Detach the listener
		$detacher();

		$called = false;

		// Second dispatch should not trigger
		$event->dispatch();
		self::assertFalse($called);
	}

	public function testMultipleListenersOfSamePriority(): void
	{
		$order = [];

		Event::listen(static function () use (&$order) {
			$order[] = 'first-listener';
		}, EventInterface::RUN_DEFAULT);

		Event::listen(static function () use (&$order) {
			$order[] = 'second-listener';
		}, EventInterface::RUN_DEFAULT);

		$event = new Event();
		$event->dispatch();

		self::assertSame(['first-listener', 'second-listener'], $order);
	}
}
