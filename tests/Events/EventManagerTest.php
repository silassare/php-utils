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
use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PHPUtils\Events\Event;
use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\Events\EventManager;
use PHPUtils\Events\Interfaces\EventInterface;
use ReflectionClass;

/**
 * Class EventManagerTest.
 *
 * @internal
 */
#[CoversNothing]
final class EventManagerTest extends TestCase
{
	#[Override]
	protected function setUp(): void
	{
		// Clear any static state between tests
		$reflection        = new ReflectionClass(EventManager::class);
		$listenersProperty = $reflection->getProperty('listeners');
		$listenersProperty->setValue(null, []);
	}

	public function testListenAndDispatch(): void
	{
		$called        = false;
		$capturedEvent = null;

		$detacher = EventManager::listen(Event::class, static function ($event) use (&$called, &$capturedEvent): void {
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

		EventManager::listen(Event::class, static function () use (&$order): void {
			$order[] = 'default';
		}, EventInterface::RUN_DEFAULT);

		EventManager::listen(Event::class, static function () use (&$order): void {
			$order[] = 'first';
		}, EventInterface::RUN_FIRST);

		EventManager::listen(Event::class, static function () use (&$order): void {
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

		EventManager::listen(Event::class, static function () use (&$defaultChannelCalled): void {
			$defaultChannelCalled = true;
		});

		EventManager::listen(Event::class, static function () use (&$testChannelCalled): void {
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

		EventManager::listen(Event::class, static function () use (&$listenerCalled): void {
			$listenerCalled = true;
		});

		$executor = static function ($listener, $event) use (&$executorData): void {
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
		$firstListener = static function ($event): void {
			$event->stopPropagation();
		};

		$secondListener = static function (): void {
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
		$firstListener = static function ($event) use ($customStopper): void {
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

		$detacher = EventManager::listen(Event::class, static function () use (&$called): void {
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

		EventManager::listen(Event::class, static function () use (&$order): void {
			$order[] = 'listener-1';
		});

		EventManager::listen(Event::class, static function () use (&$order): void {
			$order[] = 'listener-2';
		});

		EventManager::listen(Event::class, static function () use (&$order): void {
			$order[] = 'listener-3';
		});

		$event = new Event();
		EventManager::dispatch($event);

		self::assertSame(['listener-1', 'listener-2', 'listener-3'], $order);
	}

	public function testAListenerThatDispatchesItsOwnEventIsBoundedNotRefused(): void
	{
		// A listener that answers one way, sets a flag of its own and sends the event round again is
		// a legitimate shape and it is shallow -- a two-factor provider does exactly this. So what
		// is bounded is the depth, not the re-entry.
		$depths = [];
		$sent   = 0;

		EventManager::listen(Event::class, static function () use (&$depths, &$sent): void {
			$depths[] = ++$sent;

			if ($sent < 3) {
				EventManager::dispatch(new Event());
			}
		});

		EventManager::dispatch(new Event());

		self::assertSame([1, 2, 3], $depths);
	}

	public function testAListenerThatFeedsItselfStopsWithTheEventNamed(): void
	{
		// The runaway: a listener that writes what makes it fire. It used to recurse with nothing to
		// stop it -- one frame and one event object per turn -- until the stack or the memory gave
		// out, with no message naming what had happened.
		EventManager::listen(Event::class, static function (): void {
			EventManager::dispatch(new Event());
		});

		try {
			EventManager::dispatch(new Event());

			self::fail('An event feeding itself must be stopped.');
		} catch (RuntimeException $e) {
			self::assertStringContainsString(Event::class, $e->getMessage());
			self::assertStringContainsString('levels deep', $e->getMessage());
		}
	}

	public function testChannelNameFormatting(): void
	{
		$called = false;

		EventManager::listen(Event::class, static function () use (&$called): void {
			$called = true;
		}, EventInterface::RUN_DEFAULT, '');

		$event = new Event();

		// Empty string channel should still work
		EventManager::dispatch($event, null, '');
		self::assertTrue($called);
	}
}
