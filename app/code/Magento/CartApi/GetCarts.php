<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CartApi;

interface GetCarts
{
    /**
     * @return Data\Cart[]
     */
    public function execute() : array;
}