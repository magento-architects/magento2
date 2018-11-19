<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Webapi\Controller\Rest;

use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Webapi\Authorization;
use Magento\Framework\Webapi\Rest\Request as RestRequest;

/**
 * This class is responsible for validating the request
 */
class RequestValidator
{
    /**
     * @var RestRequest
     */
    private $request;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * Initialize dependencies
     *
     * @param RestRequest $request
     * @param Router $router
     * @param Authorization $authorization
     */
    public function __construct(
        RestRequest $request,
        Router $router,
        Authorization $authorization
    ) {
        $this->request = $request;
        $this->router = $router;
        $this->authorization = $authorization;
    }

    /**
     * Validate request
     *
     * @throws AuthorizationException
     * @throws \Magento\Framework\Webapi\Exception
     * @return void
     */
    public function validate()
    {
        $this->checkPermissions();
        $route = $this->router->match($this->request);
        if ($route->isSecure() && !$this->request->isSecure()) {
            throw new \Magento\Framework\Webapi\Exception(__('Operation allowed only in HTTPS'));
        }
    }

    /**
     * Perform authentication and authorization.
     *
     * @throws AuthorizationException
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function checkPermissions()
    {
        $route = $this->router->match($this->request);
        if (!$this->authorization->isAllowed($route->getAclResources())) {
            $params = ['resources' => implode(', ', $route->getAclResources())];
            throw new AuthorizationException(
                __("The consumer isn't authorized to access %resources.", $params)
            );
        }
    }
}
