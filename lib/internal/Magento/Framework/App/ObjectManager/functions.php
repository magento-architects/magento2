<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
function createObjectManager($areaCode, $inputArguments)
{
    $cache = new \Magento\Framework\ApcuCache();
    $arguments = array_filter($inputArguments, function($key){
        return strtolower(substr($key, 0, 8)) === 'magento.';
    }, ARRAY_FILTER_USE_KEY);
    $key = "om.$areaCode." . crc32(json_encode($arguments));
    $objectManager = $cache->getCachedContent($key, function() use ($areaCode, $arguments, $cache) {
        return create($areaCode, $arguments, $cache);
    });
    $generatingAutoloader = $objectManager->get(\Magento\Framework\Code\Generator\Autoloader::class);
    spl_autoload_register([$generatingAutoloader, 'load']);
    return $objectManager;
}

function create($areaCode, $arguments, $cache)
{
    $includedFiles = get_included_files();
    $metadataPath = BP . '/generated/metadata/' . $areaCode . '.php';
    $objectManager = null;
    if (file_exists($metadataPath)) {
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
        $loadedFiles = [];
    } else {
        $directoryList = new \Magento\Framework\App\Filesystem\DirectoryList(BP);
        $driverPool = new \Magento\Framework\Filesystem\DriverPool();
        $driver = $driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        $generatorIo = new \Magento\Framework\Code\Generator\Io($driver, BP . '/generated/code');
        $codeGenerator = new \Magento\Framework\Code\Generator($generatorIo);
        $generatingAutoloader = new \Magento\Framework\Code\Generator\Autoloader($codeGenerator);
        spl_autoload_register([$generatingAutoloader, 'load']);
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
        $fileResolver = new \Magento\Framework\App\Arguments\FileResolver\Primary($filesystem, $fileIteratorFactory);
        $schemaLocator = new \Magento\Framework\ObjectManager\Config\SchemaLocator();
        $validationState = new \Magento\Framework\App\Arguments\ValidationState('production');
        $diConfigReader = new \Magento\Framework\ObjectManager\Config\Reader\Dom($fileResolver, $argumentMapper, $schemaLocator, $validationState);
        list($configData, $primaryFiles) = $diConfigReader->read('primary');
        if ($configData) {
            $diConfig->extend($configData);
        }
        $factoryClass = $diConfig->getPreference(\Magento\Framework\ObjectManager\Factory\Dynamic\Developer::class);
        $factory = new $factoryClass($diConfig, null, $definitions, $arguments);
        $sharedInstances = [
            \Magento\Framework\App\DeploymentConfig::class => $deploymentConfig,
            \Magento\Framework\App\Filesystem\DirectoryList::class => $directoryList,
            \Magento\Framework\Filesystem\DirectoryList::class => $directoryList,
            \Magento\Framework\Filesystem\DriverPool::class => $driverPool,
            \Magento\Framework\ObjectManager\RelationsInterface::class => $relations,
            \Magento\Framework\Interception\DefinitionInterface::class => $pluginDefinition,
            \Magento\Framework\ObjectManager\ConfigInterface::class => $diConfig,
            \Magento\Framework\Interception\ObjectManager\ConfigInterface::class => $diConfig,
            \Magento\Framework\ObjectManager\DefinitionInterface::class => $definitions,
            \Magento\Framework\Stdlib\BooleanUtils::class => $booleanUtils,
            \Magento\Framework\ObjectManager\Config\Mapper\Dom::class => $argumentMapper,
            \Magento\Framework\Code\Generator\Autoloader::class => $generatingAutoloader,
            \Magento\Framework\ApcuCache::class => $cache
        ];
        $arguments['shared_instances'] = &$sharedInstances;
        /** @var \Magento\Framework\ObjectManagerInterface $objectManager */
        $objectManager = new \Magento\Framework\App\ObjectManager($factory, $diConfig, $sharedInstances);
        $factory->setObjectManager($objectManager);
        $generatorParams = $diConfig->getArguments(\Magento\Framework\Code\Generator::class);
        /** Arguments are stored in different format when DI config is compiled, thus require custom processing */
        $generatedEntities = isset($generatorParams['generatedEntities']['_v_'])
            ? $generatorParams['generatedEntities']['_v_']
            : (isset($generatorParams['generatedEntities']) ? $generatorParams['generatedEntities'] : []);
        $codeGenerator->setObjectManager($objectManager);
        $codeGenerator->setGeneratedEntities($generatedEntities);

        $configLoader = $objectManager->get(\Magento\Framework\App\ObjectManager\ConfigLoader::class);
        $sharedInstances[\Magento\Framework\ObjectManager\ConfigLoaderInterface::class] = $configLoader;
        $globalFiles = [];
        if ($areaCode !== 'global') {
            list($globalConfig, $globalFiles) = $configLoader->load('global');
            $objectManager->configure($globalConfig);
        }
        list($areaConfig, $areaFiles) = $configLoader->load($areaCode);
        $objectManager->configure($areaConfig);
        $objectManager->get(\Magento\Framework\Config\ScopeInterface::class)->setCurrentScope($areaCode);
        $diConfig->setInterceptionConfig($objectManager->get(\Magento\Framework\Interception\Config\Config::class));
    }
    $loadedFiles = array_diff_assoc(get_included_files(), $includedFiles);
    return [$objectManager, array_merge($primaryFiles, $globalFiles, $areaFiles, $loadedFiles, [__FILE__])];
}