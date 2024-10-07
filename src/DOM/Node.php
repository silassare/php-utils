<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\DOM;

/**
 * Class  Node.
 */
abstract class Node
{
	/**
	 * To string magic method.
	 *
	 * @return string
	 */
	abstract public function __toString(): string;
}
