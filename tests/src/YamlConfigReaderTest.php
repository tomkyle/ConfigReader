<?php
namespace tests;

use Germania\ConfigReader\YamlConfigReader;

class YamlConfigReaderTest extends \PHPUnit\Framework\TestCase
{

    public function testInstantiationWithoutCtorArgument( )
    {
        $sut = new YamlConfigReader;

        $no_file = $this->createFilenameThatNotExists();

        $result = $sut( $no_file );
        $this->assertInternalType("array", $result);
    }


    public function testInstantiationAndWorking( )
    {
        // The "mock" config files
        $mockdir = join(DIRECTORY_SEPARATOR, [ dirname(__DIR__), "mock"]);
        $files = array(
            "config_base.yaml",
            "config_override.yaml"
        );

        // Let ConfigReader do its work
        $sut = new YamlConfigReader( $mockdir );
        $result = $sut( ...$files );

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
        $tmpfname = tempnam(sys_get_temp_dir(), 'FOO'); // good
        unlink( $tmpfname );
        return $tmpfname;
    }
}
