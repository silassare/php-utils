<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Traits;

use Closure;
use JsonException;
use JsonSerializable;
use PHPUtils\Str;
use ReflectionException;
use ReflectionFunction;
use Throwable;

/**
 * Trait RichExceptionTrait.
 */
trait RichExceptionTrait
{
	protected array $data = [];

	/**
	 * RichExceptionTrait constructor.
	 *
	 * @param string         $message  the exception message
	 * @param null|array     $data     additional exception data
	 * @param null|Throwable $previous previous throwable used for the exception chaining
	 * @param int            $code     the exception code
	 */
	public function __construct(string $message, ?array $data = null, ?Throwable $previous = null, int $code = 0)
	{
		parent::__construct($message, $code, $previous);

		$this->data = $data ?? [];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws JsonException
	 */
	public function __toString(): string
	{
		$data         = $this->getData(true);
		$suspect_type = $data['_suspect']['type'] ?? null;

		if ('object' === $suspect_type && !($data['_suspect']['data'] instanceof JsonSerializable)) {
			$data['_suspect']['data'] = \print_r($data['_suspect']['data'], true);
		}

		$data = \json_encode($data, \JSON_THROW_ON_ERROR);

		return <<<STRING
\tFile    : {$this->getFile()}
\tLine    : {$this->getLine()}
\tCode    : {$this->getCode()}
\tMessage : {$this->getMessage()}
\tData    : {$data}
\tTrace   : {$this->getTraceAsString()}
STRING;
	}

	/**
	 * Specify the suspected source of the error.
	 *
	 * This will help exception pretty debug page builder.
	 *
	 * @param array $source
	 *
	 * @return $this
	 */
	public function suspect(array $source): static
	{
		$this->data['_suspect'] = $source;

		return $this;
	}

	/**
	 * Specify the suspected source location of the error.
	 *
	 * This will help exception pretty debug page builder.
	 *
	 * @param array{file: string, line?: int, start?: int, end?: int} $location
	 *
	 * @return $this
	 */
	public function suspectLocation(array $location): static
	{
		$line  = $location['line'] ?? 0;
		$start = $location['start'] ?? 0;

		$this->data['_suspect'] = [
			'type'     => 'location',
			'location' => [
				'file'  => $location['file'] ?? 'unknown',
				'line'  => $line,
				'start' => $start,
				'end'   => $location['end'] ?? $start,
			],
		];

		return $this;
	}

	/**
	 * Specify the callable that cause the error.
	 *
	 * @param callable $suspect
	 *
	 * @return $this
	 */
	public function suspectCallable(callable $suspect): static
	{
		$location = null;

		try {
			$c = Closure::fromCallable($suspect);
			$r = new ReflectionFunction($c);
			// gets the line inside the closure
			// where the error started
			$closure_file     = $r->getFileName();
			$start_line       = $r->getStartLine();
			$end_line         = $r->getEndLine();
			$line_of_interest = null;

			$original_exception = $this;
			while ($p = $original_exception->getPrevious()) {
				$original_exception = $p;
			}

			$trace = $original_exception->getTrace();

			foreach ($trace as $t) {
				if (
					isset($t['file'], $t['line'])
					&& $t['file'] === $closure_file
					&& $t['line'] >= $start_line
					&& $t['line'] <= $end_line
				) {
					$line_of_interest = $t['line'];

					break;
				}
			}

			$location = [
				'file'  => $closure_file,
				// if the closure is not called, the line of interest is the first line of the closure
				'line'  => $line_of_interest ?? $start_line,
				'start' => $start_line,
				'end'   => $end_line,
			];
		} catch (ReflectionException) {
		}

		$this->data['_suspect'] = [
			'type'     => 'callable',
			'name'     => Str::callableName($suspect),
			'location' => $location,
		];

		return $this;
	}

	/**
	 * Specify the path to the entry in an array that cause the error.
	 *
	 * ```php
	 * <?php
	 *
	 * $data = ['foo' => ['bar' => ['fizz', 'target']]];
	 * $path = 'foo.bar.1';// the cause of the error is: 'target'
	 *
	 * ```
	 *
	 * @param array       $data
	 * @param null|string $path
	 *
	 * @return $this
	 */
	public function suspectArray(array $data, ?string $path = null): static
	{
		$this->data['_suspect'] = [
			'type' => 'array',
			'data' => $data,
			'path' => $path,
		];

		return $this;
	}

	/**
	 * Suspect an object.
	 *
	 * @param object      $object
	 * @param null|string $path
	 *
	 * @return $this
	 */
	public function suspectObject(object $object, ?string $path = null): static
	{
		$this->data['_suspect'] = [
			'type' => 'object',
			'data' => $object,
			'path' => $path,
		];

		return $this;
	}

	/**
	 * Gets data.
	 *
	 * We shouldn't expose all debug data to client, may contains sensitive data
	 * like table structure, table name etc, all sensitive data should be
	 * set with the sensitive data prefix.
	 *
	 * @param bool $show_sensitive
	 *
	 * @return array
	 */
	public function getData(bool $show_sensitive = false): array
	{
		if (!$show_sensitive) {
			$data = [];

			foreach ($this->data as $key => $value) {
				if (\is_int($key) || '_' !== ($key[0] ?? '')) {
					$data[$key] = $value;
				}
			}

			return $data;
		}

		return $this->data;
	}

	/**
	 * Sets debug data.
	 *
	 * @param array $data
	 */
	public function setData(array $data): void
	{
		$this->data = $data;
	}
}
