<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
$directory = __DIR__ . DIRECTORY_SEPARATOR . $argv[1];

function initialize($directory, $componentName, $componentType, $namespace)
{
    file_put_contents($directory . '/composer.json',
        generateComposerJson($componentName, $componentType, $namespace)
    );
    file_put_contents($directory . '/registration.php',
        generateRegistration($componentName)
    );
}

function generateComposerJson($componentName, $componentType, $namespace)
{
    $tpl = file_get_contents(__DIR__ . '/init/composer.json');
    return str_replace(
        ['{{componentName}}', '{{componentType}}', '{{namespace}}'],
        [$componentName, $componentType, $namespace],
        $tpl
    );
}

function generateRegistration($componentName)
{
    $tpl = file_get_contents(__DIR__ . '/init/registration.php');
    return str_replace(
        ['{{componentName}}'],
        [$componentName],
        $tpl
    );
}

function from_camel_case($input) {
    preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
    $ret = $matches[0];
    foreach ($ret as &$match) {
        $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
    }
    return implode('-', $ret);
}

foreach (new DirectoryIterator($directory) as $dir) {
    if ($dir->isDir() && !$dir->isDot()) {
//Optimizing composer.json
          $composerJson = $dir->getPathname() . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($dir->getPathname() . DIRECTORY_SEPARATOR . $dir->getFilename())) {
            $find = '"Magento\\\\Framework\\\\App\\\\": ""';
            $replace = '"Magento\\\\Framework\\\\App\\\\' . $dir->getFilename() . '\\\\": "' . $dir->getFilename() . '"';
            echo $find . "\n" . $replace . "\n";
            $content = str_replace('}}',  "}\n    }", str_replace($find, $replace, file_get_contents($composerJson)));
            file_put_contents($composerJson, $content);
        }
//        echo 'mv ' . $dir->getPathname() . DIRECTORY_SEPARATOR . '* ' . $dir->getPathname() . DIRECTORY_SEPARATOR . $dir->getFilename() . "\n";
/*        if (!file_exists($dir->getPathname() . DIRECTORY_SEPARATOR . 'composer.json') && !file_exists($dir->getPathname() . DIRECTORY_SEPARATOR . $dir->getFilename())) {
            echo $dir->getFilename() .  "\n";
            mkdir($dir->getPathname() . DIRECTORY_SEPARATOR . $dir->getFilename());
            exec('mv ' . $dir->getPathname() . DIRECTORY_SEPARATOR . '* ' . $dir->getPathname() . DIRECTORY_SEPARATOR . $dir->getFilename());
            $componentName = 'magento/library-' . strtolower(from_camel_case($dir->getFilename()));
            $componentType = 'magento2-library';
            $namespace = '\\\\Magento\\\\Framework\\\\';
            initialize($dir->getPathname(), $componentName, $componentType, $namespace);
            echo "done\n";
        }*/
  //      echo $dir->getPathname() . "\n";
    }
}