<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Component;

/**
 * Provides ability to statically register components.
 *
 * @author Josh Di Fabio <joshdifabio@gmail.com>
 *
 * @api
 */
class ComponentRegistrar implements ComponentRegistrarInterface
{
    /**#@+
     * Different types of components
     */
    const MODULE = 'module';
    const LIBRARY = 'library';
    const THEME = 'theme';
    const LANGUAGE = 'language';
    const SETUP = 'setup';
    /**#@- */

    /**#@- */
    private static $paths = [
        self::MODULE => [],
        self::LIBRARY => [],
        self::LANGUAGE => [],
        self::THEME => [],
        self::SETUP => []
    ];

    private static $managedTypes = [
        'magento2-module' => self::MODULE,
        'magento2-theme' => self::THEME,
        'magento2-library' => self::LIBRARY,
        'magento2-language' => self::LANGUAGE
    ];

    /**
     * Sets the location of a component.
     *
     * @param string $type component type
     * @param string $componentName Fully-qualified component name
     * @param string $path Absolute file path to the component
     * @throws \LogicException
     * @return void
     */
    public static function register($type, $componentName, $path)
    {
        self::validateType($type);
        if (isset(self::$paths[$type][$componentName])) {
            throw new \LogicException(
                ucfirst($type) . ' \'' . $componentName . '\' from \'' . $path . '\' '
                . 'has been already defined in \'' . self::$paths[$type][$componentName] . '\'.'
            );
        } else {
            self::$paths[$type][$componentName] = str_replace('\\', '/', $path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaths($type)
    {
        self::validateType($type);
        return self::$paths[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function getPath($type, $componentName)
    {
        self::validateType($type);
        return self::$paths[$type][$componentName] ?? null;
    }

    /**
     * Checks if type of component is valid
     *
     * @param string $type
     * @return void
     * @throws \LogicException
     */
    private static function validateType($type)
    {
        if (!isset(self::$paths[$type])) {
            throw new \LogicException('\'' . $type . '\' is not a valid component type');
        }
    }

    public static function setComponents($components)
    {
        self::$paths = $components;
    }

    public static function install(\Composer\Script\Event $event)
    {
        echo "Installing magento components\n";
        $composer = $event->getComposer();
        $managedPackages = [];
        foreach ($composer->getLocker()->getLockedRepository()->getPackages() as $package) {
            if (in_array($package->getType(), array_keys(self::$managedTypes))) {
                $componentType = self::$managedTypes[$package->getType()];
                $path = $composer->getInstallationManager()->getInstallPath($package);
                switch ($componentType) {
                    case self::MODULE:
                        $name = str_replace('/module-', '/', $package->getName());
                        $name = implode('_', array_map(
                            function ($item) {
                                $itemParts = explode('-', $item);
                                return implode('', array_map('ucfirst', $itemParts));
                            },
                            explode('/', $name)
                        ));
                        break;
                    case self::LANGUAGE:
                        $name = str_replace('/language-', '_', $package->getName());
                        break;
                    case self::THEME:
                        $nameParts = explode('/', $package->getName());
                        $vendor = $nameParts[0];
                        $pathParts = explode('-', $nameParts[1]);
                        $area = $pathParts[1];
                        $themeName = $pathParts[2];
                        $name = $area . '/' . ucfirst($vendor) . '/' . $themeName;
                        break;
                    default:
                        $name = $package->getName();
                }
                $managedPackages[$componentType][$name] = $path;
            }
        }
        $vendorDir = realpath($composer->getConfig()->get('vendor-dir'));
        $outputFile = $vendorDir . DIRECTORY_SEPARATOR . "magento_components.php";
        $registration = '<?php $managedComponents = ' . var_export($managedPackages, true) . ";\n";
        $registration .= '\Magento\Framework\Component\ComponentRegistrar::setComponents($managedComponents);';
        \file_put_contents($outputFile, $registration);
        echo "Magento components installed\n";
    }
}
