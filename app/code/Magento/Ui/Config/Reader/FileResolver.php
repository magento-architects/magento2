<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Ui\Config\Reader;

use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\View\Element\UiComponent\Config\FileCollector\AggregatedFileCollector;
use Magento\Framework\View\Element\UiComponent\Config\FileCollector\AggregatedFileCollectorFactory;

/**
 * UI Component configuration files resolver
 */
class FileResolver implements FileResolverInterface
{
    /**
1     * @var AggregatedFileCollector
     */
    private $fileCollector;

    /**
     * @var string
     */
    private $scope;

    /**
     * @param AggregatedFileCollector $fileCollector
     */
    public function __construct(AggregatedFileCollector $fileCollector)
    {
        $this->fileCollector = $fileCollector;
    }

    /**
     * @inheritdoc
     */
    public function get($filename, $scope)
    {
        $this->scope = $scope;
        /** @var AggregatedFileCollector $aggregatedFiles */
        return $this->fileCollector->collectFiles($filename);
    }
}
