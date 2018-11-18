<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Design\Theme;

/**
 * Theme list interface
 *
 * @api
 * @preference Magento\Framework\View\Design\Theme\ThemeList
 */
interface ListInterface
{
    /**
     * Get theme by area and theme_path
     *
     * @param string $fullPath
     * @return \Magento\Framework\View\Design\ThemeInterface
     */
    public function getThemeByFullPath($fullPath);
}
