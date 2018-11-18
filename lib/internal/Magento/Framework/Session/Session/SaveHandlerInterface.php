<?php
/**
 * Session config interface
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Session;

/**
 * @preference Magento\Framework\Session\SaveHandler
 */
interface SaveHandlerInterface extends \SessionHandlerInterface
{
    /**
     * Default session save handler
     */
    const DEFAULT_HANDLER = 'files';
}
