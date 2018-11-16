<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CartApi\Data;

class CartItem
{
    /**
     * @var int
     */
    public $itemId;

    /**
     * @var string
     */
    public $sku;

    /**
     * @var float
     */
    public $qty;

    /**
     * @var string
     */
    public $name;

    /**
     * @var float
     */
    public $price;

    /**
     * @var string
     */
    public $productType;

    /**
     * @var int
     */
    public $quoteId;

    /**
     * @var ProductOptionInterface
     */
    public $productOption;

    /**
     * @var \Magento\Quote\Api\Data\CartItemExtensionInterface
     */
    public $extensionAttributes;
}