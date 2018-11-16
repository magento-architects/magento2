<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CartApi;

interface AddItems
{
    public function execute($scope, array $items);
}