<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests\Env;

use PHPUnit\Framework\TestCase;
use PHPUtils\Env\EnvParser;

/**
 * Class EnvEditorTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class EnvEditorTest extends TestCase
{
	public function testToString(): void
	{
		$content = <<<'EOF'
# comment
FOO="bar"

S3_BUCKET=env
FOO=baz

EOF;

		$env  = EnvParser::fromString($content);
		$edit = $env->edit();
		$edit->upset('S3_BUCKET', 'env2');
		$edit->upset('FOO', 'bar2');

		$new_content = <<<'EOF'
# comment
FOO="bar"

S3_BUCKET=env2
FOO=bar2

EOF;
		static::assertSame($new_content, (string) $edit);

		$with_merge = <<<'EOF'
# comment
FOO="bar"

S3_BUCKET=env
FOO=baz

# ----------------------------------------
# merged content from: raw string
# ----------------------------------------

# comment
FOO="bar"

S3_BUCKET=env2
FOO=bar2

EOF;

		$env->mergeFromString($new_content);

		static::assertSame($with_merge, (string) $env->edit());
	}
}
