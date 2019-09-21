<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Mview;

/**
 * Interface \Magento\Framework\Mview\ConfigInterface
 * @preference Magento\Framework\Mview\Config
 */
interface ConfigInterface
{
    /**
     * Get views list
     *
     * @return array[]
     */
    public function getViews();

    /**
     * Get view by ID
     *
     * @param string $viewId
     * @return array
     */
    public function getView($viewId);
}
