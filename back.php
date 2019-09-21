<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Config;

use Magento\Framework\App\Cache;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize;

class Loader
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Config\Loader\CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var int
     */
    private $revalidateFrequency;

    /**
     * @var array
     */
    private $configuration = [];

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var array \Magento\Framework\Config\FileResolverInterface[]
     */
    private $fileResolvers = [];

    /**
     * @var array \Magento\Framework\Config\ConverterInterface[]
     */
    private $converters = [];

    /**
     * @var array \Magento\Framework\Config\ConverterInterface[]
     */
    private $schemaLocators = [];

    /**
     * @var ValidationStateInterface
     */
    private $validationState;

    /**
     * @var string
     */
    private $defaultScope;

    /**
     * @var string
     */
    private $domDocumentClass;

    /**
     * @var string
     */
    private $schemaFile;

    /**
     * @var string
     */
    private $perFileSchema;

    private $idAttributes =

    /**
     * @param Serialize\SerializerInterface $serializer
     * @param string $prefix
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Config\Loader\CacheInterface $cache,
        Serialize\SerializerInterface $serializer,
        ValidationStateInterface $validationState,
        $configuration = [],
        $prefix = "magento."
    ) {
        $this->objectManager = $objectManager;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->validationState = $validationState;
        $this->configuration = $configuration;
        $this->prefix = $prefix;
        $this->revalidateFrequency = ini_get('opcache.revalidate_freq');
    }

    private function getFileResolver($key) : FileResolverInterface
    {
        if (!isset($this->fileResolvers[$key])) {
            $fileResolverType = isset($this->configuration[$key]['customFileResolver'])
                ? $this->configuration[$key]['customFileResolver']
                : FileResolver::class;
            $this->fileResolvers[$key] = $this->objectManager->get($fileResolverType);
        }
        return $this->fileResolvers[$key];
    }

    private function getConverter($key) : ConverterInterface
    {
        if (!isset($this->converters[$key])) {
            $converterType = isset($this->configuration[$key]['customConverter'])
                ? $this->configuration[$key]['customConverter']
                : ConverterInterface::class;
            $this->converters[$key] = $this->objectManager->get($converterType);
        }
        return $this->converters[$key];
    }

    private function getSchemaLocator($key) : SchemaLocatorInterface
    {
        if (!isset($this->schemaLocators[$key])) {
            $schemaLocatorType = isset($this->configuration[$key]['customSchemaLocator'])
                ? $this->configuration[$key]['customSchemaLocator']
                : SchemaLocatorInterface::class;
            $this->schemaLocators[$key] = $this->objectManager->get($schemaLocatorType);
        }
        return $this->schemaLocators[$key];
    }

    public function load(string $key, string $scope = null) : array
    {
        $scope = $scope ?: $this->defaultScope;

        $cacheKey = $this->prefix . $key . "." . $scope;
        if ($this->cache->exists($cacheKey)) {
            if (MAGENTO_APCU_CHECK_TIMESTAMPS) {
                debug ("Rechecking $cacheKey");
                $includedFiles = $this->serializer->unserialize($this->cache->fetch($cacheKey . '.used_files'));
                $reload = false;
                foreach ($includedFiles as $file => $timestamp) {
                    if ($timestamp) {
                        if (!file_exists($file)) {
                            debug("$file deleted. Reloading $cacheKey");
                            $reload = true;
                            break;
                        } else {
                            $stat = @stat($file);
                            if ($stat['mtime'] !== $timestamp) {
                                debug("$file updated. Reloading $cacheKey");
                                $reload = true;
                                break;
                            }
                        }
                    } else if (file_exists($file)) {
                        debug("$file created. Reloading $cacheKey" );
                        $reload = true;
                        break;
                    }
                }
                $this->cache->store(MAGENTO_APCU_FILES_CHECKED_KEY, true, $this->revalidateFrequency);
                $value = $reload
                    ? $this->loadAndCache($key, $cacheKey, $scope)
                    : $this->serializer->unserialize($this->cache->fetch($cacheKey));
            } else {
                $value = $this->serializer->unserialize($this->cache->fetch($cacheKey));
            }
        } else {
            $value = $this->loadAndCache($key, $cacheKey, $scope);
        }
        return $value;
    }

    /**
     * @param $key
     * @param \Closure $create
     * @return mixed
     */
    private function loadAndCache(string $key, string $cacheKey, string $scope)
    {
        debug("Recreating $cacheKey");
        list($value, $loadedFiles) = $this->read($key, $scope);
        $loadedFiles = array_combine($loadedFiles, array_map(function($file) {
            if (file_exists($file)) {
                $stat = @stat($file);
                return $stat['mtime'];
            } else {
                return false;
            }
        }, $loadedFiles));
        $this->cache->store($cacheKey, $this->serializer->serialize($value));
        $this->cache->store($cacheKey . '.used_files', $this->serializer->serialize($loadedFiles));
        $this->cache->store(MAGENTO_APCU_FILES_CHECKED_KEY, true, $this->revalidateFrequency);
        return $value;
    }

    private function read(string $key, string $scope)
    {
        echo "Reading " . get_class($this) . "<br/>";
        if (!isset($this->configuration[$key]['fileName'])) {
            throw new \InvalidArgumentException(
                "The name of file to read for configuration type $key is not specified in Config\Loader declaration"
            );
        }
        list($fileList, $checkedPaths) = $this->getFileResolver($key)->get(
            $this->configuration[$key]["fileName"],
            $scope
        );
        if (!count($fileList)) {
            return [[], []];
        }
        $output = $this->readFiles($fileList);
        $filesRead = array_merge(array_keys($fileList->toArray()), $checkedPaths);
        return [$output, $filesRead];
    }

    private function readFiles($fileList)
    {
        /** @var \Magento\Framework\Config\Dom $configMerger */
        $configMerger = null;
        foreach ($fileList as $key => $content) {
            if (!strlen($content)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase('The XML in file "%1" is empty', [$key])
                );
            }
            try {
                if (!$configMerger) {
                    $configMerger = $this->_createConfigMerger($this->domDocumentClass, $content);
                } else {
                    $configMerger->merge($content);
                }
            } catch (\Magento\Framework\Config\Dom\ValidationException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase(
                        'The XML in file "%1" is invalid:' . "\n%2\nVerify the XML and try again.",
                        [$key, $e->getMessage()]
                    )
                );
            }
        }
        if ($this->validationState->isValidationRequired()) {
            $errors = [];
            if ($configMerger && !$configMerger->validate($this->schemaFile, $errors)) {
                $message = "Invalid Document \n";
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase($message . implode("\n", $errors))
                );
            }
        }

        $output = [];
        if ($configMerger) {
            $output = $this->getConverter($key)->convert($configMerger->getDom());
        }
        return $output;
    }

    /**
     * Return newly created instance of a config merger
     *
     * @param string $mergerClass
     * @param string $initialContents
     * @return \Magento\Framework\Config\Dom
     * @throws \UnexpectedValueException
     */
    protected function _createConfigMerger($mergerClass, $initialContents)
    {
        $result = new $mergerClass(
            $initialContents,
            $this->validationState,
            $this->idAttributes,
            null,
            $this->perFileSchema
        );
        if (!$result instanceof \Magento\Framework\Config\Dom) {
            throw new \UnexpectedValueException(
                "Instance of the DOM config merger is expected, got {$mergerClass} instead."
            );
        }
        return $result;
    }
}