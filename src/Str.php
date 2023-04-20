<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils;

use Closure;
use InvalidArgumentException;
use PHPUtils\Exceptions\RuntimeException;
use ReflectionFunction;

/**
 * Class Str.
 */
class Str
{
	/**
	 * Get callable name.
	 */
	public static function callableName(callable $fn): string
	{
		$c = Closure::fromCallable($fn);

		try {
			$r     = new ReflectionFunction($c);
			$class = $r->getClosureScopeClass();
			$name  = $r->getName();

			return $class ? $class->getName() . '::' . $name : $name;
		} catch (\ReflectionException) {
			return '--unable to get callable name--';
		}
	}

	/**
	 * Interpolates context values into the message placeholders.
	 */
	public static function interpolate(
		string $message,
		array $context = [],
		string $begin = '{',
		string $close = '}'
	): string {
		// build a replacement array with braces around the context keys
		$replace = [];

		foreach ($context as $key => $val) {
			// check that the value can be cast to string
			if (!\is_array($val) && (!\is_object($val) || \method_exists($val, '__toString'))) {
				$replace[$begin . $key . $close] = $val;
			}
		}

		// interpolate replacement values into the message and return
		return \strtr($message, $replace);
	}

	/**
	 * removes a given prefix from a given string.
	 *
	 * @param string $str
	 * @param string $prefix
	 *
	 * @return string
	 */
	public static function removePrefix(string $str, string $prefix): string
	{
		if ($str === $prefix) {
			return '';
		}

		if (self::hasPrefix($str, $prefix)) {
			$str = \substr($str, \strlen($prefix));
		}

		return $str;
	}

	/**
	 * removes a given suffix from a given string.
	 *
	 * @param string $str
	 * @param string $suffix
	 *
	 * @return string
	 */
	public static function removeSuffix(string $str, string $suffix): string
	{
		if ($str === $suffix) {
			return '';
		}

		if (self::hasSuffix($str, $suffix)) {
			$str = \substr($str, 0, \strlen($str) - \strlen($suffix));
		}

		return $str;
	}

	/**
	 * checks if a given string has a given suffix.
	 *
	 * @param string $str
	 * @param string $suffix
	 *
	 * @return bool
	 */
	public static function hasSuffix(string $str, string $suffix): bool
	{
		return '' !== $str && '' !== $suffix && \str_ends_with($str, $suffix);
	}

	/**
	 * checks if a given string has a given prefix.
	 *
	 * @param string $str
	 * @param string $prefix
	 *
	 * @return bool
	 */
	public static function hasPrefix(string $str, string $prefix): bool
	{
		return '' !== $str && '' !== $prefix && \str_starts_with($str, $prefix);
	}

	/**
	 * Change a string from one encoding to another.
	 *
	 * @param string $data your raw data
	 * @param string $from encoding of your data
	 * @param string $to   encoding you want
	 *
	 * @return false|string false if error
	 */
	public static function convertEncoding(string $data, string $from, string $to = 'UTF-8'): false|string
	{
		if (\function_exists('mb_convert_encoding')) {
			// alternatives
			$alt = ['windows-949' => 'EUC-KR', 'Windows-31J' => 'SJIS'];

			$from = $alt[$from] ?? $from;
			$to   = $alt[$to] ?? $to;

			return \mb_convert_encoding($data, $to, $from);
		}

		if (\function_exists('iconv')) {
			return \iconv($from, $to, $data);
		}

		throw new RuntimeException('Make sure PHP module "iconv" or "mbstring" are installed.');
	}

	/**
	 * converts to utf8.
	 *
	 * @param string $input the string to encode
	 *
	 * @return false|string
	 */
	public static function toUtf8(string $input): false|string
	{
		$from = null;

		if (\function_exists('mb_detect_encoding')) {
			/** @var string[] $encodings */
			$encodings = \mb_detect_order();
			$from      = \mb_detect_encoding($input, $encodings, true);
		}

		return self::convertEncoding($input, $from, 'UTF-8');
	}

