<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Config\Config\Loader;

interface CacheInterface
{
    public function exists($key);

    public function fetch($key);

    public function store($key, $data, $ttl = 0);


}