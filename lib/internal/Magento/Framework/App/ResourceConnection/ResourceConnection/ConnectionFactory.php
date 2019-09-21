<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\ResourceConnection;

use Magento\Framework\ObjectManagerInterface;

/**
 * Connection adapter factory
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create connection adapter instance
     *
     * @param array $connectionConfig
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     * @throws \InvalidArgumentException
     */
    public function create(array $connectionConfig)
    {
        /** @var \Magento\Framework\App\ResourceConnection\ConnectionAdapterInterface $adapterInstance */
        $connection = $this->objectManager->create(
            \Magento\Framework\App\ResourceConnection\ConnectionAdapterInterface::class,
            ['config' => $connectionConfig]
        )->getConnection();
        /** @var \Magento\Framework\DB\Adapter\DdlCache $ddlCache */
        // TODO: RESOLVE DDL CACHE
//        $ddlCache = $this->objectManager->get(\Magento\Framework\DB\Adapter\DdlCache::class);
//        $connection->setCacheAdapter($ddlCache);
        return $connection;
    }
}
