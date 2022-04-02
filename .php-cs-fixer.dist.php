<?php

use OLIUP\CS\PhpCS;
use PhpCsFixer\Finder;

$finder = Finder::create();

$finder->in([
	__DIR__ . '/src',
	__DIR__ . '/tests',
]);

$header = <<<'EOF'
Copyright (c) 2021-present, Emile Silas Sare

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$rules = [
	'header_comment' => [
		'header'       => $header,
		'comment_type' => 'PHPDoc',
		'separate'     => 'both',
		'location'     => 'after_open'
	],
];

return (new PhpCS())->mergeRules($finder, $rules)
                    ->setRiskyAllowed(true);
