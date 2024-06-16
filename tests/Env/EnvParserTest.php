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
 * Class EnvParserTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class EnvParserTest extends TestCase
{
	public function testFromString(): void
	{
		$input    = '';
		$instance = EnvParser::fromString($input);

		self::assertInstanceOf(EnvParser::class, $instance);
	}

	public function testFromFile()
	{
		$instance = EnvParser::fromFile(TESTS_ASSETS_DIR . 'sample.env');

		self::assertInstanceOf(EnvParser::class, $instance);
	}

	public function testParser(): void
	{
		$instance = EnvParser::fromFile(TESTS_ASSETS_DIR . 'sample.env');

		self::assertSame([
			'S3_BUCKET'                   => 'env',
			'SECRET_KEY'                  => 'secret_key',
			'MESSAGE_TEMPLATE'            => "\n    Hello \${PERSON},\"\n\n    Nice to meet you!\n",
			'SECRET_HASH'                 => 'something-with-a-hash-#-this-is-not-a-comment',
			'INT'                         => 12,
			'FLOAT'                       => 12.90,
			'FALSE'                       => false,
			'TRUE'                        => true,
			'COMMENT'                     => '',
			'EMPTY'                       => '',
			'EMPTY_EVEN_WITH_WHITE_SPACE' => '',
			'RAW_ESCAPE_ESCAPE'           => "\\\\'",
			'TAB_IN_STR'                  => "\t",
			'TAB_ESCAPE'                  => '\t',
			'INTERPOLATE'                 => 'env.bucket.com',
			'INTERPOLATE_2'               => '${VAR_2}.bucket.com',
			'PASSWORD'                    => '!@G0${k}k',
		], $instance->getEnvs());
	}

	public function testMergeFromFile(): void
	{
		$instance = EnvParser::fromFile(TESTS_ASSETS_DIR . 'sample.env');

		$instance->mergeFromFile(TESTS_ASSETS_DIR . 'merge.env');

		self::assertSame($instance->getEnv('FLOAT'), 25.901);
		self::assertSame($instance->getEnv('INTERPOLATE'), 'env.fizz.com');
	}

	public function testCastNumeric(): void
	{
		$instance = EnvParser::fromFile(TESTS_ASSETS_DIR . 'sample.env');

		self::assertSame($instance->getEnv('FLOAT'), 12.9);

		$instance = EnvParser::fromFile(TESTS_ASSETS_DIR . 'sample.env', true, false);

		self::assertSame($instance->getEnv('FLOAT'), '12.90');
	}

	public function testCastBool(): void
	{
		$instance = EnvParser::fromFile(TESTS_ASSETS_DIR . 'sample.env');

		self::assertTrue($instance->getEnv('TRUE'));

		$instance = EnvParser::fromFile(TESTS_ASSETS_DIR . 'sample.env', false);

		self::assertSame($instance->getEnv('TRUE'), 'true');
	}
}
