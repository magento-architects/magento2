<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Webapi\Model;

use Magento\Framework\ApcuCache;
use Magento\Webapi\Model\Config\Reader;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;
die('erererer');
/**
 * This class gives access to consolidated web API configuration from <Module_Name>/etc/webapi.xml files.
 *
 * @api
 * @since 100.0.2
 */
class Config implements ConfigInterface
{
    const CACHE_ID = 'webapi_config';

    /**
     * Pattern for Web API interface name.
     */
    const SERVICE_CLASS_PATTERN = '/^(.+?)\\\\(.+?)\\\\Service\\\\(V\d+)+(\\\\.+)Interface$/';

    const API_PATTERN = '/^(.+?)\\\\(.+?)\\\\Api(\\\\.+)Interface$/';

    /**
     * @var ApcuCache
     */
    protected $cache;

    /**
     * @var Reader
     */
    protected $configReader;

    /**
     * @var array
     */
    protected $services;

    /**
     * Initialize dependencies.
     *
     * @param WebapiCache $cache
     * @param Reader $configReader
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        ApcuCache $cache,
        Reader $configReader
    ) {
        die ('ddd');
        $this->services = $cache->getCachedContent(self::CACHE_ID, function() use ($configReader) {
            $configReader->read();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getServices()
    {
        return $this->services;
    }
}
