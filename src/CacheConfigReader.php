<?php
namespace Germania\ConfigReader;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LogLevel;
use Psr\Log\LoggerAwareTrait;

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
     * PSR-3 Loglevel name
     * @var string
     */
    public $loglevel_success = LogLevel::INFO;

    public function __construct(ConfigReaderInterface $reader, CacheItemPoolInterface $cache, int $lifetime, ?LoggerInterface $logger = null, ?string $loglevel_success = null)
    {
        $this->reader = $reader;
        $this->cache_itempool = $cache;
        $this->cache_lifetime = $lifetime;
        $this->logger = $logger ?: new NullLogger;
        $this->loglevel_success = $loglevel_success ? $loglevel_success : $this->loglevel_success;
    }

    /**
     * @param  string[] $files Config files
     * @return mixed
     */
    public function __invoke(...$files)
    {
        // If cache lifetime is 0, bypass cache entirely
        if ($this->cache_lifetime === 0) {
            return ($this->reader)(...$files);
        }

        // Make Cache Key
        $files_concat = join(",", $files);
        $cache_key = md5($files_concat);

        // Get cache item
        $cache_item = $this->cache_itempool->getItem($cache_key);

        // Check if cache hit
        if ($cache_item->isHit()) {
            $this->logger->log($this->loglevel_success, "Cache hit for key: {$cache_key}");
            return $cache_item->get();
        }

        // Cache miss - execute the reader
        $this->logger->log($this->loglevel_success, "Cache miss for key: {$cache_key}");
        $result = ($this->reader)(...$files);

        // Store in cache
        $cache_item->set($result);
        $cache_item->expiresAfter($this->cache_lifetime);
        $this->cache_itempool->save($cache_item);

        return $result;
    }
}