	/**
	 * fix some encoding problems as we only use UTF-8.
	 *
	 * @param mixed $input       the input to fix
	 * @param bool  $encode_keys whether to encode keys if input is array or object
	 *
	 * @return mixed
	 */
	public static function encodeFix(mixed $input, bool $encode_keys = false): mixed
	{
		$result = null;

		if (\is_string($input)) {
			$result = self::toUtf8($input);
		} elseif (\is_array($input)) {
			$result = [];

			foreach ($input as $k => $v) {
				$key          = ($encode_keys) ? self::toUtf8($k) : $k;
				$result[$key] = self::encodeFix($v, $encode_keys);
			}
		} elseif (\is_object($input)) {
			$vars = \array_keys(\get_object_vars($input));

			foreach ($vars as $var) {
				$input->{$var} = self::encodeFix($input->{$var});
			}
		} else {
			return $input;
		}

		return $result;
	}

	/**
	 * Converts hexadecimal color code to rgb.
	 *
	 * @param string $hex_str    the hexadecimal code string
	 * @param bool   $get_string get result as string or in array
	 * @param string $separator  the separator to use default is ','
	 *
	 * @return array|string
	 */
	public static function hex2rgb(string $hex_str, bool $get_string = false, string $separator = ','): string|array
	{
		$hex_str   = \preg_replace('/[^0-9A-Fa-f]/', '', $hex_str); // Gets a proper hex string
		$rgb_array = [];

		if (6 === \strlen($hex_str)) {
			$color_val      = \hexdec($hex_str);
			$rgb_array['r'] = 0xFF & ($color_val >> 0x10);
			$rgb_array['g'] = 0xFF & ($color_val >> 0x8);
			$rgb_array['b'] = 0xFF & $color_val;
		} elseif (3 === \strlen($hex_str)) {
			$rgb_array['r'] = \hexdec($hex_str[0] . $hex_str[0]);
			$rgb_array['g'] = \hexdec($hex_str[1] . $hex_str[1]);
			$rgb_array['b'] = \hexdec($hex_str[2] . $hex_str[2]);
		} else {
			throw new InvalidArgumentException(\sprintf('Invalid color string: %s', $hex_str));
		}

		return $get_string ? \implode($separator, $rgb_array) : $rgb_array;
	}

	/**
	 * Creates URL Slug from string (ex: Post Title).
	 *
	 * @param string $string
	 * @param string $sep
	 *
	 * @return string
	 */
	public static function stringToURLSlug(string $string, string $sep = '-'): string
	{
		$string = \trim($string);
		$string = self::removeAccents($string);
		$string = \preg_replace('~[^a-zA-Z0-9-]+~', $sep, $string);
		$string = \preg_replace('~[' . $sep . ']{2,}~', $sep, $string);

		return \strtolower(\trim($string, '-'));
	}

