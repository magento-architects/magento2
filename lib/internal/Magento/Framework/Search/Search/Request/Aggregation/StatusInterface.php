<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Search\Request\Aggregation;

/**
 * @preference Magento\Framework\Search\Request\Aggregation\Status
 */
interface StatusInterface
{
    /**
     * @return bool
     */
    public function isEnabled();
}
