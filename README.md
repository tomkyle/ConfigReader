# Germania · ConfigReader

**Merge default and custom configuration files with [Symfony YAML](https://symfony.com/doc/current/components/yaml.html)** 

[![PHP version](https://img.shields.io/packagist/php-v/germania-kg/configreader.svg)](https://packagist.org/packages/germania-kg/configreader)
[![Build Status](https://img.shields.io/travis/GermaniaKG/ConfigReader.svg?label=Travis%20CI)](https://travis-ci.org/GermaniaKG/ConfigReader)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GermaniaKG/ConfigReader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/ConfigReader/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/GermaniaKG/ConfigReader/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/ConfigReader/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/GermaniaKG/ConfigReader/badges/build.png?b=master)](https://scrutinizer-ci.com/g/GermaniaKG/ConfigReader/build-status/master)

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
$ git clone https://github.com/GermaniaKG/ConfigReader.git configreader
$ cd configreader
$ composer install

# Run PhpUnit
$ composer test
```

