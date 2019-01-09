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
     * @param string $base_dir The config file directory, default: current work dir
     */
    public function __construct( $base_dir = null)
    {
        $this->base_dir = $base_dir;
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
                return (array) Yaml::parseFile( $file );
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
            return $per_file_values[0];
        else:
            return array_replace_recursive( ...$per_file_values );
        endif;
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
