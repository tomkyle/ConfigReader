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

    public function setUp() : void
    {
        parent::setUp();
        $this->basedir = join(DIRECTORY_SEPARATOR, [ dirname(__DIR__), "mocks"]);
    }


    public function testInstantiationWithoutCtorArgument( )
    {
        $sut = new YamlConfigReader;

        $no_file = $this->createFilenameThatNotExists();

        $result = $sut( $no_file );
        $this->assertIsArray( $result);
    }


    #[DataProvider('provideIgnoreKeys')]
    public function testIgnoreKey( $ignore_key, $excluded_keys)
    {
        $sut = new YamlConfigReader( $this->basedir );

        $result1 = $sut( "ignore.yaml" );
        $this->assertArrayHasKey($ignore_key, $result1);

        // set ignore key
        $sut->setIgnoreKey( $ignore_key );
        $result2 = $sut( "ignore.yaml" );
        foreach($excluded_keys as $ik):
            $this->assertFalse( array_key_exists($ik, $result2));
        endforeach;
        $this->assertFalse( array_key_exists($ignore_key, $result2));
    }


    public static function provideIgnoreKeys()
    {
        return [
            [ "_ignore", array( "foo" ) ],
            [ "_ignoreMultipleKeys", array( "foo", "qux" ) ]
        ];
    }



    public function testYamlParsingOptions( )
    {
        $sut = new YamlConfigReader( $this->basedir );

        if (defined('\Symfony\Component\Yaml\Yaml::PARSE_DATETIME')):
            $sut->setYamlFlags( Yaml::PARSE_DATETIME );
            $result = $sut( "options.yaml" );

            // Assumptions
            $this->assertInstanceOf ( \DateTimeInterface::class,   $result['foo']);
            $this->assertInstanceOf ( \DateTimeInterface::class,   $result['bar']);
        else:
            $this->markTestSkipped('Yaml::PARSE_DATETIME does not exist; test not possible.');
        endif;
    }


    public function testInstantiationAndOnlyOneFile( )
    {
        $sut = new YamlConfigReader( $this->basedir );
        $result = $sut( "config_base.yaml" );

        // Assumptions
        $this->assertIsArray( $result);
        $this->assertArrayHasKey("a_string", $result);
        $this->assertArrayHasKey("an_array", $result);
        $this->assertEquals("bar",   $result['a_string']);
    }


    public function testInstantiationAndOverddingFiles( )
    {
        $sut = new YamlConfigReader( $this->basedir );

        $result = $sut( "config_base.yaml", "config_override.yaml" );

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
        $raw_override = $sut(  "config_override.yaml" );
        $this->assertEquals($result['assoc_array']['foo'], $raw_override['assoc_array']['foo']);
    }


    public function testOverridingMerging( )
    {
        $sut = new YamlConfigReader( $this->basedir );
        $sut->setMerger( function (... $configs ) {
            return array('numberOfConfigs' => count($configs));
        });

        $result = $sut( "config_base.yaml", "config_override.yaml" );

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
