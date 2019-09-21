<?php
/**
 * ObjectManager configuration loader
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\ObjectManager;

use Magento\Framework\ObjectManager\ConfigLoaderInterface;

/**
 * @deprecated
 */
class ConfigLoader implements ConfigLoaderInterface
{
    /**
     * @var \Magento\Framework\Config\Loader
     */
    private $loader;

    /**
     * Config reader
     *
     * @var \Magento\Framework\ObjectManager\Config\Reader\Dom
     */
    protected $reader;

    /**
     * ConfigLoader constructor.
     * @param \Magento\Framework\Config\Loader $loader
     * @param \Magento\Framework\ObjectManager\Config\Reader\Dom $reader
     */
    public function __construct(
        \Magento\Framework\Config\Loader $loader,
        \Magento\Framework\ObjectManager\Config\Reader\Dom $reader
    ) {
        $this->loader = $loader;
        $this->reader = $reader;
    }

    /**
     * {inheritdoc}
     */
    public function load($area)
    {
        return $this->loader->getCachedContent('om.config', function() use ($area) {
            return $this->reader->read($area);
        });
    }
}
