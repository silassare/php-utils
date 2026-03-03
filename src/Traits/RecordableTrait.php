<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Traits;

use PHPUtils\Exceptions\RuntimeException;
use PHPUtils\FuncUtils;
use Throwable;

/**
 * Class RecordableTrait.
 */
trait RecordableTrait
{
	/**
	 * @var array<int, array{method: string, args: array, location: array}>
	 */
	public array $calls = [];

	/**
	 * Magic method to handle dynamic method calls.
	 *
	 * Records every method call with its name, arguments, and the caller location
	 * so they can be replayed later via {@see play()}.
	 *
	 * @param string $name      the method name that was called
	 * @param array  $arguments the list of arguments passed to the call
	 *
	 * @return $this
	 */
	public function __call(string $name, array $arguments)
	{
		$this->calls[]   = [
			'method'   => $name,
			'static'   => false,
			'args'     => $arguments,
			'location' => FuncUtils::getCallerLocation(),
		];

		return $this;
	}

	/**
	 * Executes the recorded method calls on the target object.
	 *
	 * @param object $target
	 */
	public function play(object $target): void
	{
		foreach ($this->calls as $call) {
			$method   = $call['method'];
			$args     = $call['args'];
			$location = $call['location'];

			if (!\method_exists($target, $method)) {
				throw (new RuntimeException(\sprintf(
					'Method "%s" does not exist on "%s".',
					$method,
					\get_class($target)
				)))->suspectLocation($location);
			}

			try {
				$target->{$method}(...$args);
			} catch (Throwable $e) {
				throw (new RuntimeException(\sprintf(
					'Error calling method "%s" on "%s".',
					$method,
					\get_class($target)
				), null, $e))->suspectLocation($location);
			}
		}
	}

	/**
	 * Clears the recorded method calls.
	 */
	protected function clearCalls(): void
	{
		$this->calls = [];
	}
}
