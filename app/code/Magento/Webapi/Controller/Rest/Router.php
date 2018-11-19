<?php
/**
 * Router for Magento web API.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Webapi\Controller\Rest;

use \Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Model\Config\Converter;

class Router
{
    /**
     * @var array
     */
    protected $_routes = [];


    /**
     * @var \Magento\Webapi\Model\ConfigInterface
     */
    private $config;

    /**
     * @var \Magento\Framework\Controller\Router\Route\Factory
     */
    private $routeFactory;

    /**
     * Router constructor.
     * @param \Magento\Webapi\Model\ConfigInterface $config
     * @param \Magento\Framework\Controller\Router\Route\Factory $routeFactory
     */
    public function __construct(\Magento\Webapi\Model\ConfigInterface $config, \Magento\Framework\Controller\Router\Route\Factory $routeFactory)
    {
        $this->config = $config;
        $this->routeFactory = $routeFactory;
    }


    /**
     * Route the Request, the only responsibility of the class.
     * Find route that matches current URL, set parameters of the route to Request object.
     *
     * @param Request $request
     * @return \Magento\Webapi\Controller\Rest\Router\Route
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function match(Request $request)
    {
        /** @var \Magento\Webapi\Controller\Rest\Router\Route[] $routes */
        $requestHttpMethod = $request->getHttpMethod();
        $servicesRoutes = $this->config->getServices()[Converter::KEY_ROUTES];
        $routes = [];
        // Return the route on exact match
        if (isset($servicesRoutes[$request->getPathInfo()][$requestHttpMethod])) {
            $methodInfo = $servicesRoutes[$request->getPathInfo()][$requestHttpMethod];
            $route = $this->routeFactory->createRoute(
                \Magento\Webapi\Controller\Rest\Router\Route::class,
                $request->getPathInfo(),
                $methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_CLASS],
                $methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_METHOD],
                $methodInfo[Converter::KEY_SECURE],
                array_keys($methodInfo[Converter::KEY_ACL_RESOURCES]),
                $methodInfo[Converter::KEY_DATA_PARAMETERS]
            );
        } else {
            $serviceBaseUrl = preg_match('#^/?\w+/\w+#', $request->getPathInfo(), $matches) ? $matches[0] : null;
            ksort($servicesRoutes, SORT_STRING);
            foreach ($servicesRoutes as $url => $httpMethods) {
                // skip if baseurl is not null and does not match
                if (!$serviceBaseUrl || strpos(trim($url, '/'), trim($serviceBaseUrl, '/')) !== 0) {
                    // base url does not match, just skip this service
                    continue;
                }
                foreach ($httpMethods as $httpMethod => $methodInfo) {
                    if (strtoupper($httpMethod) == strtoupper($requestHttpMethod)) {
                        $aclResources = array_keys($methodInfo[Converter::KEY_ACL_RESOURCES]);
                        $routes[] = $route = $this->routeFactory->createRoute(
                            \Magento\Webapi\Controller\Rest\Router\Route::class,
                            $url,
                            $methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_CLASS],
                            $methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_METHOD],
                            $methodInfo[Converter::KEY_SECURE],
                            $aclResources,
                            $methodInfo[Converter::KEY_DATA_PARAMETERS]
                        );
                    }
                }
            }

            $matched = [];
            foreach ($routes as $route) {
                $params = $route->match($request);
                if ($params !== false) {
                    $request->setParams($params);
                    $matched[] = $route;
                }
            }
            if (empty($matched)) {
                throw new \Magento\Framework\Webapi\Exception(
                    __('Request does not match any route.'),
                    0,
                    \Magento\Framework\Webapi\Exception::HTTP_NOT_FOUND
                );
            }
            $route = array_pop($matched);
        }
        return $route;
    }
}
