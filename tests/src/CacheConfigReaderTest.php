<?php
namespace tests;

use Germania\ConfigReader\CacheConfigReader;
use Germania\ConfigReader\ConfigReaderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CacheConfigReaderTest extends TestCase
{

    public $logger;

    protected function setUp() : void
    {
        $this->logger = new Logger("CacheConfigReaderTest", [
            new StreamHandler('php://stdout', \Psr\Log\LogLevel::DEBUG)
        ]);

    }

    public function testInstantiation( )
    {
        $reader = $this->createMock(ConfigReaderInterface::class);
        $cache_itempool = $this->createMock(CacheItemPoolInterface::class);

        $cacheConfigReader = new CacheConfigReader($reader, $cache_itempool, 10, $this->logger );
        $this->assertInstanceOf( ConfigReaderInterface::class, $cacheConfigReader);
    }


    public function testWithEmptyCacheLifetime()
    {
        $reader = $this->createMock(ConfigReaderInterface::class);
        $reader->expects($this->once())
               ->method('__invoke')
               ->with($this->anything());

        $cache_itempool = $this->createMock(CacheItemPoolInterface::class);

        $cache_lifetime = 0;

        $cacheConfigReader = new CacheConfigReader($reader, $cache_itempool, $cache_lifetime, $this->logger );
        $cacheConfigReader("foo");
    }


    #[DataProvider('provideCacheKey')]
    public function testNormal($key, $expected_result, $is_cache_hit, $cache_lifetime)
    {
        $reader = $this->createMock(ConfigReaderInterface::class);

        // When cache lifetime is 0, cache is disabled and reader is always called
        if (!$is_cache_hit || $cache_lifetime === 0) {
            $reader->expects($this->once())
                   ->method('__invoke')
                   ->with($key)
                   ->willReturn($expected_result);
        } else {
            $reader->expects($this->never())
                   ->method('__invoke');
        }

        // Prepare CacheItem
        $cache_item = $this->createMock(CacheItemInterface::class);
        $cache_item->method('isHit')
                   ->willReturn($is_cache_hit);
        $cache_item->method('get')
                   ->willReturn($expected_result);

        if (!$is_cache_hit) {
            $cache_item->expects($this->once())
                       ->method('set')
                       ->with($this->isType('string'))
                       ->willReturnSelf();
            $cache_item->expects($this->once())
                       ->method('expiresAfter')
                       ->with($cache_lifetime)
                       ->willReturnSelf();
        }

        // Prepare CacheItemPool
        $cache_itempool = $this->createMock(CacheItemPoolInterface::class);
        $cache_itempool->method('getItem')
                       ->with($this->isType('string'))
                       ->willReturn($cache_item);

        if (!$is_cache_hit) {
            $cache_itempool->expects($this->once())
                           ->method('save')
                           ->with($cache_item);
        }

        $cacheConfigReader = new CacheConfigReader($reader, $cache_itempool, $cache_lifetime, $this->logger );
        $result = $cacheConfigReader( $key );

        $this->assertEquals($expected_result, $result);
    }

    public static function provideCacheKey()
    {

        return [
            [ "foo", "bar", true, 99],
            [ "foo", "bar", true, 0],
            [ "foo", "bar", false, 100]
        ];
    }


}
