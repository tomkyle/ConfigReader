# Germania · ConfigReader

**Merge default and custom configuration files with [Symfony YAML](https://symfony.com/doc/current/components/yaml.html)** 

[![Packagist](https://img.shields.io/packagist/v/germania-kg/configreader.svg?style=flat)](https://packagist.org/packages/germania-kg/configreader)
[![PHP version](https://img.shields.io/packagist/php-v/germania-kg/configreader.svg)](https://packagist.org/packages/germania-kg/configreader)
[![Tests](https://github.com/GermaniaKG/ConfigReader/actions/workflows/tests.yml/badge.svg)](https://github.com/GermaniaKG/ConfigReader/actions/workflows/tests.yml)


## Installation

- v2 requires PHP 7.1 
- v3.0 requires PHP 7.2+
- v3.1 requires PHP 7.3+

```bash
$ composer require germania-kg/configreader
$ composer require germania-kg/configreader:^2.0
$ composer require germania-kg/configreader:^3.0
$ composer require germania-kg/configreader:^3.1
```



## Interfaces

The **ConfigReaderInterface** requires an *__invoke* method which may be called with an arbitrary number of filename strings:

```php
<?php
namespace Germania\ConfigReader;

interface ConfigReaderInterface
{
    public function __invoke( ... $files );
}
```



## Usage

### **YamlConfigReader**

The **YamlConfigReader** implemens *ConfigReaderInterface*. It internally uses *array_replace_recursive*. If the given config files do not exist, nothing happens. The return value is an array in any case.

```php
<?php
use Germania\ConfigReader\YamlConfigReader;

$reader = new YamlConfigReader( "/path/to/configs");

// Returns array
$config = $reader("defaults.yaml", "optionals.yaml");
```

#### PSR-6 Cache support

The **CacheConfigReader** also implements *ConfigReaderInterface* and combines a *ConfigReaderInterface* instance with PSR-6 Cache functionality. 

```php
<?php
use Germania\ConfigReader\YamlConfigReader;
use Germania\ConfigReader\CacheConfigReader;

$reader = new YamlConfigReader( "/path/to/configs");
$cache_item_pool = ... // PSR-6 CacheItemPool
$cache_lifetime = 3600;
$logger = ...
  
$cache_reader = new CacheConfigReader($reader, $cache_item_pool, $cache_lifetime);
$cache_reader = new CacheConfigReader($reader, $cache_item_pool, $cache_lifetime, $logger);

$config = $cache_reader("defaults.yaml", "optionals.yaml");
```



### YAML parsing options

The **setYamlFlags** method allows to set integer flags to be used by Symfony's YAML component. See official  docs for a list of possible values: [Symfony YAML component docs](https://symfony.com/doc/current/components/yaml.html#advanced-usage-flags). 

Ideas for using in config files:

- **Yaml::PARSE_CONSTANT** for evaluating constants created with `.env` configuration
-  **Yaml::PARSE_DATETIME** to save work with *string-to-DateTime* conversion

*Do not use* **Yaml::PARSE_OBJECT_FOR_MAP** as it will break the internal *array_replace_recursive* call. This is a good topic for future releases.

```php
<?php
use use Symfony\Component\Yaml\Yaml;

$reader = new YamlConfigReader( "/path/to/configs");
$reader->setYamlFlags( Yaml::PARSE_DATETIME | Yaml::PARSE_CONSTANT );
```



### Excluding results

Given a YAML map like this:

```yaml
# ignoring.yaml

# Exclude a single item:
_ignore: foo
# ... or even multiple items:
_ignore: 
  - foo
  - qux
  
foo:  bar
qux:  baz
name: john
```

To exclude a certain elements, use **setIgnoreKey** to set the name of a YAML map item *that contains the keys to exclude.* The result in our example will not contain neither `foo`nor `_ignore`. Be careful to not overuse this feature!

```php
$reader = new YamlConfigReader( "/path/to/configs");
$reader->setIgnoreKey( "_ignore" );
$config = $reader("ignoring.yaml");

# Will both be FALSE:
isset( $config["_ignore"])
isset( $config["foo"])

# Reset again
$reader->setIgnoreKey( null );
```



## Exceptions

When *YamlConfigReader* stumbles upon a *Symfony\Component\Yaml\Exception\ParseException*, it will catch it and wrap it in a **Germania\ConfigReader\ParseException**. This class implements **ConfigReaderExceptionInterface** you can watch out for:

```php
use Germania\ConfigReader\ConfigReaderExceptionInterface;
try {
	$config = $reader("defaults.yaml", "optionals.yaml");  
}
catch (ConfigReaderExceptionInterface $e)
{
  echo $e->getMessage();
}

```





## Development

```bash
$ git clone https://github.com/GermaniaKG/ConfigReader.git
$ cd ConfigReader
$ composer install
```

## Unit tests

Either copy `phpunit.xml.dist` to `phpunit.xml` and adapt to your needs, or leave as is. Run [PhpUnit](https://phpunit.de/) test or composer scripts like this:

```bash
$ composer test
# or
$ vendor/bin/phpunit
```

The test results logs are in the `tests/log` directory.

