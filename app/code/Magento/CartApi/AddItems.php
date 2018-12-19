<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CartApi;

interface AddItems
{
    /**
     * @param string $scope
     * @param array $items
     * @param string $cartId
     */
    public function execute($scope, array $items, $cartId = null);
}