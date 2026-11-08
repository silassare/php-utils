<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PHPUtils\Str;

/**
 * Class StrTest.
 *
 * @internal
 */
#[CoversNothing]
final class StrTest extends TestCase
{
	public function testCallableName(): void
	{
		$anonymous = static function (): void {};

		self::assertSame('PHPUtils\Str::toClassName', Str::callableName([Str::class, 'toClassName']));
		self::assertStringContainsString('closure', Str::callableName($anonymous));
		self::assertSame('PHPUtils\Tests\StrTest::testCallableName', Str::callableName([$this, 'testCallableName']));
	}

	public function testToMethodName(): void
	{
		$list = [
			'a_method_name'       => 'aMethodName',
			'my_method_name'      => 'myMethodName',
			'my_method_name_id'   => 'myMethodNameId',
			'my_filter_by_name'   => 'myFilterByName',
			'another_method_name' => 'anotherMethodName',
			'a-relation-name'     => 'aRelationName',
		];

		foreach ($list as $str => $expected) {
			self::assertSame($expected, Str::toMethodName($str));
		}
	}

	public function testToClassName(): void
	{
		$list = [
			'a_class_name'       => 'AClassName',
			'my_class_name'      => 'MYClassName',
			'my_class_name_id'   => 'MYClassNameID',
			'another_class_name' => 'AnotherClassName',
			'a-relation-name'    => 'ARelationName',
		];

		foreach ($list as $str => $expected) {
			self::assertSame($expected, Str::toClassName($str));
		}
	}

	public function testToGetterName(): void
	{
		$list = [
			'var'              => 'getVar',
			'a_var'            => 'getAVar',
			'my_var_name'      => 'getMyVarName',
			'my_var_name_id'   => 'getMyVarNameId',
			'another_var_name' => 'getAnotherVarName',
			'a-var-name'       => 'getAVarName',
		];

		foreach ($list as $str => $expected) {
			self::assertSame($expected, Str::toGetterName($str));
		}
	}

	public function testToSetterName(): void
	{
		$list = [
			'var'              => 'setVar',
			'a_var'            => 'setAVar',
			'my_var_name'      => 'setMyVarName',
			'my_var_name_id'   => 'setMyVarNameId',
			'another_var_name' => 'setAnotherVarName',
			'a-var-name'       => 'setAVarName',
		];

		foreach ($list as $str => $expected) {
			self::assertSame($expected, Str::toSetterName($str));
		}
	}

	public function testAUrlSlugCollapsesWhatItReplaced(): void
	{
		self::assertSame('hello-world-ok', Str::stringToURLSlug('  Hello  World!! ok  '));
		self::assertSame('hello_world_ok', Str::stringToURLSlug('Hello  World!! ok', '_'));
	}

	/**
	 * A separator is the caller's string and lands inside a character class, where some of them mean
	 * something: unquoted, these made a class that could not compile and the slug came back as a
	 * type error.
	 */
	public function testAUrlSlugTakesASeparatorARegularExpressionReadsAsSyntax(): void
	{
		$separators = [
			'^'  => 'hello^world',
			'['  => 'hello[world',
			'\\' => 'hello\\world',
			'-'  => 'hello-world',
		];

		foreach ($separators as $sep => $expected) {
			self::assertSame($expected, Str::stringToURLSlug('Hello World', $sep));
		}
	}

	public function testAColourIsReadFromWhatOfItIsHexadecimal(): void
	{
		self::assertSame('255,136,0', Str::hex2rgb('#ff8800', true));
		self::assertSame(['r' => 255, 'g' => 255, 'b' => 255], Str::hex2rgb('fff'));
	}

	public function testAColourThatIsNoneIsRefusedByWhatWasPassed(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid color string: #zz');

		Str::hex2rgb('#zz');
	}
}
