# Changelog

This file starts at the 2.x development line. What came before it is the repository's tags, `1.0.0`
to `1.0.7`, and their history.

## Unreleased (2.x-dev)

### Fixed

- **A symlink is not the directory it names, and three recursive walks read it as one.** Found by
  auditing for the shape that killed a deploy in a consumer: `is_dir()` answers for a symlink's
  target, so a walk that trusts it leaves the tree it was given -- and one pointing at an ancestor
  never ends, opening a directory per turn until the process may open no more.

  - `FSUtils::rmdir()` descended through a directory symlink and **deleted what was on the other
    side**: a release directory holding `data -> /srv/app/data` had the live data emptied. It now
    unlinks the link and leaves the target alone.
  - `FSUtils::walk()` never closed what it opened -- `opendir()` with no `closedir()` anywhere in the
    function -- so a walk held one handle per level for its whole life, and a walk that returned
    early or threw held them to the end of the process. Closed in a `finally` by the frame that
    opened it, and a symlinked directory is reported to the walker as a leaf instead of descended
    into.
  - `FSUtils::cp()` copied a directory symlink as a whole tree of its own, and one pointing at an
    ancestor of the source recursed while `mkdir()` dug the destination deeper until the path length
    gave out. The link is now recreated as a link.
  - `FSUtils::getIterator()` and `FilesFilter::find()` passed `FOLLOW_SYMLINKS`, and neither SPL
    class keeps a set of what it has visited, so a cyclic tree yielded ever deeper paths for ever --
    including for `FSUtils::canRemove()`, which reads `find()` to decide whether a tree may be
    deleted. A check that cannot return is worse than no check. Symlinks are now listed and not
    followed, and `SKIP_DOTS` is on; a caller that wants the targets passes the flag itself and owns
    the cycle.

- **A recursion whose depth is the caller's data now says so instead of exhausting the stack.**
  `Store::merge()` walks while both sides are iterable at the same key, and a `Store` is
  `IteratorAggregate`, so two stores that each hold themselves descended into each other for ever.
  Bounded by `Store::MAX_MERGE_DEPTH` (64), with a message naming the limit.

- **An event that feeds itself is stopped, and one that re-dispatches itself is not.**
  `EventManager::dispatch()` had no notion of how deep it was. A listener that writes what makes it
  fire recursed with nothing to stop it; a listener that answers, sets a flag of its own and sends
  the event round again is a legitimate shape and shallow. So what is bounded is the depth --
  `EventManager::MAX_DISPATCH_DEPTH` (32), per event name, with the name in the message -- and not
  the re-entry.

- **`LockableTrait::getLock()` filled its memo after `createLock()` returned**, and `createLock()` is
  the documented place to inject a shared or custom lock, so what runs in it is a consumer's code.
  An override that asked for the lock it was creating found the memo still null and built again, for
  ever. It now refuses the re-entrant call by name.

### Breaking changes

- **PHP 8.5 is the minimum** (`"php": ">=8.5"`, was `>=8.0`). The package is the floor every other
  first-party package stands on, so it moves with them rather than holding them back.

- **Every class constant whose value settles its type is typed**, and PHP refuses a subclass that
  redeclares a typed constant without the type: `Event::RUN_FIRST` / `RUN_DEFAULT` / `RUN_LAST`
  (`int`), `FSUtils::DIRECTORY_PERMISSIONS` / `FILE_PERMISSIONS` / `READ_CHUNK_LENGTH` (`int`), and
  `EnvParser::NEW_LINE` / `ESCAPE_CHAR` / `DOUBLE_QUOTE` / `SINGLE_QUOTE` / `COMMENT_CHAR`
  (`string`). A class extending one of them and restating a constant must write the type too.

- **`Store::merge()` merges deeply.** It replaced a key whose value was itself a map, so merging
  `['a' => ['x' => 1]]` into `['a' => ['y' => 2]]` lost `y`. It now walks into arrays and objects
  on both sides (`Store::deepMerge()`), and a scalar still replaces a scalar. Code that relied on a
  nested map being replaced whole must set that key instead of merging it.

- **`RichExceptionTrait::setData()` returns `static`**, where it returned `void`, and
  `mergeData()` is new beside it; both are declared on `RichExceptionInterface`. An implementation
  that overrides either must match the signature.

- **`Str::interpolate()`'s opening delimiter parameter is `$open`**, not `$begin`. Only a caller
  passing it by name is affected.

- **The fluent methods return `static` instead of `self`** (`FSUtils`, `FilesFilter`,
  `EnvEditor::upset()`), and `RecordableTrait::__call()` declares `static` where it declared
  nothing. A caller sees no change; a subclass overriding one of them must widen its own return
  type to `static` too.

### Added

- **The 8.5 syntax, written by the shared coding standard.** The floor moved to PHP 8.5 and the
  fixer carried no version migration set, so each file kept the syntax of the year it was written.
  The standard now applies the 8.5 migration: `$x::class` over `get_class($x)`, `0o755` over
  `0755`, an arrow function over a closure that only returns, a heredoc indented with its code. Nine files here.

- **`Store\Map`**: a `Store` that is `ArrayAccess` and iterable, built on data taken by reference,
  with `lazyMerge()` -- the same result as `merge()` without its per-key cost. `merge()` reads and
  writes every key through a dot path, which made merging metadata most of the cost of building a
  definition; where no key can read as a path and no value is an object,
  `array_replace_recursive()` answers the same thing, and `lazyMerge()` falls back to `merge()`
  where it cannot.

- **`Interfaces\MetaCapableInterface` and `Traits\MetaCapableTrait`**: arbitrary metadata on any
  object, as `getMeta(): Map`, `mergeMeta(array|Map): static` and `setMetaKey(string, mixed):
  static`. The metadata `Map` is built on first use.

- **A lock system**, under `Lock\`: `Lock` and `PermanentLock`, the
  `Interfaces\LockInterface` / `LockableInterface` / `ReleasableLockInterface` they answer to, and
  the `Traits\LockableTrait` / `PermanentlyLockableTrait` an object locks itself with. A permanent
  lock cannot be released, which is what a built definition needs.

- **`PortablePattern`**: one pattern declared once and readable by both PHP and JavaScript, so a
  rule validated on the server and in the browser is the same rule rather than two spellings of it
  that drift.

- **`DotPath`**: a parsed path in the JavaScript dot and bracket notation
  (`foo[0].bar`, `foo['bar.baz']`), as a value object that keeps its segments.

### Fixed

- `FSUtils` no longer iterates `false`: the two `scandir()` calls fell through to a `foreach` when
  the directory could not be read, which PHP 8.5 reports.
- `StoreTrait` resolves a single-segment bracket-quoted path (`['a.b']`) instead of splitting it on
  the dot inside the quotes.
- `EnvEditor::upset()` quotes the value it writes for a key that was not there, instead of writing
  a new variable whose value was left unquoted.
- `PathUtils::resolve()` answers a clean absolute path.
- `DataAccess::getIterator()` iterates a copy of the data, and says so: writing through the
  iterator wrote into that copy and was silently lost. Reading and writing in place is what `get()`,
  `set()` and `next()` are for.
- `Str` and the rest no longer carry a `@param` or `@return` that only restates the signature: one
  source of truth for a type, which is the code.
