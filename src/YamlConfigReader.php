<?php
namespace Germania\ConfigReader;

use Symfony\Component\Yaml\Yaml;

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
        $values = array_map(function($file) {
            return (array) Yaml::parseFile( $file );
        }, $files);

        // Glue arrays, if needed
        if (empty($values)):
            return array();
        elseif (count($values) === 1):
            return array_replace_recursive( $values, array());
        else:
            return array_replace_recursive( ...$values );
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
