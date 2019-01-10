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
$config = $reader("ignoring");

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

