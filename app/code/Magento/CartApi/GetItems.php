<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CartApi;

interface GetItems
{
    /**
     * @return Data\CartItem[]
     */
    public function execute() : array;
}