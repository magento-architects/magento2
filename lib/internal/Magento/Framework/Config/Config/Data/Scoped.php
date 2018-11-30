<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Config\Data;

use Magento\Framework\ApcuCache;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Provides scoped configuration
 * @api
 */
class Scoped extends \Magento\Framework\Config\Data
{
    /**
     * Configuration scope resolver
     *
     * @var \Magento\Framework\Config\ScopeInterface
     */
    protected $_configScope;

    /**
     * Configuration reader
     *
     * @var \Magento\Framework\Config\ReaderInterface
     */
    protected $_reader;

    /**
     * Configuration cache
     *
     * @var ApcuCache
     */
    protected $_cache;

    /**
     * Cache tag
     *
     * @var string
     */
    protected $_cacheId;

    /**
     * Scope priority loading scheme
     *
     * @var string[]
     */
    protected $_scopePriorityScheme = [];

    /**
     * Loaded scopes
     *
     * @var array
     */
    protected $_loadedScopes = [];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Config\ReaderInterface $reader
     * @param \Magento\Framework\Config\ScopeInterface $configScope
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string $cacheId
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        \Magento\Framework\Config\ReaderInterface $reader,
        \Magento\Framework\Config\ScopeInterface $configScope,
        ApcuCache $cache,
        $cacheId,
        SerializerInterface $serializer = null
    ) {
        $this->_reader = $reader;
        $this->_configScope = $configScope;
        $this->_cache = $cache;
        $this->_cacheId = $cacheId;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    /**
     * Get config value by key
     *
     * @param string $path
     * @param mixed $default
     * @return array|mixed|null
     */
    public function get($path = null, $default = null)
    {
        $this->_loadScopedData();
        return parent::get($path, $default);
    }

    /**
     * Load data for current scope
     *
     * @return void
     */
    protected function _loadScopedData()
    {
        $scope = $this->_configScope->getCurrentScope();
        if (false == isset($this->_loadedScopes[$scope])) {
            if (false == in_array($scope, $this->_scopePriorityScheme)) {
                $this->_scopePriorityScheme[] = $scope;
            }
            foreach ($this->_scopePriorityScheme as $scopeCode) {
                if (false == isset($this->_loadedScopes[$scopeCode])) {
                    $data = $this->_cache->getCachedContent($scopeCode . '::' . $this->_cacheId, function() use ($scopeCode) {
                        return $this->_reader->read($scopeCode);
                    });
                    $this->merge($data);
                    $this->_loadedScopes[$scopeCode] = true;
                }
                if ($scopeCode == $scope) {
                    break;
                }
            }
        }
    }
}
