<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Webapi\Controller\Rest;

interface RequestMatcherInterface
{
    /**
     * Method should return true for all the request current processor can process.
     *
     * Invoked in the loop for all registered request processors. The first one wins.
     *
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @return bool
     */
    public function canProcess(\Magento\Framework\Webapi\Rest\Request $request);
}