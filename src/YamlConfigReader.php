<?php
namespace Germania\ConfigReader;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException as SymfonyYamlParseException;

class YamlConfigReader implements ConfigReaderInterface
{

    /**
     * @var string|null
     */
    public $ignore_key;

    /**
     * YAML parsing flags.
     * @see https://symfony.com/doc/current/components/yaml.html#advanced-usage-flags
     * @var int
     */
    public $yaml_flags = 0;


    /**
     * The merging function
     * @var callable|null
     */
    public $merger;


    /**
     * @param string $base_dir The config file directory, default: current work dir
     */
    public function __construct(
        /**
         * The config file directory
         */
        public $base_dir = null
    )
    {
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
     * Returns the merging function.
     */
    public function getMerger() : callable
    {
        if (empty($this->merger)) {
            $fn = (fn(...$per_file_values) => array_replace_recursive( ...$per_file_values ));
            $this->setMerger( $fn );
        }

        return $this->merger;
    }


    /**
     * Sets the merging function.
     */
    public function setMerger( callable $merger )
    {
        $this->merger = $merger;
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
            catch(SymfonyYamlParseException $symfonyYamlParseException) {
                $msg = sprintf("Could not parse '%s': %s", $file, $symfonyYamlParseException->getMessage());
                throw new ParseException( $msg, 0, $symfonyYamlParseException );
            }
        }, $files) ?: [];

        // Glue arrays, if needed
        if ($per_file_values === []):
            return [];
        endif;

        $result = ($this->getMerger())( ...$per_file_values );

        // Handle "ignore keys"
        return $this->removeIgnoreKey($this->ignore_key, $result);
    }



    protected function removeIgnoreKey( $key, $result)
    {
        if (!$key || !array_key_exists($key, $result)) {
            return $result;
        }

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
        $files = array_map(fn($file) => $this->base_dir // if basedir not empty ...
        ? implode(DIRECTORY_SEPARATOR, [ $this->base_dir, $file])
        : $file, $files);

        // Disclose those that are not readable
        return array_filter( $files, fn($file) => is_readable($file));
    }

}
