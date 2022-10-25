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

		static::assertInstanceOf(EnvParser::class, $instance);
	}

	public function testFromFile()
	{
		$instance = EnvParser::fromFile(__DIR__ . DS . 'assets/sample.env');

		static::assertInstanceOf(EnvParser::class, $instance);
	}

	public function testParser(): void
	{
		$instance = EnvParser::fromFile(__DIR__ . DS . 'assets/sample.env');

		$instance->parse();

		static::assertSame([
			'S3_BUCKET'        => 'env',
			'SECRET_KEY'       => 'secret_key',
			'PASSWORD'         => '!@G0${k}k',
			'MESSAGE_TEMPLATE' => "\n    Hello \${PERSON},\"\n\n    Nice to meet you!\n",
			'SECRET_HASH'      => 'something-with-a-hash-#-this-is-not-a-comment',
			'INT'              => 12,
			'FLOAT'            => 12.90,
			'EMPTY'            => "\\\\'",
			'FALSE'            => false,
			'TRUE'             => true,
			'COMMENT'          => '',
			'TAB_IN_STR'       => "\t",
			'TAB'              => '\\t',
			'INTERPOLATE'      => 'env.bucket.com',
			'INTERPOLATE_2'    => '${VAR_2}.bucket.com',
			'VAR_2'            => 'me',
		], $instance->getEnvs());
	}

	public function testMergeFromFile(): void
	{
		$instance = EnvParser::fromFile(__DIR__ . DS . 'assets/sample.env');

		$instance->parse()->mergeFromFile(__DIR__ . DS . 'assets/merge.env');

		static::assertSame($instance->getEnv('FLOAT'), 25.901);
		static::assertSame($instance->getEnv('INTERPOLATE'), 'env.fizz.com');
	}

	public function testCastNumeric(): void
	{
		$instance = EnvParser::fromFile(__DIR__ . DS . 'assets/sample.env');

		$instance->parse();

		static::assertSame($instance->getEnv('FLOAT'), 12.9);

		$instance->castNumeric(false)
			->parse();

		static::assertSame($instance->getEnv('FLOAT'), '12.90');
	}

	public function testCastBool(): void
	{
		$instance = EnvParser::fromFile(__DIR__ . DS . 'assets/sample.env');

		$instance->parse();

		static::assertTrue($instance->getEnv('TRUE'));

		$instance->castBool(false)
			->parse();

		static::assertSame($instance->getEnv('TRUE'), 'true');
	}
}
