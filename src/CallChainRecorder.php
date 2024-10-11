<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils;

use PHPUtils\Exceptions\RuntimeException;
use Throwable;

/**
 * Class CallChainRecorder.
 */
class CallChainRecorder
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
	 * Record the chain on the given object.
	 *
	 * @param mixed &$recorder recorder object
	 */
	public function record(mixed &$recorder): void
	{
		$recorder = new class($this) {
			public function __construct(protected CallChainRecorder $recorder) {}

			/**
			 * @param string $method
			 * @param array  $args
			 *
			 * @return mixed
			 */
			public function __call(string $method, array $args)
			{
				$this->recorder->addRecord([
					'method' => $method,
					'args'   => $args,
					'loc'    => $this->getCallerLocation(),
				]);

				return $this;
			}

			/**
			 * @return array{file: string, line: int }
			 */
			private function getCallerLocation(): array
			{
				$trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

				$caller = $trace[1];

				return [
					'file'  => $caller['file'],
					'line'  => $caller['line'],
				];
			}
		};
	}

	/**
	 * Run the recorded chain on the given object.
	 */
	public function run(mixed $target)
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
}
