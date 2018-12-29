<?php
/**
 * Application config file resolver
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Config;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use \Magento\Framework\Filesystem;

class FileResolver implements \Magento\Framework\Config\FileResolverInterface
{
    /**
     * File iterator factory
     *
     * @var \Magento\Framework\Config\FileIteratorFactory
     */
    protected $iteratorFactory;

    /**
     * Filesystem
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var Filesystem\Directory\ReadFactory
     */
    protected $readFactory;

    /**
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Config\FileIteratorFactory $iteratorFactory
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Config\FileIteratorFactory $iteratorFactory,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
    ) {
        $this->iteratorFactory = $iteratorFactory;
        $this->filesystem = $filesystem;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function get($filename, $scope)
    {
        $configFiles = [];
        $configDir = \Magento\Framework\Module\Dir::MODULE_ETC_DIR;
        $checkedPaths = [];
        $componentPaths = array_merge(
            $this->componentRegistrar->getPaths(ComponentRegistrar::LIBRARY),
            $this->componentRegistrar->getPaths(ComponentRegistrar::MODULE),
            $this->componentRegistrar->getPaths(ComponentRegistrar::THEME)
        );

        foreach ($componentPaths as $componentPath) {
            $componentDir = $this->readFactory->create($componentPath);
            if ($componentDir->isExist($configDir . DIRECTORY_SEPARATOR . $filename)) {
                $configFiles[] = $componentDir->getAbsolutePath($configDir . DIRECTORY_SEPARATOR . $filename);
            } else {
                $checkedPaths[] = $componentDir->getAbsolutePath($configDir);
            }
            if ($scope !== 'global') {
                $areaConfigDir = $configDir . DIRECTORY_SEPARATOR . $scope;
                if ($componentDir->isExist($areaConfigDir . DIRECTORY_SEPARATOR . $filename)) {
                    $configFiles[] = $componentDir->getAbsolutePath($areaConfigDir . DIRECTORY_SEPARATOR . $filename);
                } else {
                    $checkedPaths[] = $componentDir->getAbsolutePath($areaConfigDir);
                }
            }
        }

        $directory = $this->filesystem->getDirectoryRead(DirectoryList::CONFIG);
        $found = false;
        foreach ($directory->search('{' . $filename . ',*/' . $filename . '}') as $path) {
            $configFiles[] = $directory->getAbsolutePath($path);
            $found = true;
        }
        if (!$found) {
            $checkedPaths[] = $directory->getAbsolutePath();
        }
        $iterator = $this->iteratorFactory->create($configFiles);
        return [$iterator, $checkedPaths];
    }
}
