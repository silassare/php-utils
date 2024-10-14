<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Macro;

use PHPUtils\FuncUtils;

/**
 * Class ChainableProxy.
 */
class ChainableProxy
{
	public function __construct(protected MacroRecorder $recorder) {}

	/**
	 * @param string $method
	 * @param array  $args
	 *
	 * @return $this
	 */
	public function __call(string $method, array $args): static
	{
		$this->recorder->addRecord([
			'method' => $method,
			'args'   => $args,
			'loc'    => FuncUtils::getCallerLocation(),
		]);

		return $this;
	}

	/**
	 * @param string $method
	 * @param array  $args
	 *
	 * @return static
	 */
	public static function __callStatic(string $method, array $args): static
	{
		$instance = new static(new MacroRecorder());
		$instance->recorder->addRecord([
			'method' => $method,
			'args'   => $args,
			'loc'    => FuncUtils::getCallerLocation(),
		]);

		return $instance;
	}
}
