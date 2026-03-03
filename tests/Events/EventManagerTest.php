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
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUtils\Events\Event;
use PHPUtils\Events\EventManager;
use PHPUtils\Events\Interfaces\EventInterface;
use ReflectionClass;

/**
 * Class EventManagerTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class EventManagerTest extends TestCase
{
	protected function setUp(): void
	{
		// Clear any static state between tests
		$reflection        = new ReflectionClass(EventManager::class);
		$listenersProperty = $reflection->getProperty('listeners');
		$listenersProperty->setAccessible(true);
		$listenersProperty->setValue(null, []);
	}

	public function testListenAndDispatch(): void
	{
		$called        = false;
		$capturedEvent = null;

		$detacher = EventManager::listen(Event::class, static function ($event) use (&$called, &$capturedEvent) {
			$called        = true;
			$capturedEvent = $event;
		});

		self::assertInstanceOf(Closure::class, $detacher);

		$event = new Event();
		EventManager::dispatch($event);

		self::assertTrue($called);
		self::assertSame($event, $capturedEvent);
	}

	public function testListenWithInvalidPriority(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid priority');

		EventManager::listen(Event::class, static fn () => null, 999);
	}

	public function testDispatchWithDifferentPriorities(): void
	{
		$order = [];

		EventManager::listen(Event::class, static function () use (&$order) {
			$order[] = 'default';
		}, EventInterface::RUN_DEFAULT);

		EventManager::listen(Event::class, static function () use (&$order) {
			$order[] = 'first';
		}, EventInterface::RUN_FIRST);

		EventManager::listen(Event::class, static function () use (&$order) {
			$order[] = 'last';
		}, EventInterface::RUN_LAST);

		$event = new Event();
		EventManager::dispatch($event);

		// FIRST runs first, then DEFAULT, then LAST (reversed)
		self::assertSame(['first', 'default', 'last'], $order);
	}

	public function testDispatchWithChannels(): void
	{
		$defaultChannelCalled = false;
		$testChannelCalled    = false;

		EventManager::listen(Event::class, static function () use (&$defaultChannelCalled) {
			$defaultChannelCalled = true;
		});

		EventManager::listen(Event::class, static function () use (&$testChannelCalled) {
			$testChannelCalled = true;
		}, EventInterface::RUN_DEFAULT, 'test-channel');

		$event = new Event();

		// Dispatch to default channel
		EventManager::dispatch($event);
		self::assertTrue($defaultChannelCalled);
		self::assertFalse($testChannelCalled);

		// Reset flags
		$defaultChannelCalled = false;
		$testChannelCalled    = false;

		// Dispatch to test channel
		EventManager::dispatch($event, null, 'test-channel');
		self::assertFalse($defaultChannelCalled);
		self::assertTrue($testChannelCalled);
	}

	public function testDispatchWithCustomExecutor(): void
	{
		$executorData   = [];
		$listenerCalled = false;

		EventManager::listen(Event::class, static function () use (&$listenerCalled) {
			$listenerCalled = true;
		});

		$executor = static function ($listener, $event) use (&$executorData) {
			$executorData[] = ['listener' => $listener, 'event' => $event];
			$listener($event);
		};

		$event = new Event();
		EventManager::dispatch($event, $executor);

		self::assertTrue($listenerCalled);
		self::assertCount(1, $executorData);
		self::assertSame($event, $executorData[0]['event']);
		self::assertIsCallable($executorData[0]['listener']);
	}

	public function testStopPropagationSetsStopperAutomatically(): void
	{
		$firstListener = static function ($event) {
			$event->stopPropagation();
		};

		$secondListener = static function () {
			// Should not be called
		};

		EventManager::listen(Event::class, $firstListener, EventInterface::RUN_FIRST);
		EventManager::listen(Event::class, $secondListener, EventInterface::RUN_DEFAULT);

		$event = new Event();
		EventManager::dispatch($event);

		self::assertTrue($event->isPropagationStopped());
		self::assertSame($firstListener, $event->getPropagationStopper());
	}

	public function testStopPropagationDoesNotOverrideStopper(): void
	{
		$customStopper = static fn () => null;
		$firstListener = static function ($event) use ($customStopper) {
			$event->setPropagationStopper($customStopper);
			$event->stopPropagation();
		};

		EventManager::listen(Event::class, $firstListener, EventInterface::RUN_FIRST);

		$event = new Event();
		EventManager::dispatch($event);

		self::assertTrue($event->isPropagationStopped());
		self::assertSame($customStopper, $event->getPropagationStopper());
	}

	public function testDispatchWithNoListeners(): void
	{
		$event = new Event();

		// Should not throw any exceptions
		EventManager::dispatch($event);

		self::assertFalse($event->isPropagationStopped());
		self::assertNull($event->getPropagationStopper());
	}

	public function testDetachListener(): void
	{
		$called = false;

		$detacher = EventManager::listen(Event::class, static function () use (&$called) {
			$called = true;
		});

		// First dispatch should trigger
		$event = new Event();
		EventManager::dispatch($event);
		self::assertTrue($called);

		// Detach the listener
		$detacher();

		$called = false;

		// Second dispatch should not trigger
		EventManager::dispatch($event);
		self::assertFalse($called);
	}

	public function testMultipleListenersWithSamePriority(): void
	{
		$order = [];

		EventManager::listen(Event::class, static function () use (&$order) {
			$order[] = 'listener-1';
		});

		EventManager::listen(Event::class, static function () use (&$order) {
			$order[] = 'listener-2';
		});

		EventManager::listen(Event::class, static function () use (&$order) {
			$order[] = 'listener-3';
		});

		$event = new Event();
		EventManager::dispatch($event);

		self::assertSame(['listener-1', 'listener-2', 'listener-3'], $order);
	}

	public function testChannelNameFormatting(): void
	{
		$called = false;

		EventManager::listen(Event::class, static function () use (&$called) {
			$called = true;
		}, EventInterface::RUN_DEFAULT, '');

		$event = new Event();

		// Empty string channel should still work
		EventManager::dispatch($event, null, '');
		self::assertTrue($called);
	}
}