	/**
	 * Removes accents from string.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function removeAccents(string $string): string
	{
		if (!\preg_match('/[\x80-\xff]/', $string)) {
			return $string;
		}

		$chars = [
			// Decompositions for Latin-1 Supplement
			\chr(195) . \chr(128) => 'A',
			\chr(195) . \chr(129) => 'A',
			\chr(195) . \chr(130) => 'A',
			\chr(195) . \chr(131) => 'A',
			\chr(195) . \chr(132) => 'A',
			\chr(195) . \chr(133) => 'A',
			\chr(195) . \chr(135) => 'C',
			\chr(195) . \chr(136) => 'E',
			\chr(195) . \chr(137) => 'E',
			\chr(195) . \chr(138) => 'E',
			\chr(195) . \chr(139) => 'E',
			\chr(195) . \chr(140) => 'I',
			\chr(195) . \chr(141) => 'I',
			\chr(195) . \chr(142) => 'I',
			\chr(195) . \chr(143) => 'I',
			\chr(195) . \chr(145) => 'N',
			\chr(195) . \chr(146) => 'O',
			\chr(195) . \chr(147) => 'O',
			\chr(195) . \chr(148) => 'O',
			\chr(195) . \chr(149) => 'O',
			\chr(195) . \chr(150) => 'O',
			\chr(195) . \chr(153) => 'U',
			\chr(195) . \chr(154) => 'U',
			\chr(195) . \chr(155) => 'U',
			\chr(195) . \chr(156) => 'U',
			\chr(195) . \chr(157) => 'Y',
			\chr(195) . \chr(159) => 's',
			\chr(195) . \chr(160) => 'a',
			\chr(195) . \chr(161) => 'a',
			\chr(195) . \chr(162) => 'a',
			\chr(195) . \chr(163) => 'a',
			\chr(195) . \chr(164) => 'a',
			\chr(195) . \chr(165) => 'a',
			\chr(195) . \chr(167) => 'c',
			\chr(195) . \chr(168) => 'e',
			\chr(195) . \chr(169) => 'e',
			\chr(195) . \chr(170) => 'e',
			\chr(195) . \chr(171) => 'e',
			\chr(195) . \chr(172) => 'i',
			\chr(195) . \chr(173) => 'i',
			\chr(195) . \chr(174) => 'i',
			\chr(195) . \chr(175) => 'i',
			\chr(195) . \chr(177) => 'n',
			\chr(195) . \chr(178) => 'o',
			\chr(195) . \chr(179) => 'o',
			\chr(195) . \chr(180) => 'o',
			\chr(195) . \chr(181) => 'o',
			\chr(195) . \chr(182) => 'o',
			\chr(195) . \chr(182) => 'o',
			\chr(195) . \chr(185) => 'u',
			\chr(195) . \chr(186) => 'u',
			\chr(195) . \chr(187) => 'u',
			\chr(195) . \chr(188) => 'u',
			\chr(195) . \chr(189) => 'y',
			\chr(195) . \chr(191) => 'y',
			// Decompositions for Latin Extended-A
			\chr(196) . \chr(128) => 'A',
			\chr(196) . \chr(129) => 'a',
			\chr(196) . \chr(130) => 'A',
			\chr(196) . \chr(131) => 'a',
			\chr(196) . \chr(132) => 'A',
			\chr(196) . \chr(133) => 'a',
			\chr(196) . \chr(134) => 'C',
			\chr(196) . \chr(135) => 'c',
			\chr(196) . \chr(136) => 'C',
			\chr(196) . \chr(137) => 'c',
			\chr(196) . \chr(138) => 'C',
			\chr(196) . \chr(139) => 'c',
			\chr(196) . \chr(140) => 'C',
			\chr(196) . \chr(141) => 'c',
			\chr(196) . \chr(142) => 'D',
			\chr(196) . \chr(143) => 'd',
			\chr(196) . \chr(144) => 'D',
			\chr(196) . \chr(145) => 'd',
			\chr(196) . \chr(146) => 'E',
			\chr(196) . \chr(147) => 'e',
			\chr(196) . \chr(148) => 'E',
			\chr(196) . \chr(149) => 'e',
			\chr(196) . \chr(150) => 'E',
			\chr(196) . \chr(151) => 'e',
			\chr(196) . \chr(152) => 'E',
			\chr(196) . \chr(153) => 'e',
			\chr(196) . \chr(154) => 'E',
			\chr(196) . \chr(155) => 'e',
			\chr(196) . \chr(156) => 'G',
			\chr(196) . \chr(157) => 'g',
			\chr(196) . \chr(158) => 'G',
			\chr(196) . \chr(159) => 'g',
			\chr(196) . \chr(160) => 'G',
			\chr(196) . \chr(161) => 'g',
			\chr(196) . \chr(162) => 'G',
			\chr(196) . \chr(163) => 'g',
			\chr(196) . \chr(164) => 'H',
			\chr(196) . \chr(165) => 'h',
			\chr(196) . \chr(166) => 'H',
			\chr(196) . \chr(167) => 'h',
			\chr(196) . \chr(168) => 'I',
			\chr(196) . \chr(169) => 'i',
			\chr(196) . \chr(170) => 'I',
			\chr(196) . \chr(171) => 'i',
			\chr(196) . \chr(172) => 'I',
			\chr(196) . \chr(173) => 'i',
			\chr(196) . \chr(174) => 'I',
			\chr(196) . \chr(175) => 'i',
			\chr(196) . \chr(176) => 'I',
			\chr(196) . \chr(177) => 'i',
			\chr(196) . \chr(178) => 'IJ',
			\chr(196) . \chr(179) => 'ij',
			\chr(196) . \chr(180) => 'J',
			\chr(196) . \chr(181) => 'j',
			\chr(196) . \chr(182) => 'K',
			\chr(196) . \chr(183) => 'k',
			\chr(196) . \chr(184) => 'k',
			\chr(196) . \chr(185) => 'L',
			\chr(196) . \chr(186) => 'l',
			\chr(196) . \chr(187) => 'L',
			\chr(196) . \chr(188) => 'l',
			\chr(196) . \chr(189) => 'L',
			\chr(196) . \chr(190) => 'l',
			\chr(196) . \chr(191) => 'L',
			\chr(197) . \chr(128) => 'l',
			\chr(197) . \chr(129) => 'L',
			\chr(197) . \chr(130) => 'l',
			\chr(197) . \chr(131) => 'N',
			\chr(197) . \chr(132) => 'n',
			\chr(197) . \chr(133) => 'N',
			\chr(197) . \chr(134) => 'n',
			\chr(197) . \chr(135) => 'N',
			\chr(197) . \chr(136) => 'n',
			\chr(197) . \chr(137) => 'N',
			\chr(197) . \chr(138) => 'n',
			\chr(197) . \chr(139) => 'N',
			\chr(197) . \chr(140) => 'O',
			\chr(197) . \chr(141) => 'o',
			\chr(197) . \chr(142) => 'O',
			\chr(197) . \chr(143) => 'o',
			\chr(197) . \chr(144) => 'O',
			\chr(197) . \chr(145) => 'o',
			\chr(197) . \chr(146) => 'OE',
			\chr(197) . \chr(147) => 'oe',
			\chr(197) . \chr(148) => 'R',
			\chr(197) . \chr(149) => 'r',
			\chr(197) . \chr(150) => 'R',
			\chr(197) . \chr(151) => 'r',
			\chr(197) . \chr(152) => 'R',
			\chr(197) . \chr(153) => 'r',
			\chr(197) . \chr(154) => 'S',
			\chr(197) . \chr(155) => 's',
			\chr(197) . \chr(156) => 'S',
			\chr(197) . \chr(157) => 's',
			\chr(197) . \chr(158) => 'S',
			\chr(197) . \chr(159) => 's',
			\chr(197) . \chr(160) => 'S',
			\chr(197) . \chr(161) => 's',
			\chr(197) . \chr(162) => 'T',
			\chr(197) . \chr(163) => 't',
			\chr(197) . \chr(164) => 'T',
			\chr(197) . \chr(165) => 't',
			\chr(197) . \chr(166) => 'T',
			\chr(197) . \chr(167) => 't',
			\chr(197) . \chr(168) => 'U',
			\chr(197) . \chr(169) => 'u',
			\chr(197) . \chr(170) => 'U',
			\chr(197) . \chr(171) => 'u',
			\chr(197) . \chr(172) => 'U',
			\chr(197) . \chr(173) => 'u',
			\chr(197) . \chr(174) => 'U',
			\chr(197) . \chr(175) => 'u',
			\chr(197) . \chr(176) => 'U',
			\chr(197) . \chr(177) => 'u',
			\chr(197) . \chr(178) => 'U',
			\chr(197) . \chr(179) => 'u',
			\chr(197) . \chr(180) => 'W',
			\chr(197) . \chr(181) => 'w',
			\chr(197) . \chr(182) => 'Y',
			\chr(197) . \chr(183) => 'y',
			\chr(197) . \chr(184) => 'Y',
			\chr(197) . \chr(185) => 'Z',
			\chr(197) . \chr(186) => 'z',
			\chr(197) . \chr(187) => 'Z',
			\chr(197) . \chr(188) => 'z',
			\chr(197) . \chr(189) => 'Z',
			\chr(197) . \chr(190) => 'z',
			\chr(197) . \chr(191) => 's',
		];

		return \strtr($string, $chars);
	}

	/**
	 * Converts string to methodName.
	 *
	 * example:
	 *    a_method_name        => aClassName
	 *    my_method_name       => myMethodName
	 *    my_method_name_id    => myMethodNameID
	 *    another_method_name  => anotherMethodName
	 *    a-relation-name     => aRelationName
	 *
	 * @param string $str the table or column name
	 *
	 * @return string
	 */
	public static function toMethodName(string $str): string
	{
		$str   = \str_replace('-', '_', $str);
		$parts = \explode('_', $str);
		$out   = '';

		foreach ($parts as $index => $part) {
			if (0 === $index) {
				$out .= $part;
			} else {
				$out .= \ucfirst($part);
			}
		}

		return $out;
	}

