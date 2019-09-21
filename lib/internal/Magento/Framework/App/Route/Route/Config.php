<?php
/**
 * Routes configuration model
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Route;

use Magento\Framework\Serialize\SerializerInterface;

class Config implements ConfigInterface
{
    /**
     * @var \Magento\Framework\App\Route\Config\Reader
     */
    protected $_reader;

    /**
     * @var \Magento\Framework\Config\Loader
     */
    protected $loader;

    /**
     * @var string
     */
    protected $_cacheId;

    /**
     * @var \Magento\Framework\Config\ScopeInterface
     */
    protected $_configScope;

    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $_areaList;

    /**
     * @var array
     */
    protected $_routes;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Config constructor.
     * @param Config\Reader $reader
     * @param \Magento\Framework\Config\Loader $loader
     * @param \Magento\Framework\Config\ScopeInterface $configScope
     * @param \Magento\Framework\App\AreaList $areaList
     * @param string $cacheId
     */
    public function __construct(
        Config\Reader $reader,
        \Magento\Framework\Config\Loader $loader,
        \Magento\Framework\Config\ScopeInterface $configScope,
        \Magento\Framework\App\AreaList $areaList,
        $cacheId = 'routes'
    ) {
        $this->_reader = $reader;
        $this->loader = $loader;
        $this->_cacheId = $cacheId;
        $this->_configScope = $configScope;
        $this->_areaList = $areaList;
    }

    /**
     * Fetch routes from configs by area code and router id
     *
     * @param string $scope
     * @return array
     */
    protected function _getRoutes($scope = null)
    {
        $scope = $scope ?: $this->_configScope->getCurrentScope();
        if (!isset($this->_routes[$scope])) {
            $cacheId = $this->_cacheId . '.' . $scope;
            $routers = $this->loader->getCachedContent($cacheId, function () use ($scope) {
                return $this->_reader->read($scope);
            });
            $this->_routes[$scope] = $routers[$this->_areaList->getDefaultRouter($scope)]['routes'];
        }
        return $this->_routes[$scope];
    }

    /**
     * Retrieve route front name
     *
     * @param string $routeId
     * @param null $scope
     * @return string
     */
    public function getRouteFrontName($routeId, $scope = null)
    {
        $routes = $this->_getRoutes($scope);
        return isset($routes[$routeId]) ? $routes[$routeId]['frontName'] : $routeId;
    }

    /**
     * @param string $frontName
     * @param string $scope
     * @return bool|int|string
     */
    public function getRouteByFrontName($frontName, $scope = null)
    {
        foreach ($this->_getRoutes($scope) as $routeId => $routeData) {
            if ($routeData['frontName'] == $frontName) {
                return $routeId;
            }
        }

        return false;
    }

    /**
     * @param string $frontName
     * @param string $scope
     * @return string[]
     */
    public function getModulesByFrontName($frontName, $scope = null)
    {
        $routes = $this->_getRoutes($scope);
        $modules = [];
        foreach ($routes as $routeData) {
            if ($routeData['frontName'] == $frontName && isset($routeData['modules'])) {
                $modules = $routeData['modules'];
                break;
            }
        }

        return array_unique($modules);
    }

    /**
     * Get serializer
     *
     * @return \Magento\Framework\Serialize\SerializerInterface
     * @deprecated 100.2.0
     */
    private function getSerializer()
    {
        if ($this->serializer === null) {
            $this->serializer = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(SerializerInterface::class);
        }
        return $this->serializer;
    }
}
