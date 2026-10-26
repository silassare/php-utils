<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Store;

use PHPUtils\Store\Store;

/**
 * Class ChainableStore.
 *
 * A Store subclass that adds a method of its own, so that chaining one of Store's fluent
 * methods into it is checked: both that the call works, and that a static analyser still
 * sees the subclass on the other side of the chain.
 *
 * @extends Store<array>
 */
class ChainableStore extends Store
{
	/**
	 * Returns something only this subclass has.
	 */
	public function own(): string
	{
		return 'own';
	}
}
