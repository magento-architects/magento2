<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Data\Collection;

/**
 * Interface \Magento\Framework\Data\Collection\EntityFactoryInterface
 *
 * @preference Magento\Framework\Data\Collection\EntityFactory
 */
interface EntityFactoryInterface
{
    /**
     * Create new object instance
     *
     * @param string $type
     * @param array $arguments
     * @return mixed
     */
    public function create($type, array $arguments = []);
}
