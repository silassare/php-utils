# PHP Utils

A PHP 8.1+ utility library providing reusable components across projects, with strict typing throughout.

## Requirements

- PHP ≥ 8.1
- `ext-json`
- `psr/http-message ^2.0`

## Installation

```bash
composer require silassare/php-utils
```

## Components

- [String utilities (`Str`)](#str)
- [Class introspection (`ClassUtils`)](#classutils)
- [Stack trace utilities (`FuncUtils`)](#funcutils)
- [Dot/bracket path value object (`DotPath`)](#dotpath)
- [HTML node builder (`DOM`)](#dom)
- [`.env` parser & editor (`Env`)](#env)
- [Event system (`Events`)](#events)
- [Rich exceptions (`Exceptions`)](#exceptions)
- [Filesystem utilities (`FS`)](#fs)
- [Interfaces](#interfaces)
- [Traits](#traits)
- [Key-value store (`Store`)](#store)

---

## Str

`PHPUtils\Str` — Static string manipulation utilities.

```php
use PHPUtils\Str;

// Placeholder interpolation
Str::interpolate('Hello, {name}!', ['name' => 'World']); // "Hello, World!"

// URL slug
Str::stringToURLSlug('Héllo Wörld'); // "hello-world"

// Naming conventions
Str::toMethodName('my_property');  // "myProperty"
Str::toClassName('my_component');  // "MyComponent"
Str::toGetterName('name');         // "getName"
Str::toSetterName('name');         // "setName"

// Prefix / suffix helpers
Str::hasPrefix('foobar', 'foo');    // true
Str::removePrefix('foobar', 'foo'); // "bar"
Str::hasSuffix('foobar', 'bar');    // true
Str::removeSuffix('foobar', 'bar'); // "foo"

// Closest match suggestion
Str::getSuggestion(['get', 'set', 'has'], 'haz'); // "has"

// Indentation
Str::indent("line1\nline2", '    ');   // 4-space indent
Str::unIndent("    line1\n    line2"); // removes leading tabs

// Encoding
Str::toUtf8($string);
Str::encodeFix($mixedValue);

// Hex color
Str::hex2rgb('#ff6600');               // [255, 102, 0]
Str::hex2rgb('#ff6600', true);         // "255,102,0"

// Callable name (uses reflection)
Str::callableName('array_map');        // "array_map"
Str::callableName([MyClass::class, 'method']); // "MyClass::method"
```

---

## ClassUtils

`PHPUtils\ClassUtils` — Deep trait introspection with result caching.

```php
use PHPUtils\ClassUtils;

// Check if a class (or any parent/trait) uses a trait
ClassUtils::hasTrait(MyClass::class, SomeTrait::class); // bool

// Get all traits used transitively across the full class hierarchy
$traits = ClassUtils::getUsedTraitsDeep(MyClass::class);
// ['TraitFQCN' => 'TraitFQCN', ...]
```

---

## FuncUtils

`PHPUtils\FuncUtils` — Stack-trace caller location.

```php
use PHPUtils\FuncUtils;

function myHelper(): array {
    return FuncUtils::getCallerLocation(); // ['file' => '...', 'line' => N]
}

// Returns the file and line of the code that called myHelper()
$location = myHelper();
```

---

## DotPath

`PHPUtils\DotPath` — Value object for parsed JS-like dot/bracket path strings.

**Supported syntax:**

| Syntax          | Example         | Description                           |
| --------------- | --------------- | ------------------------------------- |
| Plain           | `foo.bar`       | Segments matching `[a-zA-Z0-9_]+`     |
| Bracket integer | `items[0]`      | Array index access                    |
| Bracket quoted  | `map['my.key']` | Keys containing dots or special chars |

```php
use PHPUtils\DotPath;

$path = DotPath::parse('users[0].address.city');
$path->getSegments(); // ['users', '0', 'address', 'city']
(string) $path;       // "users.0.address.city"

// Keys with special characters
$path = DotPath::parse("config['db.host']");
$path->getSegments(); // ['config', 'db.host']
(string) $path;       // "config['db.host']"
```

---

## DOM

`PHPUtils\DOM\` — Lightweight HTML node builder.

```php
use PHPUtils\DOM\Tag;

$div = new Tag('div');
$div->setAttribute('class', 'container')
    ->addTextNode('Hello, World!')
    ->addCommentNode('Generated content');

echo $div;
// <div class="container">
// Hello, World!
// <!-- Generated content -->
// </div>

// Self-closing tag
$img = (new Tag('img', true))
    ->setAttribute('src', '/logo.png')
    ->setAttribute('alt', 'Logo');

echo $img; // <img src="/logo.png" alt="Logo"/>
```

---

## Env

`PHPUtils\Env\` — Tokenizing `.env` file parser and token-level editor.

### Parsing

```php
use PHPUtils\Env\EnvParser;

// From a file
$parser = EnvParser::fromFile('/path/to/.env');

// From a string
$parser = EnvParser::fromString("APP_ENV=production\nDEBUG=false");

// Get all values
$envs = $parser->getEnvs(); // ['APP_ENV' => 'production', 'DEBUG' => false]

// Get a single value
$env = $parser->getEnv('APP_ENV', 'local'); // 'production'

// Merge additional .env content
$parser->mergeFromFile('/path/to/.env.local');
```

**Casting rules:**

- Unquoted `true`/`false` → `bool` (when `cast_bool=true`)
- Unquoted numbers → `int`/`float` (when `cast_numeric=true`)
- Quoted values are always `string`

### Editing

```php
$editor = $parser->edit();

// Update or append a key
$editor->upset('APP_ENV', 'staging');
$editor->upset('NEW_KEY', 'value', quote: true); // wraps in double-quotes

// Serialize back to string (preserves comments and whitespace)
$updated = (string) $editor;
file_put_contents('/path/to/.env', $updated);
```

---

## Events

`PHPUtils\Events\` — Priority-based event system with channels.

### Priority levels

| Constant                      | Value | Description                                        |
| ----------------------------- | ----- | -------------------------------------------------- |
| `EventInterface::RUN_FIRST`   | `1`   | Runs before default listeners                      |
| `EventInterface::RUN_DEFAULT` | `2`   | Default priority                                   |
| `EventInterface::RUN_LAST`    | `3`   | Runs after all others (reverse registration order) |

### Usage

```php
use PHPUtils\Events\Event;
use PHPUtils\Events\EventManager;
use PHPUtils\Events\Interfaces\EventInterface;

// Define a custom event
class UserCreated extends Event {
    public function __construct(public readonly int $userId) {}
}

// Register a listener (returns a detach closure)
$detach = UserCreated::listen(function (UserCreated $event) {
    echo "User created: {$event->userId}";
});

// Dispatch the event
(new UserCreated(42))->dispatch();

// Detach the listener
$detach();

// Stop propagation
UserCreated::listen(function (UserCreated $event) {
    $event->stopPropagation();
}, EventInterface::RUN_FIRST);

// Scoped channels
UserCreated::listen($handler, channel: 'admin');
(new UserCreated(42))->dispatch(channel: 'admin');
```

---

## Exceptions

`PHPUtils\Exceptions\RuntimeException` — Rich exception with structured data and suspect tracking.

```php
use PHPUtils\Exceptions\RuntimeException;

throw (new RuntimeException('Invalid configuration', ['key' => 'db.host']))
    ->suspect(['value' => $value, 'expected' => 'string'])
    ->suspectLocation(['file' => __FILE__, 'line' => __LINE__]);

// Retrieve exception data
$e->getData();               // hides keys starting with '_'
$e->getData(true);           // shows all keys, including sensitive '_suspect'
```

**Suspect helpers:**

| Method                                                 | Description                                 |
| ------------------------------------------------------ | ------------------------------------------- |
| `suspect(array)`                                       | Set raw suspect data                        |
| `suspectLocation(['file', 'line?', 'start?', 'end?'])` | Point to a source location                  |
| `suspectCallable(callable)`                            | Record a callable's file/line range         |
| `suspectArray(array, ?string $path)`                   | Record an array (optionally with a DotPath) |
| `suspectObject(object, ?string $path)`                 | Record an object                            |

---

## FS

`PHPUtils\FS\` — Filesystem utilities.

### PathUtils

`PHPUtils\FS\PathUtils` — Static path resolution and normalisation.

```php
use PHPUtils\FS\PathUtils;

PathUtils::resolve('/var/www', 'html/index.php'); // "/var/www/html/index.php"
PathUtils::resolve('/var/www', '/etc/nginx.conf'); // "/etc/nginx.conf"
PathUtils::isRelative('./config');  // true
PathUtils::isRelative('/etc');      // false
PathUtils::getProtocol('https://example.com'); // "https"
PathUtils::getProtocol('/etc/hosts');           // ""

// Register a custom protocol resolver
PathUtils::registerResolver('storage', fn(string $path) => '/mnt/data/' . ltrim($path, '/'));
PathUtils::resolve('', 'storage://uploads/file.txt'); // "/mnt/data/uploads/file.txt"
```

### FSUtils

`PHPUtils\FS\FSUtils` — Filesystem operations rooted at a base path.

```php
use PHPUtils\FS\FSUtils;

$fs = new FSUtils('/var/www/project');

// Navigate
$fs->cd('public');              // change root
$fs->cd('cache', true);        // change root, auto-create if missing

// Write / read
$fs->wf('config.json', json_encode($data));
$fs->append('log.txt', "New entry\n");
$fs->prepend('log.txt', "Header\n");

// Copy, move, delete
$fs->cp('src/', 'dist/');
$fs->rm('temp.txt');
$fs->rmdir('cache/');

// Create directories and symlinks
$fs->mkdir('uploads/avatars');
$fs->ln('../shared/assets', 'assets');

// Download a remote file
$fs->download('https://example.com/file.zip', 'downloads/file.zip');

// Walk a directory tree
$fs->walk('src/', function (string $file, string $path, bool $isDir) {
    echo $path . PHP_EOL;
});

// File metadata
$info = $fs->info('uploads/');
$full = $fs->fullPathInfo('config.json');
```

### FilesFilter

`PHPUtils\FS\FilesFilter` — Chainable file finder / filter.

```php
use PHPUtils\FS\FSUtils;

$filter = (new FSUtils('/var/www/project'))->filter()
    ->isFile()
    ->name('/\.php$/')
    ->notPath('/vendor/')
    ->isReadable();

foreach ($filter->find() as $path => $fileInfo) {
    echo $path . PHP_EOL;
}

// Assert conditions (throws RuntimeException on failure)
$filter->assert('/var/www/project/index.php');

// Check conditions (returns bool)
if (!$filter->check('/var/www/project/index.php')) {
    echo $filter->getError();
}
```

---

## Interfaces

| Interface                                    | Description                                                              |
| -------------------------------------------- | ------------------------------------------------------------------------ |
| `PHPUtils\Interfaces\ArrayCapableInterface`  | Contracts `toArray(): array\|ArrayAccess` and `jsonSerialize()`.         |
| `PHPUtils\Interfaces\LockInterface`          | Irreversible lock contract: `lock()`, `isLocked()`, `assertNotLocked()`. |
| `PHPUtils\Interfaces\RichExceptionInterface` | Rich exception contract with `getData(bool $show_sensitive)`.            |

---

## Traits

| Trait                                | Description                                                                                                                               |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `PHPUtils\Traits\ArrayCapableTrait`  | Implements `jsonSerialize()` by delegating to `toArray()`. Set `$json_empty_array_is_object = true` to serialize an empty result as `{}`. |
| `PHPUtils\Traits\LockTrait`          | Implements `LockInterface`.                                                                                                               |
| `PHPUtils\Traits\RichExceptionTrait` | Full implementation of `RichExceptionInterface` with suspect tracking.                                                                    |
| `PHPUtils\Traits\RecordableTrait`    | Records dynamic method calls via `__call()` and replays them on another object via `play($target)`.                                       |

### RecordableTrait

```php
use PHPUtils\Traits\RecordableTrait;

class QueryBuilder {
    use RecordableTrait;
}

$builder = new QueryBuilder();
$builder->where('id', 1)->orderBy('name'); // calls recorded

// Replay all calls on a real target
$realBuilder = new RealQueryBuilder();
$builder->play($realBuilder);
```

---

## Store

`PHPUtils\Store\` — Generic data containers with dot/bracket-notation path access.

### Store (editable)

```php
use PHPUtils\Store\Store;

$store = new Store(['user' => ['name' => 'Alice', 'roles' => ['admin']]]);

$store->get('user.name');           // "Alice"
$store->get('user.roles[0]');      // "admin"
$store->has('user.age');            // false

$store->set('user.age', 30);
$store->set("config['db.host']", 'localhost'); // bracket notation for keys with dots

$store->remove('user.roles[0]');

// ArrayAccess
$store['user.name'] = 'Bob';
echo $store['user.name']; // "Bob"

// Magic properties
$store->user; // same as $store->get('user')

// Merge
$store->merge(['user' => ['email' => 'bob@example.com']]);

// Dump to array
$store->toArray();
```

### StoreNotEditable (read-only)

```php
use PHPUtils\Store\StoreNotEditable;

$store = new StoreNotEditable($data);
$store->get('key'); // OK
$store->set('key', 'value'); // throws RuntimeException
```

---

## License

MIT — see [LICENSE](LICENSE) for details.
