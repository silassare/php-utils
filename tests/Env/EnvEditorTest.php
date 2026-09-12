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
	public function testUpsetAddsANewKey(): void
	{
		$env  = EnvParser::fromString("FOO=bar\n");
		$edit = $env->edit();
		$edit->upset('S3_BUCKET', 'env');

		// A new key used to render as `=env`: the name and the separating newline were built with a
		// value but no raw text, and `Token::__toString()` renders the raw text. Only existing keys
		// were ever edited in place, so nothing caught it -- and the file no longer parsed.
		self::assertSame("FOO=bar\n\nS3_BUCKET=env", (string) $edit);
	}

	public function testANewKeyIsReadBack(): void
	{
		$edit = EnvParser::fromString("FOO=bar\n")->edit();
		$edit->upset('S3_BUCKET', 'env');
		$edit->upset('S3_REGION', 'eu-west-1');

		// The point of writing it: the result has to parse, and hold what was put in.
		$reparsed = EnvParser::fromString((string) $edit);

		self::assertSame('bar', $reparsed->getEnv('FOO'));
		self::assertSame('env', $reparsed->getEnv('S3_BUCKET'));
		self::assertSame('eu-west-1', $reparsed->getEnv('S3_REGION'));
	}

	public function testUpsetQuotesWhenAsked(): void
	{
		$edit = EnvParser::fromString("FOO=bar\n")->edit();
		$edit->upset('GREETING', 'hello world # not a comment', false, true);

		// `$quote` reached the token as the semantic value instead of the raw text, so it never
		// reached the file: the value was written bare, and everything from the `#` read as a
		// comment on the way back.
		$reparsed = EnvParser::fromString((string) $edit);

		self::assertSame('hello world # not a comment', $reparsed->getEnv('GREETING'));
	}

	public function testUpsetQuotesAnExistingKeyToo(): void
	{
		$edit = EnvParser::fromString("GREETING=hi\n")->edit();
		$edit->upset('GREETING', 'hello world', false, true);

		$reparsed = EnvParser::fromString((string) $edit);

		self::assertSame('hello world', $reparsed->getEnv('GREETING'));
	}

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
		self::assertSame($new_content, (string) $edit);

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

		self::assertSame($with_merge, (string) $env->edit());
	}
}
