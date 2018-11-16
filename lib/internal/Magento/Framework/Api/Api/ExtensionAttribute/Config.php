<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Api\ExtensionAttribute;

use Magento\Framework\ApcuCache;
use Magento\Framework\Api\ExtensionAttribute\Config\Reader;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Extension attributes config
 */
class Config extends \Magento\Framework\Config\Data
{
    /**
     * Cache identifier
     */
    const CACHE_ID = 'extension_attributes_config';

    /**
     * Constructor
     *
     * @param Reader $reader
     * @param CacheInterface $cache
     * @param string $cacheId |null
     * @param SerializerInterface|null $serializer
     */
    public function __construct(Reader $reader, ApcuCache $cache, $cacheId = self::CACHE_ID)
    {
        $cache->getCachedContent($cacheId, function () use ($reader) {
            $result = $reader->read();
            return $result;
        });
    }

    protected function initData()
    {
    }

    /**
     * Stubbed out
     */
    public function reset()
    {
    }
}
