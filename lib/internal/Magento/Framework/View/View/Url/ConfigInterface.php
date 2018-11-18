<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Url;

/**
 * Url Config Interface
 * @api
 * @preference Magento\Framework\View\Url\Config
 */
interface ConfigInterface
{
    /**
     * Get url config value by path
     *
     * @param string $path
     * @return mixed
     */
    public function getValue($path);
}
