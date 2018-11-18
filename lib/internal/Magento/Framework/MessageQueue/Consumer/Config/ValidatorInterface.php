<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\MessageQueue\Consumer\Config;

/**
 * Queue consumer config validator interface.
 * @preference Magento\Framework\MessageQueue\Consumer\Config\CompositeValidator
 */
interface ValidatorInterface
{
    /**
     * Validate merged consumer config data.
     *
     * @param array $configData
     * @return void
     * @throws \LogicException
     */
    public function validate($configData);
}
