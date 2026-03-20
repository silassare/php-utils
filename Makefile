.PHONY: test cs lint fix help

# = Tests

## Run the full test suite
test:
	vendor/bin/phpunit --testdox --do-not-cache-result

# = Code style

## Check code style
cs:
	vendor/bin/phpcs

## Lint with Psalm (static analysis) without using cache
lint:
	vendor/bin/psalm --no-cache

## Fix code style automatically
fix: lint
	vendor/bin/oliup-cs fix
