<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App;

use Magento\Framework\Controller\ResultInterface;

/**
 * Application front controller responsible for dispatching application requests.
 * Front controller contains logic common for all actions.
 * Every application area has own front controller.
 *
 * @api
 * @preference Magento\Framework\App\FrontController
 */
interface FrontControllerInterface
{
    /**
     * Dispatch application action
     *
     * @param RequestInterface $request
     * @return ResultInterface
     */
    public function dispatch(RequestInterface $request);
}
