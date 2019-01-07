# Germania · ConfigReader

## Installation

```bash
$ composer require germania-kg/configreader
```

## Usage

The **YamlConfigReader** internally uses *array_replace_recursive*. If the given config files do not exist, nothing happens. The return value is an array in any case.

```php
<?php
use Germania\ConfigReader\YamlConfigReader;

$reader = new YamlConfigReader( "/path/to/configs");

// Returns array
$config = $reader("defaults.yaml", "optionals.yaml");
```





## Development

```bash
$ git clone https://github.com/GermaniaKG/ConfigReader.git configreader
$ cd configreader
$ composer install

# Run PhpUnit
$ composer test
```