	/**
	 * Converts string to ClassName.
	 *
	 * example:
	 *    a_class_name        => AClassName
	 *    my_class_name       => MYClassName
	 *    my_class_name_id    => MYClassNameID
	 *    another_class_name  => AnotherClassName
	 *    a-relation-name     => ARelationName
	 *
	 * @param string $str the table or column name
	 *
	 * @return string
	 */
	public static function toClassName(string $str): string
	{
		$str   = \str_replace('-', '_', $str);
		$parts = \explode('_', $str);
		$out   = '';
		$c     = \count($parts);

		foreach ($parts as $index => $part) {
			if ((0 === $index || $index === ($c - 1)) && 2 === \strlen($part)) {
				$out .= \strtoupper($part);
			} else {
				$out .= \ucfirst($part);
			}
		}

		return $out;
	}

	/**
	 * Converts string to getterName.
	 *
	 * example:
	 *    var               => getVar
	 *    a_var             => getAVar
	 *    my_var_name       => getMyVarName
	 *    my_var_name_id    => getMyVarNameId
	 *    another_var_name  => getAnotherVarName
	 *    a-var-name        => getAVarName
	 *
	 * @param string $str the table or column name
	 *
	 * @return string
	 */
	public static function toGetterName(string $str): string
	{
		return self::toMethodName('get_' . $str);
	}

