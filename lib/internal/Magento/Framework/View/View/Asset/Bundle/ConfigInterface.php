<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\View\Asset\Bundle;

use Magento\Framework\View\Asset\File\FallbackContext;

/**
 * Interface ConfigInterface
 * @deprecated 100.2.0
 * @see \Magento\Deploy\Config\BundleConfig
 * @preference Magento\Framework\View\Asset\Bundle\Config
 */
interface ConfigInterface
{
    /**
     * @param FallbackContext $assetContext
     * @return bool
     */
    public function isSplit(FallbackContext $assetContext);

    /**
     * @param FallbackContext $assetContext
     * @return \Magento\Framework\Config\View
     */
    public function getConfig(FallbackContext $assetContext);

    /**
     * @param FallbackContext $assetContext
     * @return false|float|int|string
     */
    public function getPartSize(FallbackContext $assetContext);
}
