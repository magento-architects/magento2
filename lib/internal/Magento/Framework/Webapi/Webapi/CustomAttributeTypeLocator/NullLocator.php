<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Webapi\CustomAttributeTypeLocator;

class NullLocator implements \Magento\Framework\Webapi\CustomAttributeTypeLocatorInterface
{
    public function getType($attributeCode, $entityType)
    {
    }

    public function getAllServiceDataInterfaces()
    {
    }
}