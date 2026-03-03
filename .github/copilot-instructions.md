# PHP Utils Copilot Instructions

## Project Overview

PHP utility library providing reusable components across projects. Uses PHP 8.0+ features with strict typing throughout.

## Architecture Patterns

### Namespace & Organization

- Root namespace: `PHPUtils\`
- Functional groupings: `Str`, `ClassUtils`, `FuncUtils`, `DOM\`, `Env\`, `Events\`, `Exceptions\`, `FS\`, `Store\`, `Traits\`
- Mirror structure between `src/` and `tests/` directories
- PSR-4 autoloading with test namespace `PHPUtils\Tests\`

### Interface + Trait Pattern

Core design pattern used throughout:

- `ArrayCapableInterface` + `ArrayCapableTrait` for array/JSON conversion
- `RichExceptionInterface` + `RichExceptionTrait` for enhanced exceptions
- `EventInterface` for event system contracts

### Store System

Generic data container with:

- `Store<T>` class using PHP generics (`@template T of array|object`)
- `StoreNotEditable<T>` for read-only access — throws `RuntimeException` on any mutation
- `DataAccess<T>` internal class for raw get/set/remove/iterator operations
- `StoreTrait` for dot-notation `has()`, `get()`, `set()`, `remove()`, `parentOf()`
- Implements `ArrayAccess`, `IteratorAggregate`, `ArrayCapableInterface`

### Event System

- `EventManager` for centralized static event listener registry with priorities and channels
- Three priority levels: `RUN_FIRST = 1`, `RUN_DEFAULT = 2`, `RUN_LAST = 3` (reversed)
- `EventManager::listen()` returns a detach `Closure`
- Event classes extend `Event` which implements `EventInterface`
- Propagation can be stopped via `stopPropagation()`; stopper callable tracked for debugging

## Code Style Requirements

### File Structure

```php
<?php

