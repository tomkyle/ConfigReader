<?php
namespace Germania\ConfigReader;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LogLevel;
use Psr\Log\LoggerAwareTrait;

use Germania\Cache\CacheCallable;

class CacheConfigReader implements ConfigReaderInterface
{
    use LoggerAwareTrait;

    /**
     * @var CacheItemPoolInterface
     */
    public $cache_itempool;

    /**
     * @var int
     */
    public $cache_lifetime;

    /**
     * @var ConfigReaderInterface
     */
    public $reader;

    /**
     * @var CacheCallable
     */
    public $cache_callable;


    /**
     * PSR-3 Loglevel name
     * @var string
     */
    public $loglevel_success =LogLevel::INFO;


    public function __construct( ConfigReaderInterface $reader, CacheItemPoolInterface $cache, int $lifetime, LoggerInterface $logger = null, string $loglevel_success = null)
    {
        $this->reader = $reader;
        $this->cache_itempool = $cache;
        $this->cache_lifetime = $lifetime;
        $this->logger = $logger ?: new NullLogger;
        $this->loglevel_success  = $loglevel_success ? $loglevel_success : $this->loglevel_success;

        $this->cache_callable = new CacheCallable($this->cache_itempool, $this->cache_lifetime, function() {}, $this->logger, $this->loglevel_success);
    }


    /**
     * @param  string[] $files Config files
     * @return mixed
     */
    public function __invoke( ...$files )
    {
        // Make Cache Key
        $files_concat = join(",", $files);
        $cache_key = sha1($files_concat);

        // Utilize CacheCallable with custom creator function
        return ($this->cache_callable)($cache_key, function( $cache_key ) use ($files) { 
            return ($this->reader)(... $files); 
        });

    }
}
