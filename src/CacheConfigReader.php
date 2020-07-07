<?php
namespace Germania\ConfigReader;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Stash\Interfaces\ItemInterface as StashItemInterface;
use Stash\Invalidation as StashInvalidation;

class CacheConfigReader implements ConfigReaderInterface
{
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
    public $loglevel_success = "info";


    public function __construct( ConfigReaderInterface $reader, CacheItemPoolInterface $cache, int $lifetime, LoggerInterface $logger = null)
    {
        $this->reader = $reader;
        $this->cache_itempool = $cache;
        $this->cache_lifetime = $lifetime;
        $this->logger = $logger ?: new NullLogger;
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


        // Always rebuild when no cache lifetime given
        if (!$this->cache_lifetime) {
            $this->logger->debug("Cache lifetime is empty, execute config parsing '{files}'", [
                'files' => $files_concat
            ]);
            return ($this->reader)(... $files);
        }

        // Grab CacheItem
        $item = $this->cache_itempool->getItem( $cache_key );

        // Stampede/Dog pile protection (proprietary)
        if ($item instanceOf StashItemInterface):
            $precompute_time = round($this->cache_lifetime / 4);
            $item->setInvalidationMethod(StashInvalidation::PRECOMPUTE, $precompute_time);
        endif;

        // Just return cached value if valid
        if ($item->isHit()) :
            $value = $item->get();
            return $value;
        endif;

        // Must rebuild
        $this->logger->log($this->loglevel_success, "Rebuild cache item for '{files}'", [
            'cache_lifetime' => $this->cache_lifetime,
            'files' => $files_concat
        ]);

        // Use proprietary lock feature
        if ($item instanceOf StashItemInterface):
            $item->lock();
        endif;

        // Rebuild + save
        $value = ($this->reader)(... $files);

        $item->set( $value );
        $item->expiresAfter( $this->cache_lifetime );
        $this->cache_itempool->save( $item );

        return $value;
    }
}
