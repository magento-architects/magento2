<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cart;

use Magento\Framework\App\ResourceConnection;

class GetCarts implements \Magento\CartApi\GetCarts
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * GetCarts constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(): array
    {
        $db = $this->resourceConnection->getConnection();
        $statement = $db->select()->from("quote")->query();
        return $statement->fetchAll();
    }
}