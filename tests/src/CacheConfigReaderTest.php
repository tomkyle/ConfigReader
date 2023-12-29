<?php
namespace tests;

use Germania\ConfigReader\CacheConfigReader;
use Germania\ConfigReader\ConfigReaderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class CacheConfigReaderTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public $logger;

    public function setUp() : void
    {
        $this->logger = new Logger("CacheConfigReaderTest", [
            new StreamHandler('php://stdout', \Psr\Log\LogLevel::DEBUG)
        ]);

    }

    public function testInstantiation( )
    {
        $reader_mock = $this->prophesize( ConfigReaderInterface::class );
        $reader = $reader_mock->reveal();

        $cache_itempool_mock = $this->prophesize( CacheItemPoolInterface::class );
        $cache_itempool = $cache_itempool_mock->reveal();

        $sut = new CacheConfigReader($reader, $cache_itempool, 10, $this->logger );
        $this->assertInstanceOf( ConfigReaderInterface::class, $sut);
    }


    public function testWithEmptyCacheLifetime()
    {
        $reader_mock = $this->prophesize( ConfigReaderInterface::class );
        $reader_mock->__invoke(Argument::any())->shouldBeCalled();
        $reader = $reader_mock->reveal();

        $cache_itempool_mock = $this->prophesize( CacheItemPoolInterface::class );
        $cache_itempool = $cache_itempool_mock->reveal();

        $cache_lifetime = 0;

        $sut = new CacheConfigReader($reader, $cache_itempool, $cache_lifetime, $this->logger );
        $sut("foo");
    }


    /**
     * @dataProvider provideCacheKey
     */
    public function testNormal($key, $expected_result, $is_cache_hit, $cache_lifetime)
    {
        $reader_mock = $this->prophesize( ConfigReaderInterface::class );

        if (!$is_cache_hit) {
            $reader_mock->__invoke($key)->shouldBeCalled();
        }
        $reader_mock->__invoke($key)->wilLReturn($expected_result);
        $reader = $reader_mock->reveal();


        // Prepare CacheItem
        $cache_item_mock = $this->prophesize( CacheItemInterface::class );
        $cache_item_mock->isHit()->willReturn( $is_cache_hit );
        $cache_item_mock->get()->willReturn( $expected_result );

        if (!$is_cache_hit) {
            $cache_item_mock->set(Argument::type("string"))->willReturn($cache_item_mock);
            $cache_item_mock->expiresAfter($cache_lifetime)->willReturn($cache_item_mock);
        }
        $cache_item = $cache_item_mock->reveal();


        // Prepare CacheItemPool
        $cache_itempool_mock = $this->prophesize( CacheItemPoolInterface::class );
        $cache_itempool_mock->getItem(Argument::type("string"))->willReturn($cache_item);

        if (!$is_cache_hit) {
            $cache_itempool_mock->save(Argument::any())->shouldBeCalled();
        }

        $cache_itempool = $cache_itempool_mock->reveal();


        $sut = new CacheConfigReader($reader, $cache_itempool, $cache_lifetime, $this->logger );
        $result = $sut( $key );

        $this->assertEquals($result, $expected_result);
    }

    public static function provideCacheKey()
    {

        return array(
            [ "foo", "bar", true, 99],
            [ "foo", "bar", true, 0],
            [ "foo", "bar", false, 100]
        );
    }


}
