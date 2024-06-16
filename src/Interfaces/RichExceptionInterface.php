<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Interfaces;

use Throwable;

/**
 * Interface RichExceptionInterface.
 */
interface RichExceptionInterface extends Throwable
{
	/**
	 * RichExceptionTrait constructor.
	 *
	 * @param string         $message  the exception message
	 * @param null|array     $data     additional exception data
	 * @param null|Throwable $previous previous throwable used for the exception chaining
	 */
	public function __construct(string $message, ?array $data = null, ?Throwable $previous = null);

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
	public function getData(bool $show_sensitive = false): array;
}
