#!/usr/bin/env php
<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

if (PHP_SAPI !== 'cli') {
    echo 'bin/magento must be run as a CLI application';
    exit(1);
}

try {
    require __DIR__ . '/../app/bootstrap.php';
} catch (\Exception $e) {
    echo 'Autoload error: ' . $e->getMessage();
    exit(1);
}
try {
    $application = new Magento\Framework\Console\Cli('Magento CLI');
    $application->run();
} catch (\Exception $e) {
    while ($e) {
        echo $e->getMessage();
        echo $e->getTraceAsString();
        echo "\n\n";
        $e = $e->getPrevious();
    }
    exit(Magento\Framework\Console\Cli::RETURN_FAILURE);
}
