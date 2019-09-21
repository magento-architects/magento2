<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Config\App\Config\Source;

use Magento\Framework\App\Config\ConfigSourceInterface;
use Magento\Framework\DataObject;
use Magento\Framework\App\Config\Initial\Reader;

/**
 * Class for retrieving initial configuration from modules
 *
 * @api
 * @since 100.1.2
 */
class ModularConfigSource implements ConfigSourceInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var \Magento\Framework\Config\Loader
     */
    private $loader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader, \Magento\Framework\Config\Loader $loader)
    {
        $this->reader = $reader;
        $this->loader = $loader;
    }

    /**
     * Get initial data
     *
     * @param string $path Format is scope type and scope code separated by slash: e.g. "type/code"
     * @return array
     * @since 100.1.2
     */
    public function get($path = '')
    {
        $data = new DataObject($this->loader->getCachedContent(
            \Magento\Framework\App\Config\Initial::CACHE_ID,
            function () {
                return $this->reader->read();
            }
        ));
        if ($path !== '') {
            $path = '/' . $path;
        }
        return $data->getData('data' . $path) ?: [];
    }
}
