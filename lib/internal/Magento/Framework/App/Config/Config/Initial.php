<?php
/**
 * Initial configuration data container. Provides interface for reading initial config values
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Initial
{
    /**
     * Cache identifier used to store initial config
     */
    const CACHE_ID = 'initial_config';

    /**
     * Config data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Config metadata
     *
     * @var array
     */
    protected $_metadata = [];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Initial constructor
     *
     * @param Initial\Reader $reader
     * @param \Magento\Framework\App\Cache\Type\Config $cache
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        \Magento\Framework\App\Config\Initial\Reader $reader,
        \Magento\Framework\Config\Loader $loader
    ) {
        $data = $loader->getCachedContent(self::CACHE_ID, function () use ($reader) {return $reader->read();});
        $this->_data = $data['data'];
        $this->_metadata = $data['metadata'];
    }

    /**
     * Get initial data by given scope
     *
     * @param string $scope Format is scope type and scope code separated by pipe: e.g. "type|code"
     * @return array
     */
    public function getData($scope)
    {
        list($scopeType, $scopeCode) = array_pad(explode('|', $scope), 2, null);

        if (ScopeConfigInterface::SCOPE_TYPE_DEFAULT == $scopeType) {
            return $this->_data[$scopeType] ?? [];
        } elseif ($scopeCode) {
            return $this->_data[$scopeType][$scopeCode] ?? [];
        }
        return [];
    }

    /**
     * Get configuration metadata
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->_metadata;
    }
}
