<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Ui\Config\Reader\Definition;

use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\Loader;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Ui\Config\Converter;
use Magento\Framework\Data\Argument\InterpreterInterface;
use Magento\Ui\Config\Reader\Definition;
use Magento\Ui\Config\Reader\DefinitionFactory;

/**
 * Read UI Component definition configuration data ang evaluate arguments
 */
class Data implements \Magento\Framework\Config\DataInterface
{
    /**
     * ID in the storage cache
     */
    const CACHE_ID = 'ui_component.configuration.definition_data';

    /**
     * Search pattern
     */
    const SEARCH_PATTERN = '%s.xml';

    /**
     * Config data
     *
     * @var array
     */
    private $data = [];

    /**
     * Argument interpreter.
     *
     * @var InterpreterInterface
     */
    private $argumentInterpreter;

    /**
     * @param DefinitionFactory $readerFactory
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param InterpreterInterface $argumentInterpreter
     */
    public function __construct(
        Definition $reader,
        Loader $configLoader,
        InterpreterInterface $argumentInterpreter
    ) {
        $this->argumentInterpreter = $argumentInterpreter;
        $data = $configLoader->getCachedContent(static::CACHE_ID, function() use ($reader) {
            return $reader->read();
        });
        if (!empty($data)) {
            $this->data = $this->evaluateComponentArguments($data);
        }
    }

    /**
     * Merge config data to the object
     *
     * @param array $config
     * @return void
     */
    public function merge(array $config)
    {
        $this->data = array_replace_recursive($this->data, $config);
    }

    /**
     * Get config value by key
     *
     * @param string $key
     * @param mixed $default
     * @return array|mixed|null
     */
    public function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Evaluated components data
     *
     * @param array $components
     * @return array
     */
    private function evaluateComponentArguments($components)
    {
        foreach ($components as &$component) {
            $component[Converter::DATA_ATTRIBUTES_KEY] = isset($component[Converter::DATA_ATTRIBUTES_KEY])
                ? $component[Converter::DATA_ATTRIBUTES_KEY]
                : [];
            $component[Converter::DATA_ARGUMENTS_KEY] = isset($component[Converter::DATA_ARGUMENTS_KEY])
                ? $component[Converter::DATA_ARGUMENTS_KEY]
                : [];

            foreach ($component[Converter::DATA_ARGUMENTS_KEY] as $argumentName => $argument) {
                $component[Converter::DATA_ARGUMENTS_KEY][$argumentName] =
                    $this->argumentInterpreter->evaluate($argument);
            }
        }

        return $components;
    }
}
