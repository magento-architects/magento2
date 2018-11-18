<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Api\Search;

use Magento\Framework\Api\CustomAttributesDataInterface;

/**
 * Interface \Magento\Framework\Api\Search\DocumentInterface
 * @preference Magento\Framework\Api\Search\Document
 */
interface DocumentInterface extends CustomAttributesDataInterface
{
    const ID = 'id';

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);
}
