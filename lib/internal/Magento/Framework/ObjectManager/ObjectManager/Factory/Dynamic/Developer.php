<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ObjectManager\Factory\Dynamic;

class Developer extends \Magento\Framework\ObjectManager\Factory\AbstractFactory
{
    /**
     * Resolve constructor arguments
     *
     * @param string $requestedType
     * @param array $parameters
     * @param array $arguments
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     */
    protected function _resolveArguments($requestedType, array $parameters, array $arguments = [])
    {
        // Get default arguments from config, merge with supplied arguments
        $defaultArguments = $this->config->getArguments($requestedType);
        if (is_array($defaultArguments)) {
            if (count($arguments)) {
                $arguments = array_replace($defaultArguments, $arguments);
            } else {
                $arguments = $defaultArguments;
            }
        }

        return $this->resolveArgumentsInRuntime($requestedType, $parameters, $arguments);
    }

    /**
     * Create instance with call time arguments
     *
     * @param string $requestedType
     * @param array $arguments
     * @return object
     * @throws \Exception
     */
    public function create($requestedType, array $arguments = [])
    {
        global $objects;
        if (!isset($objects[$requestedType])) {
            $objects[$requestedType] = 0;
        }
        $objects[$requestedType]++;
        $type = $this->config->getInstanceType($requestedType);
        $parameters = $this->definitions->getParameters($type);
        if ($parameters == null) {
            $class = new \ReflectionClass($type);
            if ($class->isInterface()) {
                throw new \Exception("Can not instantiate interface $type");
            }
            return new $type();
        }
        if (isset($this->creationStack[$requestedType])) {
            $lastFound = end($this->creationStack);
            $this->creationStack = [];
            throw new \LogicException("Circular dependency: {$requestedType} depends on {$lastFound} and vice versa.");
        }
        $this->creationStack[$requestedType] = $requestedType;
        try {
            $args = $this->_resolveArguments($requestedType, $parameters, $arguments);
            unset($this->creationStack[$requestedType]);
        } catch (\Exception $e) {
            unset($this->creationStack[$requestedType]);
            throw $e;
        }

        return $this->createObject($type, $args);
    }
}
