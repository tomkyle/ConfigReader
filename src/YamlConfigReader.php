<?php
namespace Germania\ConfigReader;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException as SymfonyYamlParseException;

class YamlConfigReader
{

    /**
     * The config file directory
     * @var string
     */
    public $base_dir;

    /**
     * @var string|null
     */
    public $ignore_key = null;

    /**
     * YAML parsing flags.
     * @see https://symfony.com/doc/current/components/yaml.html#advanced-usage-flags
     * @var int
     */
    public $yaml_flags = 0;


    /**
     * @param string $base_dir The config file directory, default: current work dir
     */
    public function __construct( $base_dir = null)
    {
        $this->base_dir = $base_dir;
    }


    /**
     * Sets    the parsing flags for Simfony's YAML parser.
     * @see    https://symfony.com/doc/current/components/yaml.html#advanced-usage-flags
     * @param  int $flags
     * @return self
     */
    public function setYamlFlags( $flags )
    {
        $this->yaml_flags = $flags;
        return $this;
    }


    /**
     * @param string|null|false $key "Ignore" key, FALSE or NULL to reset
     * @return self
     */
    public function setIgnoreKey( $key = null)
    {
        $this->ignore_key = $key;
        return $this;
    }


    /**
     * @param string[] $files
     */
    public function __invoke( ... $files )
    {
        // Append "base" configs dir where the files can be found
        $files = $this->prepareFiles( $files );

        // Parse each file
        $per_file_values = array_map(function($file) {
            try {
                return (array) Yaml::parse(file_get_contents( $file), $this->yaml_flags );
            }
            catch(SymfonyYamlParseException $e) {
                $msg = sprintf("Could not parse '%s': %s", $file, $e->getMessage());
                throw new ParseException( $msg, 0, $e );
            }
        }, $files);

        // Glue arrays, if needed
        if (empty($per_file_values)):
            return array();
        elseif (count($per_file_values) === 1):
            $result = $per_file_values[0];
        else:
            $result = array_replace_recursive( ...$per_file_values );
        endif;

        // Handle "ignore keys"
        return $this->removeIgnoreKey($this->ignore_key, $result);
    }



    protected function removeIgnoreKey( $key, $result)
    {
        if (!$key
        or !array_key_exists($key, $result))
            return $result;

        $to_delete = (array) $result[ $key ];
        $to_delete[] = $key;
        $to_delete = array_flip(array_filter($to_delete));
        return array_diff_key( $result, $to_delete);
    }


    /**
     * @param string[] $files
     * @return array
     */
    public function prepareFiles( $files )
    {
        // Append "base" configs dir where the files can be found
        $files = array_map(function( $file ) {
            return $this->base_dir // if basedir not empty ...
            ? join(DIRECTORY_SEPARATOR, [ $this->base_dir, $file])
            : $file;
        }, $files);

        // Disclose those that are not readable
        return array_filter( $files, function( $file ) {
            return is_readable($file);
        });
    }

}