	/**
	 * Converts string to setterName.
	 *
	 * example:
	 *    var               => setVar
	 *    a_var             => setAVar
	 *    my_var_name       => setMyVarName
	 *    my_var_name_id    => setMyVarNameId
	 *    another_var_name  => setAnotherVarName
	 *    a-var-name        => setAVarName
	 *
	 * @param string $str the table or column name
	 *
	 * @return string
	 */
	public static function toSetterName(string $str): string
	{
		return self::toMethodName('set_' . $str);
	}

	/**
	 * Looks for a string from possibilities that is most similar to value, but not the same (for 8-bit encoding).
	 *
	 * @param string[] $possibilities
	 */
	public static function getSuggestion(array $possibilities, string $value): ?string
	{
		$best = null;
		$min  = (\strlen($value) / 4 + 1) * 10 + .1;
		foreach (\array_unique($possibilities) as $item) {
			if ($item !== $value && ($len = \levenshtein($item, $value, 10, 11, 10)) < $min) {
				$min  = $len;
				$best = $item;
			}
		}

		return $best;
	}

	/**
	 * Indent code with a given indent char.
	 *
	 * @param string $code
	 * @param string $indent_char
	 * @param int    $deep
	 * @param bool   $indent_empty_line
	 *
	 * @return string
	 */
	public static function indent(
		string $code,
		string $indent_char,
		int $deep = 1,
		bool $indent_empty_line = false
	): string {
		if ($deep && !empty($code)) {
			$indent = \str_repeat($indent_char, $deep);

			return $indent . ($indent_empty_line ? \preg_replace('~(\r\n?|\n)~', '$1' . $indent, $code)
			: \preg_replace('~(\r\n?|\n)([^\n\r])~', '$1' . $indent . '$2', $code));
		}

		return $code;
	}

	/**
	 * Un-indent code.
	 *
	 * @param string $code
	 * @param string $indent_char
	 * @param int    $deep
	 *
	 * @return string
	 */
	public static function unIndent(string $code, string $indent_char = '\t', int $deep = 1): string
	{
		$indent_char = empty($indent_char) ? '\t' : \preg_quote($indent_char, '~');

		if ($deep && !empty($code)) {
			return \preg_replace('~^(' . $indent_char . '){1,' . $deep . '}~m', '', $code);
		}

		return $code;
	}
}
