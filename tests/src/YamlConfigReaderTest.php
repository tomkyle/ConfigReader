<?php
namespace tests;

use Germania\ConfigReader\YamlConfigReader;
use Germania\ConfigReader\ParseException;
use Germania\ConfigReader\ConfigReaderExceptionInterface;
use Symfony\Component\Yaml\Yaml;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class YamlConfigReaderTest extends TestCase
{

    public $basedir;

    protected function setUp() : void
    {
        parent::setUp();
        $this->basedir = implode(DIRECTORY_SEPARATOR, [ dirname(__DIR__), "mocks"]);
    }


    public function testInstantiationWithoutCtorArgument( )
    {
        $yamlConfigReader = new YamlConfigReader;

        $no_file = $this->createFilenameThatNotExists();

        $result = $yamlConfigReader( $no_file );
        $this->assertIsArray( $result);
    }


    #[DataProvider('provideIgnoreKeys')]
    public function testIgnoreKey( $ignore_key, $excluded_keys)
    {
        $yamlConfigReader = new YamlConfigReader( $this->basedir );

        $result1 = $yamlConfigReader( "ignore.yaml" );
        $this->assertArrayHasKey($ignore_key, $result1);

        // set ignore key
        $yamlConfigReader->setIgnoreKey( $ignore_key );
        $result2 = $yamlConfigReader( "ignore.yaml" );
        foreach($excluded_keys as $excluded_key):
            $this->assertFalse( array_key_exists($excluded_key, $result2));
        endforeach;

        $this->assertFalse( array_key_exists($ignore_key, $result2));
    }


    public static function provideIgnoreKeys()
    {
        return [
            [ "_ignore", [ "foo" ] ],
            [ "_ignoreMultipleKeys", [ "foo", "qux" ] ]
        ];
    }



    public function testYamlParsingOptions( )
    {
        $yamlConfigReader = new YamlConfigReader( $this->basedir );

        if (defined(\Symfony\Component\Yaml\Yaml::class . '::PARSE_DATETIME')):
            $yamlConfigReader->setYamlFlags( Yaml::PARSE_DATETIME );
            $result = $yamlConfigReader( "options.yaml" );

            // Assumptions
            $this->assertInstanceOf ( \DateTimeInterface::class,   $result['foo']);
            $this->assertInstanceOf ( \DateTimeInterface::class,   $result['bar']);
        else:
            $this->markTestSkipped('Yaml::PARSE_DATETIME does not exist; test not possible.');
        endif;
    }


    public function testInstantiationAndOnlyOneFile( )
    {
        $yamlConfigReader = new YamlConfigReader( $this->basedir );
        $result = $yamlConfigReader( "config_base.yaml" );

        // Assumptions
        $this->assertIsArray( $result);
        $this->assertArrayHasKey("a_string", $result);
        $this->assertArrayHasKey("an_array", $result);
        $this->assertEquals("bar",   $result['a_string']);
    }


    public function testInstantiationAndOverddingFiles( )
    {
        $yamlConfigReader = new YamlConfigReader( $this->basedir );

        $result = $yamlConfigReader( "config_base.yaml", "config_override.yaml" );

        // Assumptions
        $this->assertIsArray( $result);
        $this->assertArrayHasKey("a_string", $result);
        $this->assertArrayHasKey("an_array", $result);
        $this->assertArrayHasKey("custom",   $result);
        $this->assertArrayHasKey("another_string",   $result);
        $this->assertArrayHasKey("assoc_array",   $result);

        $this->assertEquals("value", $result['custom']);
        $this->assertEquals("bar",   $result['a_string']);
        $this->assertEquals("mockingbird",   $result['another_string']);

        $this->assertEquals(3, count($result['assoc_array']));
        $raw_override = $yamlConfigReader(  "config_override.yaml" );
        $this->assertEquals($result['assoc_array']['foo'], $raw_override['assoc_array']['foo']);
    }


    public function testOverridingMerging( )
    {
        $yamlConfigReader = new YamlConfigReader( $this->basedir );
        $yamlConfigReader->setMerger( fn(... $configs) => ['numberOfConfigs' => count($configs)]);

        $result = $yamlConfigReader( "config_base.yaml", "config_override.yaml" );

        // Assumptions
        $this->assertIsArray( $result);
        $this->assertArrayHasKey("numberOfConfigs", $result);
        $this->assertEquals(2, $result['numberOfConfigs']);

    }


    protected function createFilenameThatNotExists()
    {
        // Create temp file with name that does not exist
        // and delete at once………
        $tmpfname = tempnam(sys_get_temp_dir(), 'FOO');
        unlink( $tmpfname );

        // Return that very file name that does not exist
        return $tmpfname;
    }
}
