<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Model\Entity;

/**
 * Interface ScopeInterface
 * @preference Magento\Framework\Model\Entity\Scope
 */
interface ScopeInterface
{
    /**
     * @return string
     */
    public function getValue();

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return ScopeInterface|null
     */
    public function getFallback();
}
