<?php
namespace tests;

use Germania\ConfigReader\YamlConfigReader;
use Germania\ConfigReader\ParseException;
use Germania\ConfigReader\ConfigReaderExceptionInterface;
use Symfony\Component\Yaml\Yaml;

class YamlConfigReaderTest extends \PHPUnit\Framework\TestCase
{
    public $basedir;

    public function setUp()
    {
        parent::setUp();
        $this->basedir = join(DIRECTORY_SEPARATOR, [ dirname(__DIR__), "mock"]);
    }


    public function testInstantiationWithoutCtorArgument( )
    {
        $sut = new YamlConfigReader;

        $no_file = $this->createFilenameThatNotExists();

        $result = $sut( $no_file );
        $this->assertInternalType("array", $result);
    }


    public function testParseExceptionOnDoubleKey( )
    {
        $sut = new YamlConfigReader( $this->basedir );

        $this->expectException( ParseException::class );
        $this->expectException( ConfigReaderExceptionInterface::class );
        $sut( "err_doublekey.yaml" );
    }


    /**
     * @dataProvider provideIgnoreKeys
     */
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


    public function provideIgnoreKeys()
    {
        return [
            [ "_ignore", array( "foo" ) ],
            [ "_ignoreMultipleKeys", array( "foo", "qux" ) ]
        ];
    }



    public function testYamlParsingOptions( )
    {
        $sut = new YamlConfigReader( $this->basedir );
        $sut->setYamlFlags( Yaml::PARSE_DATETIME );
        $result = $sut( "options.yaml" );

        // Assumptions
        $this->assertInstanceOf ( \DateTime::class,   $result['foo']);
        $this->assertInstanceOf ( \DateTime::class,   $result['bar']);
    }


    public function testInstantiationAndOnlyOneFile( )
    {
        $sut = new YamlConfigReader( $this->basedir );
        $result = $sut( "config_base.yaml" );

        // Assumptions
        $this->assertInternalType("array",   $result);
        $this->assertArrayHasKey("a_string", $result);
        $this->assertArrayHasKey("an_array", $result);
        $this->assertEquals("bar",   $result['a_string']);
    }


    public function testInstantiationAndOverddingFiles( )
    {
        $sut = new YamlConfigReader( $this->basedir );
        $result = $sut( "config_base.yaml", "config_override.yaml" );

        // Assumptions
        $this->assertInternalType("array",   $result);
        $this->assertArrayHasKey("a_string", $result);
        $this->assertArrayHasKey("an_array", $result);
        $this->assertArrayHasKey("custom",   $result);

        $this->assertEquals("value", $result['custom']);
        $this->assertEquals("bar",   $result['a_string']);
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
