<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Macro;

use PHPUtils\Exceptions\RuntimeException;
use Throwable;

/**
 * Class MacroRecorder.
 *
 * @template T
 */
class MacroRecorder
{
	/**
	 * @var array<array{method: string, args: array, loc: array{file:string, line:int}}>
	 */
	private array $records = [];

	/**
	 * Add a record to the call stack.
	 *
	 * @param array{method: string, args: array, loc: array{file:string, line:int}} $record
	 */
	public function addRecord(array $record): void
	{
		$this->records[] = $record;
	}

	/**
	 * Get the recorded call stack.
	 *
	 * @return array<array{method: string, args: array, loc: array{file:string, line:int}}>
	 */
	public function getRecords(): array
	{
		return $this->records;
	}

	/**
	 * Start recording a chain.
	 *
	 * @return T
	 */
	public function start()
	{
		$this->createProxy($proxy);

		return $proxy;
	}

	/**
	 * Run the recorded chain on the given object.
	 *
	 * @param T $target
	 */
	public function run($target)
	{
		foreach ($this->records as $record) {
			$method = $record['method'];
			$args   = $record['args'];

			try {
				$target = \call_user_func_array([$target, $method], $args);
			} catch (Throwable $t) {
				$this->records = [];

				throw (new RuntimeException($t->getMessage(), [], $t))->suspectLocation(
					$record['loc']['file'],
					$record['loc']['line']
				);
			}
		}

		$this->records = [];

		return $target;
	}

	/**
	 * Create a proxy object.
	 *
	 * @param mixed &$proxy
	 */
	private function createProxy(mixed &$proxy): void
	{
		$proxy = new ChainableProxy($this);
	}
}
