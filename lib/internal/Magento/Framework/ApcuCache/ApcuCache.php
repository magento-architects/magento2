<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework;

class ApcuCache
{
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var int
     */
    private $revalidateFrequency;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param Serialize\SerializerInterface $serializer
     * @param string $prefix
     */
    public function __construct(Serialize\SerializerInterface $serializer =  null, $prefix = "magento.")
    {
        $this->serializer = $serializer;
        $this->prefix = $prefix;
        $this->revalidateFrequency = ini_get('opcache.revalidate_freq');
    }

    /**
     * @param string $key
     * @param \Closure $create
     * @return array|bool|float|int|null|string
     */
    public function getCachedContent(string $key, \Closure $create)
    {
        $key = $this->prefix . $key;
        if (apcu_exists($key)) {
            if (MAGENTO_APCU_CHECK_TIMESTAMPS) {
                echo "Rechecking $key<br />";
                $includedFiles = $this->serializer ?
                    $this->serializer->unserialize(apcu_fetch($key . '.used_files'))
                    : apcu_fetch($key . '.used_files');
                $last = time() - $this->revalidateFrequency;
                $reload = false;
                foreach ($includedFiles as $file) {
                    $stat = stat($file);
                    if ($stat['mtime'] >= $last) {
                        $reload = true;
                        break;
                    }
                }
                apcu_store(MAGENTO_APCU_FILES_CHECKED_KEY, true, $this->revalidateFrequency);
                $value = $reload
                    ? $this->loadAndCache($key, $create)
                    : ($this->serializer ? $this->serializer->unserialize(apcu_fetch($key)) : apcu_fetch($key));
            } else {
                $value = $this->serializer ? $this->serializer->unserialize(apcu_fetch($key)) : apcu_fetch($key);
            }
        } else {
            $value = $this->loadAndCache($key, $create);
        }
        return $value;
    }

    /**
     * @param $key
     * @param \Closure $create
     * @return mixed
     */
    private function loadAndCache($key, \Closure $create)
    {
        echo "Recreating $key </br>";
        list($value, $loadedFiles) = $create();
        apcu_store($key, $this->serializer ? $this->serializer->serialize($value) : $value);
        apcu_store($key . '.used_files', $this->serializer ? $this->serializer->serialize($loadedFiles) : $loadedFiles);
        apcu_store(MAGENTO_APCU_FILES_CHECKED_KEY, true, $this->revalidateFrequency);
        return $value;
    }
}