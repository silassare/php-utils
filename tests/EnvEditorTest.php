<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests;

use PHPUnit\Framework\TestCase;
use PHPUtils\EnvParser;

/**
 * Class EnvEditorTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class EnvEditorTest extends TestCase
{
	public function testUpset(): void
	{
		$content = <<<'EOF'
# comment
FOO=bar

S3_BUCKET=env
FOO=baz

EOF;

		$env  = EnvParser::fromString($content);
		$edit = $env->edit();
		$edit->upset('S3_BUCKET', 'env2');
		$edit->upset('FOO', 'bar2');

		$new_content = <<<'EOF'
# comment
FOO=bar

S3_BUCKET=env2
FOO=bar2

EOF;
		static::assertSame($new_content, (string) $edit);
	}
}
