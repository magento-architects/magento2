<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Webapi\Controller;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Webapi\Authorization;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Request;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Webapi\Controller\Rest\ParamsOverrider;
use Magento\Webapi\Controller\Rest\Router;
use Magento\Webapi\Controller\Rest\Router\Route;
use Magento\Webapi\Controller\Rest\RequestProcessorPool;

/**
 * Front controller for WebAPI REST area.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Rest implements \Magento\Framework\App\FrontControllerInterface
{
    /**
     * Path for accessing REST API schema
     *
     * @deprecated 100.3.0
     */
    const SCHEMA_PATH = '/schema';

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Webapi\Rest\Response
     */
    protected $_response;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Webapi\ErrorProcessor
     */
    protected $_errorProcessor;

    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $areaList;

    /**
     * @var RequestProcessorPool
     */
    protected $requestProcessorPool;

    /**
     * Rest constructor.
     * @param RestResponse $_response
     * @param \Magento\Framework\ObjectManagerInterface $_objectManager
     * @param ErrorProcessor $_errorProcessor
     * @param \Magento\Framework\App\AreaList $areaList
     * @param RequestProcessorPool $requestProcessorPool
     */
    public function __construct(RestResponse $_response, \Magento\Framework\ObjectManagerInterface $_objectManager, ErrorProcessor $_errorProcessor, \Magento\Framework\App\AreaList $areaList, RequestProcessorPool $requestProcessorPool)
    {
        $this->_response = $_response;
        $this->_objectManager = $_objectManager;
        $this->_errorProcessor = $_errorProcessor;
        $this->areaList = $areaList;
        $this->requestProcessorPool = $requestProcessorPool;
    }

    /**
     * Handle REST request
     *
     * Based on request decide is it schema request or API request and process accordingly.
     * Throws Exception in case if cannot be processed properly.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
/*        \Magento\Framework\Phrase::setRenderer(
            $this->_objectManager->get(\Magento\Framework\Phrase\RendererInterface::class)
        );*/
        try {
            $processor = $this->requestProcessorPool->getProcessor($request);
            $processor->process($request);
        } catch (\Exception $e) {
            $maskedException = $this->_errorProcessor->maskException($e);
            $this->_response->setException($maskedException);
        }

        return $this->_response;
    }

    /**
     * Retrieve current route.
     *
     * @return Route
     * @deprecated 100.1.0
     * @see \Magento\Webapi\Controller\Rest\InputParamsResolver::getRoute
     */
    protected function getCurrentRoute()
    {
        if (!$this->_route) {
            $this->_route = $this->_router->match($this->_request);
        }

        return $this->_route;
    }

    /**
     * Perform authentication and authorization.
     *
     * @throws \Magento\Framework\Exception\AuthorizationException
     * @return void
     * @deprecated 100.1.0
     * @see \Magento\Webapi\Controller\Rest\RequestValidator::checkPermissions
     */
    protected function checkPermissions()
    {
        $route = $this->getCurrentRoute();
        if (!$this->authorization->isAllowed($route->getAclResources())) {
            $params = ['resources' => implode(', ', $route->getAclResources())];
            throw new AuthorizationException(
                __("The consumer isn't authorized to access %resources.", $params)
            );
        }
    }

    /**
     * Validate request
     *
     * @throws AuthorizationException
     * @throws \Magento\Framework\Webapi\Exception
     * @return void
     * @deprecated 100.1.0
     * @see \Magento\Webapi\Controller\Rest\RequestValidator::validate
     */
    protected function validateRequest()
    {
        $this->checkPermissions();
        if ($this->getCurrentRoute()->isSecure() && !$this->_request->isSecure()) {
            throw new \Magento\Framework\Webapi\Exception(__('Operation allowed only in HTTPS'));
        }
    }
}
