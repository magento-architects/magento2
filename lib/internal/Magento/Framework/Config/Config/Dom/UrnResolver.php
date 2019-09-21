<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Resolve URN path to a real schema path
 */
namespace Magento\Framework\Config\Dom;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;

/**
 * @api
 */
class UrnResolver
{
    /**
     * Get real file path by it's URN reference
     *
     * @param string $schema
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getRealPath($schema)
    {
        if (strpos($schema, 'urn:') !== 0) {
            return $schema;
        }

        $componentRegistrar = new ComponentRegistrar();
        $matches = [];
        $modulePattern = '/urn:(?<vendor>([a-zA-Z]*)):module:(?<module>([A-Za-z0-9\_]*)):(?<path>(.+))/';
        $frameworkPattern = '/urn:(?<vendor>([a-zA-Z]*)):(?<framework>(framework[A-Za-z\-]*)):(?<component>([A-Za-z\-]*))\/(?<path>(.+))/';
        $setupPattern = '/urn:(?<vendor>([a-zA-Z]*)):(?<setup>(setup[A-Za-z\-]*)):(?<path>(.+))/';
        if (preg_match($modulePattern, $schema, $matches)) {
            //urn:magento:module:Magento_Catalog:etc/catalog_attributes.xsd
            $package = $componentRegistrar
                ->getPath(ComponentRegistrar::MODULE, $matches['module']);
        } elseif (preg_match($frameworkPattern, $schema, $matches)) {
            //urn:magento:framework:Module/etc/module.xsd
            //urn:magento:framework-amqp:Module/etc/module.xsd
            $package = $componentRegistrar->getPath(
                ComponentRegistrar::LIBRARY,
                $matches['vendor'] . '/library-' . $this->fromCamelCase($matches['component'])
            );
            if (!file_exists($package . '/' . $matches['path'])) {
                $matches['path'] =  $matches['component'] . '/' . $matches['path'];
            }
        } elseif (preg_match($setupPattern, $schema, $matches)) {
            //urn:magento:setup:
            $package = $componentRegistrar
                ->getPath(ComponentRegistrar::SETUP, $matches['vendor'] . '/' . $matches['setup']);
        } else {
            throw new \InvalidArgumentException(new Phrase("Unsupported format of schema location: '%1'", [$schema]));
        }
        $schemaPath = $package . '/' . $matches['path'];
        if (empty($package) || !file_exists($schemaPath)) {
            var_dump($package, $schemaPath);
            throw new \InvalidArgumentException(
                new Phrase("Could not locate schema: '%1' at '%2'", [$schema, $schemaPath])
            );
        }
        return $schemaPath;
    }

    /**
     * Callback registered for libxml to resolve URN to the file path
     *
     * @param string $public
     * @param string $system
     * @param array $context
     * @return resource
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function registerEntityLoader($public, $system, $context)
    {
        if (strpos($system, 'urn:') === 0) {
            $filePath = $this->getRealPath($system);
        } else {
            if (file_exists($system)) {
                $filePath = $system;
            } else {
                throw new LocalizedException(new Phrase("File '%system' cannot be found", ['system' => $system]));
            }
        }
        return fopen($filePath, "r");
    }

    private function fromCamelCase($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('-', $ret);
    }

}
