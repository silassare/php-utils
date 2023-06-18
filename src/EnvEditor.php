<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils;

/**
 * Class EnvEditor.
 */
class EnvEditor
{
	/**
	 * EnvEditor constructor.
	 *
	 * @param array<int, array{type: 'comment'|'raw'|'value'|'var', value: string }> $file_structure
	 */
	public function __construct(protected array $file_structure)
	{
	}

	/**
	 * Magic string conversion.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		$str = '';

		foreach ($this->file_structure as $item) {
			$value = $item['value'];

			switch ($item['type']) {
				case 'var':
					$str .= $value . '=';

					break;

				case 'value':
					$quote = '';
					$head  =  \substr($value, 0, 1);
					$end   = \substr($value, -1);
					if ($head === $end && \in_array($head, ['"', "'"], true)) {
						$quote = $head;
						$value = \substr($value, 1, -1);
					}

					$value = \addcslashes($value, "\n\r\t\v\f\\" . $quote);

					$str .= $quote . $value . $quote;

					break;

				case 'comment':
					$str .= '#' . $value;

					break;

				case 'raw':
					$str .= $value;

					break;
			}
		}

		return $str;
	}

	/**
	 * Returns dot env file structure.
	 *
	 * @return array<int, array{type: 'comment'|'raw'|'value'|'var', value: string}>
	 */
	public function getStructure(): array
	{
		return $this->file_structure;
	}

	/**
	 * Updates the value of an existing key or adds a new key-value pair to the end of the file.
	 *
	 * @param string $key              the key to set
	 * @param string $value            the key value
	 * @param bool   $first_occurrence if true, the first occurrence of the key will be updated
	 *                                 if false, the last occurrence of the key will be updated
	 * @param bool   $quote            if true, the value will be quoted
	 *
	 * @return $this
	 */
	public function upset(string $key, string $value, bool $first_occurrence = false, bool $quote = false): self
	{
		if ($quote) {
			$value = '"' . \addcslashes($value, '"') . '"';
		}
		$index = -1;
		// find the index of the key
		foreach ($this->file_structure as $i => $item) {
			if ('var' === $item['type'] && \trim($item['value']) === $key) {
				$index = $i;
				if ($first_occurrence) {
					break;
				}
			}
		}

		if (-1 === $index) {
			// add the key-value pair to the end of the file
			$this->file_structure[] = [
				'type'  => 'raw',
				'value' => EnvParser::NEW_LINE,
			];
			$this->file_structure[] = [
				'type'  => 'var',
				'value' => $key,
			];
			$this->file_structure[] = [
				'type'  => 'value',
				'value' => $value,
			];

			return $this;
		}

		// find the next value index

		$next_value_index = -1;
		$len              = \count($this->file_structure);
		for ($i = $index + 1; $i < $len; ++$i) {
			if ('value' === $this->file_structure[$i]['type']) {
				$next_value_index = $i;

				break;
			}
		}

		if (-1 === $next_value_index) {
			// possible end of file
			$head = \array_slice($this->file_structure, 0, $index + 1);
			$tail = \array_slice($this->file_structure, $index + 1);

			$this->file_structure = [
				...$head,
				[
					'type'  => 'value',
					'value' => $value,
				],
				...$tail,
			];
		} else {
			// update the value
			$this->file_structure[$next_value_index]['value'] = $value;
		}

		return $this;
	}
}
