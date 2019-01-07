<?php
namespace tests;

use Germania\ConfigReader\YamlConfigReader;

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
