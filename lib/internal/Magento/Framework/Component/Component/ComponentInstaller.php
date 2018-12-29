<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Component;

class ComponentInstaller
{
    /**
     * @var array
     */
    private static $managedTypes = [
        'magento2-module' => ComponentRegistrar::MODULE,
        'magento2-theme' => ComponentRegistrar::THEME,
        'magento2-library' => ComponentRegistrar::LIBRARY,
        'magento2-language' => ComponentRegistrar::LANGUAGE
    ];

    /**
     * @param \Composer\Script\Event $event
     */
    public static function install(\Composer\Script\Event $event)
    {
        echo "Installing magento components\n";
        $composer = $event->getComposer();
        $packageMeta = [];
        foreach ($composer->getLocker()->getLockedRepository()->getPackages() as $package) {
            if (in_array($package->getType(), array_keys(self::$managedTypes))) {
                $componentType = self::$managedTypes[$package->getType()];
                $path = $composer->getInstallationManager()->getInstallPath($package);
                switch ($componentType) {
                    case ComponentRegistrar::MODULE:
                        $name = str_replace('/module-', '/', $package->getName());
                        $name = implode('_', array_map(
                            function ($item) {
                                $itemParts = explode('-', $item);
                                return implode('', array_map('ucfirst', $itemParts));
                            },
                            explode('/', $name)
                        ));
                        break;
                    case ComponentRegistrar::LANGUAGE:
                        $name = str_replace('/language-', '_', $package->getName());
                        break;
                    case ComponentRegistrar::THEME:
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
                $packageMeta[$componentType][$package->getName()] = [
                    'name' => $name,
                    'path' => $path,
                    'requires' => array_map(function($item) {return $item->getTarget();}, $package->getRequires())
                ];
            }
        }

        $result = [];
        foreach ($packageMeta as $type => $packages) {
            $sortedPackages = self::sortPackages($packages);
            foreach ($sortedPackages as $packageName) {
                $name = $packageMeta[$type][$packageName]['name'];
                $path = $packageMeta[$type][$packageName]['path'];
                $result[$type][$name] = $path;
            }
        }

        $vendorDir = realpath($composer->getConfig()->get('vendor-dir'));
        $outputFile = $vendorDir . DIRECTORY_SEPARATOR . "magento_components.php";
        $registration = '<?php $managedComponents = ' . var_export($result, true) . ";\n";
        $registration .= '\Magento\Framework\Component\ComponentRegistrar::setComponents($managedComponents);';
        \file_put_contents($outputFile, $registration);
        echo "Magento components installed\n";
    }

    /**
     * @param array $packageDependencies
     * @return array|string[]
     * @throws \MJS\TopSort\CircularDependencyException
     * @throws \MJS\TopSort\ElementNotFoundException
     */
    public static function sortPackages($packageDependencies)
    {
        $sorter = new \MJS\TopSort\Implementations\StringSort();
        foreach ($packageDependencies as $packageName => $packageMeta) {
            $dependencyNames = [];
            foreach ($packageMeta['requires'] as $dependency) {
                if (isset($packageDependencies[$dependency])) {
                    $dependencyNames[] = $dependency;
                }
            }
            $sorter->add($packageName, $dependencyNames);
        }
        return $sorter->sort();
    }
}