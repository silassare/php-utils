<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Store;

use ArrayAccess;
use IteratorAggregate;

/**
 * Class Map.
 *
 * @template TOf of mixed
 *
 * @extends Store<array<string, TOf>>
 *
 * @implements ArrayAccess<string, TOf>
 * @implements IteratorAggregate<string, TOf>
 */
class Map extends Store implements ArrayAccess, IteratorAggregate
{
	/**
	 * Map constructor.
	 */
	public function __construct(array &$data = [])
	{
		$this->json_empty_array_is_object = true;

		parent::__construct($data);
	}
}