/**
 * Copyright (c) 2021-present, Emile Silas Sare
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPUtils\[ComponentName];
```

### Key Conventions

- Always use `declare(strict_types=1)`
- PHP 8.0+ union types: `object|string`, `array|ArrayAccess`
- Method visibility defaults to `public static` for utilities
- Use `self::` for static calls within class, `static::` when subclass instantiation is intended
- Prefer `\is_string()` over `is_string()` (leading backslash for all built-ins)
- Use `null !== $x` / `false !== $x` for explicit null/false checks — avoid truthiness checks on mixed types
- Never use `empty()` on typed variables — use explicit `=== null || === ''` checks
- PHPDoc `@param non-empty-string` for all regex patterns passed to `preg_match`
- Add `/** @psalm-suppress Reason */` inline above the specific statement when suppressing known-safe Psalm coercions, not on the block

## Development Workflow

### Quality Tools

- **Code style**: `./csfix` (uses oliup-cs-php, NOT standard PHP CS Fixer)
- **Tests**: `./run_test` (PHPUnit 9.6 with `--testdox --do-not-cache-result`)
- **Static analysis**: `./vendor/bin/psalm --no-cache` (level 4, 0 errors expected)
- **Standards**: Uses Oliup CS ruleset, not PSR standards

### Testing Patterns

- Test classes in `tests/` mirror `src/` structure
- Extend `PHPUnit\Framework\TestCase`
- Use `@internal` and `@coversNothing` annotations
- Test methods follow `testMethodName()` convention
- Use `self::assertSame()` for strict comparisons
- When testing `RichExceptionTrait::getData()`, keys prefixed with `_` are **sensitive** and hidden by default — use `getData(true)` to retrieve them; `_suspect` is always sensitive
- `FuncUtils::getCallerLocation()` must be tested through a non-test helper method — when called directly from a PHPUnit test method, `$trace[1]` points to PHPUnit internals

### Key Files

- `composer.json`: Defines `PHPUtils\` autoloading and dev dependencies
- `phpcs.xml.dist`: References `vendor/oliup/oliup-cs-php/src` rules
- `psalm.xml`: Error level 4, analyzes `src/` directory only
- `csfix` & `run_test`: Custom shell scripts for common tasks
- `tests/assets/`: `.env` fixture files used by `EnvParserTest` and `EnvEditorTest`

## Component-Specific Notes

### Str Class

Static utility methods for string manipulation: encoding conversion, URL slug generation, accent removal, interpolation, method/class name conversion, callable name introspection via reflection.

### ClassUtils Class

Deep trait inspection with result caching in `$cache['deep_traits']`. Accepts both object instances and class-name strings. The do-while parent traversal uses the resolved `$c` string (not the original `$class` which may be an object) as the argument to `get_parent_class()`.

### FuncUtils Class

Stack trace utilities. `getCallerLocation()` returns `array{file: string, line: int}` describing where the **caller of the caller** is. Requires at least 2 frames in the debug backtrace and throws `RuntimeException` if file/line info is missing.

### PathUtils Class

Path resolution and normalisation with pluggable protocol resolvers via `registerResolver()`. Uses `DIRECTORY_SEPARATOR` as `DS`. `isRelative()` detects relative paths including `./`, `../`, and bare names. `resolve()` handles Unix, Windows drive letters, and custom protocols. `getProtocol()` returns the protocol prefix (e.g. `https`, `C`) or an empty string.

### FSUtils Class

Filesystem operations rooted at a configurable base path. All path arguments are resolved relative to the current root. Key methods: `cd()`, `cp()`, `mv()` (rename), `rm()`, `rmdir()`, `mkdir()`, `wf()`, `append()`, `prepend()`, `download()`, `walk()`, `info()`, `fullPathInfo()`. Default permissions: `0770` for directories, `0660` for files.

### FilesFilter Class

Chainable file filter/finder. All regex patterns (`name()`, `notName()`, `path()`, `notPath()`) are typed `@param non-empty-string` and validated with `preg_match` before storage. `check()` returns `bool`; `assert()` throws on failure. `find()` yields `string => SplFileInfo` pairs.

### EnvParser Class

Tokenising `.env` parser. Constructor parses immediately. `cast_bool` casts unquoted `true`/`false`; `cast_numeric` casts unquoted numbers — quoted values are always strings. Static factories `fromString()` and `fromFile()` use `new static()` to support subclassing. `mergeFromFile()` and `mergeFromString()` append with a separator comment and re-parse. `edit()` returns an `EnvEditor` instance backed by the token list.

### EnvEditor Class

Token-level `.env` editor. `upset()` updates the last occurrence of a key (or first if `$first_occurrence = true`), or appends a new key-value pair. Casting to string re-serialises all tokens preserving comments and whitespace.

### Event / EventManager Classes

`EventInterface` constants: `RUN_FIRST = 1`, `RUN_DEFAULT = 2`, `RUN_LAST = 3`. Listeners at `RUN_LAST` are called in reverse registration order. `listen()` returns a detach closure. `dispatch()` accepts an optional `$executor(callable, EventInterface): void` for wrapping each listener call. Use `null !== $executor` (not truthiness) when checking the executor.

### RichExceptionTrait / RuntimeException

Constructor signature: `__construct(string $message, ?array $data = null, ?Throwable $previous = null, int $code = 0)`. Suspect helpers: `suspect(array $source)`, `suspectLocation(array{file, line?, start?, end?})`, `suspectCallable(callable)`, `suspectArray(array, ?string $path)`, `suspectObject(object, ?string $path)`. All suspects are stored under `data['_suspect']` which is a sensitive key. `getData(bool $show_sensitive = false)` hides keys whose first character is `_` unless `$show_sensitive = true`.

### RecordableTrait

Records dynamic method calls (name, args, caller location) in `$this->calls`. `play(object $target)` replays them, throwing a `RuntimeException` with `suspectLocation()` set if the method is missing or throws.

### ArrayCapableTrait

Provides `jsonSerialize()` delegating to `toArray()`. Set `$json_empty_array_is_object = true` to serialise an empty result as `{}` rather than `[]`.

When adding new components, follow the established Interface + Trait pattern and maintain the strict typing throughout.
