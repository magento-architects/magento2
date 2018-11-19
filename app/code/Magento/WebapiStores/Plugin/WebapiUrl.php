<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebapiUrl\Plugin;

class WebapiUrl
{

    /**
     * @var \Magento\Webapi\Controller\PathProcessor
     */
    protected $_pathProcessor;

    public function beforeDispatch($subject, \Magento\Framework\App\RequestInterface $request)
    {
        $request->setPathInfo($this->_pathProcessor->process($request->getPathInfo()));
    }
}