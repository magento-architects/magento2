<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::LIBRARY, 'magento/framework-amqp', __DIR__);
define('MAGENTO_APCU_FILES_CHECKED_KEY', 'magento.files_checked');
define(
    'MAGENTO_APCU_CHECK_TIMESTAMPS',
    ini_get('opcache.validate_timestamps') && !apcu_exists(MAGENTO_APCU_FILES_CHECKED_KEY)
);
