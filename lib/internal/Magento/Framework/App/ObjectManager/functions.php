<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
function createObjectManager($areaCode, $inputArguments) : \Magento\Framework\ObjectManagerInterface
{
    $cache = new \Magento\Framework\ApcuCache();
    $arguments = array_filter($inputArguments, function($key){
        return strtolower(substr($key, 0, 8)) === 'magento.';
    }, ARRAY_FILTER_USE_KEY);
    $key = "om.$areaCode." . crc32(json_encode($arguments));
    $metadataPath = BP . '/generated/metadata/' . $areaCode . '.php';
    $objectManager = null;
    if (file_exists($metadataPath)) {
        $objectManager = $cache->getCachedContent($key, function() use ($metadataPath, $areaCode, $arguments, $cache) {
            return createCompiled($metadataPath, $areaCode, $arguments, $cache);
        });
    } else {
        $objectManager = $cache->getCachedContent($key, function() use ($areaCode, $arguments, $cache) {
            return createRuntime($areaCode, $arguments, $cache);
        });
        $generatingAutoloader = $objectManager->get(\Magento\Framework\Code\Generator\Autoloader::class);
        spl_autoload_register([$generatingAutoloader, 'load']);
    }
    return $objectManager;
}

function createCompiled($metadataPath, $areaCode, $arguments, $cache)
{
    $includedFiles = get_included_files();
    $diConfig = new \Magento\Framework\Interception\ObjectManager\Config\Compiled (include $metadataPath);
    $factoryClass = $diConfig->getPreference(\Magento\Framework\ObjectManager\Factory\Compiled::class);
    $sharedInstances = [];
    $arguments['shared_instances'] = &$sharedInstances;
    $factory = new $factoryClass($diConfig, $arguments['shared_instances'], $arguments);
    /** @var \Magento\Framework\ObjectManagerInterface $objectManager */
    $objectManager = new \Magento\Framework\App\ObjectManager($factory, $diConfig, $sharedInstances);
    $factory->setObjectManager($objectManager);
    $scopeConfig = $objectManager->get(\Magento\Framework\Config\ScopeInterface::class);
    $scopeConfig->setCurrentScope('global');
    $interceptionConfig = $objectManager->get(\Magento\Framework\Interception\Config\Config::class);
    $diConfig->setInterceptionConfig($interceptionConfig);
    $compiledConfigCache = $objectManager->get(\Magento\Framework\App\Interception\Cache\CompiledConfig::class);
    $pluginList = $objectManager->create(\Magento\Framework\Interception\PluginListInterface::class, ['cache' => $compiledConfigCache]);
    $sharedInstances[\Magento\Framework\Interception\PluginList\PluginList::class] = $pluginList;
    $sharedInstances[\Magento\Framework\ApcuCache::class] = $cache;
    $cacheManager = $objectManager->get(\Magento\Framework\App\Cache\Manager::class);
    $cacheManager->setEnabled([CompiledConfig::TYPE_IDENTIFIER], true);
    $loadedFiles = array_diff_assoc(get_included_files(), $includedFiles);
    $loadedFiles[] = __FILE__;
    return [$objectManager, $loadedFiles];
}

