<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Request;

class PathInfoProcessor implements PathInfoProcessorInterface
{
    public function process(\Magento\Framework\App\RequestInterface $request, $pathInfo)
    {
        return $pathInfo;
    }
}