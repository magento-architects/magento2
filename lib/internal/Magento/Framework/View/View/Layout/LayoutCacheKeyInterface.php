<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Layout;

/**
 * Interface LayoutCacheKeyInterface
 * @preference Magento\Framework\View\Model\Layout\CacheKey
 */
interface LayoutCacheKeyInterface
{
    /**
     * Add cache key(s) for generating different cache id for same handles
     *
     * @param array|string $cacheKeys
     * @return void
     */
    public function addCacheKeys($cacheKeys);

    /**
     * Return cache keys array
     *
     * @return array
     */
    public function getCacheKeys();
}