function createRuntime($areaCode, $arguments, $cache)
{
    $includedFiles = get_included_files();
    $directoryList = new \Magento\Framework\App\Filesystem\DirectoryList(BP);
    $driverPool = new \Magento\Framework\Filesystem\DriverPool();
    $driver = $driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
    $configFilePool = new \Magento\Framework\Config\File\ConfigFilePool();
    $reader = new \Magento\Framework\App\DeploymentConfig\Reader($directoryList, $driverPool, $configFilePool);
    $deploymentConfig = new \Magento\Framework\App\DeploymentConfig($reader);
    $pluginDefinition = new \Magento\Framework\Interception\Definition\Runtime();
    $definitions = new \Magento\Framework\ObjectManager\Definition\Runtime();
    $relations = new \Magento\Framework\ObjectManager\Relations\Runtime();
    $diConfig = new \Magento\Framework\Interception\ObjectManager\Config\Developer($relations, $definitions);
    $booleanUtils = new \Magento\Framework\Stdlib\BooleanUtils();
    $constInterpreter = new \Magento\Framework\Data\Argument\Interpreter\Constant();
    $argInterpreter = new \Magento\Framework\Data\Argument\Interpreter\Composite([], \Magento\Framework\ObjectManager\Config\Reader\Dom::TYPE_ATTRIBUTE);
    $argInterpreter->addInterpreters([
        'boolean' => new \Magento\Framework\Data\Argument\Interpreter\Boolean($booleanUtils),
        'string' => new \Magento\Framework\Data\Argument\Interpreter\BaseStringUtils($booleanUtils),
        'number' => new \Magento\Framework\Data\Argument\Interpreter\Number(),
        'null' => new \Magento\Framework\Data\Argument\Interpreter\NullType(),
        'object' => new \Magento\Framework\Data\Argument\Interpreter\DataObject($booleanUtils),
        'const' => new \Magento\Framework\Data\Argument\Interpreter\Constant(),
        'init_parameter' => new \Magento\Framework\App\Arguments\ArgumentInterpreter($constInterpreter),
        'array' => new \Magento\Framework\Data\Argument\Interpreter\ArrayType($argInterpreter)
    ]);
    $argumentMapper = new \Magento\Framework\ObjectManager\Config\Mapper\Dom($argInterpreter);
    $readFactory = new \Magento\Framework\Filesystem\Directory\ReadFactory($driverPool);
    $writeFactory = new \Magento\Framework\Filesystem\Directory\WriteFactory($driverPool);
    $filesystem = new \Magento\Framework\Filesystem($directoryList, $readFactory, $writeFactory);
    $fileReadFactory = new \Magento\Framework\Filesystem\File\ReadFactory($driverPool);
    $fileIteratorFactory = new \Magento\Framework\Config\FileIteratorFactory($fileReadFactory);
    $converter = new \Magento\Framework\Module\Declaration\Converter\Dom();
    $parser = new \Magento\Framework\Xml\Parser();
    $componentRegistrar = new \Magento\Framework\Component\ComponentRegistrar();
    $moduleListLoader = new \Magento\Framework\Module\ModuleList\Loader($converter, $parser, $componentRegistrar, $driver);
    $moduleDirs = new \Magento\Framework\Module\Dir($componentRegistrar);
    $moduleList = new \Magento\Framework\Module\ModuleList($deploymentConfig, $moduleListLoader);
    $moduleReader = new \Magento\Framework\Module\Dir\Reader($moduleDirs, $moduleList, $fileIteratorFactory, $readFactory, $componentRegistrar);
    $fileResolver = new \Magento\Framework\App\Config\FileResolver($moduleReader, $filesystem, $fileIteratorFactory);
    $schemaLocator = new \Magento\Framework\ObjectManager\Config\SchemaLocator();
    $validationState = new \Magento\Framework\App\Arguments\ValidationState('production');
    $diConfigReader = new \Magento\Framework\ObjectManager\Config\Reader\Dom($fileResolver, $argumentMapper, $schemaLocator, $validationState);
    $configAreas = ($areaCode === 'global') ? ['global', 'primary'] : ['global', $areaCode, 'primary'];
    $loadedConfigFiles = [];
    foreach ($configAreas as $area) {
        list($areaConfig, $areaFiles) = $diConfigReader->read($area);
        $diConfig->extend($areaConfig);
        $loadedConfigFiles = array_merge($loadedConfigFiles, $areaFiles);
    }
    $factoryClass = $diConfig->getPreference(\Magento\Framework\ObjectManager\Factory\Dynamic\Developer::class);
    $factory = new $factoryClass($diConfig, null, $definitions, $arguments);
    $sharedInstances = [
        \Magento\Framework\ApcuCache::class => $cache,
        \Magento\Framework\App\DeploymentConfig::class => $deploymentConfig,
        \Magento\Framework\App\Filesystem\DirectoryList::class => $directoryList,
        \Magento\Framework\Component\ComponentRegistrarInterface::class => $componentRegistrar,
        \Magento\Framework\Filesystem\DirectoryList::class => $directoryList,
        \Magento\Framework\Filesystem\DriverPool::class => $driverPool,
        \Magento\Framework\Interception\DefinitionInterface::class => $pluginDefinition,
        \Magento\Framework\Interception\ObjectManager\ConfigInterface::class => $diConfig,
        \Magento\Framework\ObjectManager\Config\Mapper\Dom::class => $argumentMapper,
        \Magento\Framework\ObjectManager\Config\Reader\Dom::class => $diConfigReader,
        \Magento\Framework\ObjectManager\ConfigInterface::class => $diConfig,
        \Magento\Framework\ObjectManager\DefinitionInterface::class => $definitions,
        \Magento\Framework\ObjectManager\RelationsInterface::class => $relations,
        \Magento\Framework\Stdlib\BooleanUtils::class => $booleanUtils,
    ];
    $arguments['shared_instances'] = &$sharedInstances;
    /** @var \Magento\Framework\ObjectManagerInterface $objectManager */
    $objectManager = new \Magento\Framework\App\ObjectManager($factory, $diConfig, $sharedInstances);
    $factory->setObjectManager($objectManager);
    $diConfig->setInterceptionConfig($objectManager->get(\Magento\Framework\Interception\Config\Config::class));
    $loadedFiles = array_diff_assoc(get_included_files(), $includedFiles);
    return [$objectManager, array_merge($loadedConfigFiles, $loadedFiles, [__FILE__])];
}