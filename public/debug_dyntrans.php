<?php
// Direct test of DynamicTranslationLoader
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

return function (array $context) {
    $kernel = new \AppKernel($context['APP_ENV'], (bool)$context['APP_DEBUG']);
    $kernel->boot();
    $container = $kernel->getContainer();

    $output = [];

    // 1. Check the raw DB loader
    $rawLoader = new \ReflectionClass(\Oro\Bundle\TranslationBundle\Translation\CacheableDynamicTranslationLoader::class);
    $innerProp = $rawLoader->getProperty('loader');
    $innerProp->setAccessible(true);

    $cacheableLoader = $container->get('oro_translation.dynamic_translation_provider');

    // Get the internal loader via reflection
    $providerRef = new \ReflectionClass($cacheableLoader);
    $loaderProp = $providerRef->getProperty('loader');
    $loaderProp->setAccessible(true);
    $loader = $loaderProp->getValue($cacheableLoader);

    $output[] = 'Loader class: ' . get_class($loader);

    // Get the inner (raw) loader
    $innerLoader = $innerProp->getValue($loader);
    $output[] = 'Inner loader class: ' . get_class($innerLoader);

    // Call the raw loader directly
    $rawData = $innerLoader->loadTranslations(['fr_FR'], false);
    $spot7Count = 0;
    $totalMessages = 0;
    if (isset($rawData['fr_FR']['messages'])) {
        $totalMessages = count($rawData['fr_FR']['messages']);
        foreach ($rawData['fr_FR']['messages'] as $key => $val) {
            if (str_starts_with($key, 'spot7.')) {
                $spot7Count++;
                if ($spot7Count <= 5) {
                    $output[] = "  spot7 key: $key = $val";
                }
            }
        }
    }
    $output[] = "Raw DB loader fr_FR: totalMessages=$totalMessages, spot7=$spot7Count";

    // 2. Check what the DynamicTranslationProvider has cached
    $translationsProp = $providerRef->getProperty('translations');
    $translationsProp->setAccessible(true);
    $translations = $translationsProp->getValue($cacheableLoader);
    $output[] = 'Cached locales in provider: ' . implode(', ', array_keys($translations));
    foreach ($translations as $loc => $domains) {
        $locSpot7 = 0;
        $locTotal = isset($domains['messages']) ? count($domains['messages']) : 0;
        if (isset($domains['messages'])) {
            foreach ($domains['messages'] as $k => $v) {
                if (str_starts_with($k, 'spot7.')) $locSpot7++;
            }
        }
        $output[] = "  $loc: messages=$locTotal, spot7=$locSpot7";
    }

    // 3. Check the PSR cache
    $cacheProp = $providerRef->getProperty('cache');
    $cacheProp->setAccessible(true);
    $dynCache = $cacheProp->getValue($cacheableLoader);
    $dynCacheRef = new \ReflectionClass($dynCache);
    $cachePoolProp = $dynCacheRef->getProperty('cache');
    $cachePoolProp->setAccessible(true);
    $cachePool = $cachePoolProp->getValue($dynCache);
    $output[] = 'PSR cache pool class: ' . get_class($cachePool);

    $item = $cachePool->getItem('dynamic_translations_fr_FR');
    $output[] = 'Cache item fr_FR isHit: ' . ($item->isHit() ? 'YES' : 'NO');
    if ($item->isHit()) {
        $cachedData = $item->get();
        $cachedSpot7 = 0;
        $cachedTotal = isset($cachedData['messages']) ? count($cachedData['messages']) : 0;
        if (isset($cachedData['messages'])) {
            foreach ($cachedData['messages'] as $k => $v) {
                if (str_starts_with($k, 'spot7.')) $cachedSpot7++;
            }
        }
        $output[] = "  cached fr_FR: messages=$cachedTotal, spot7=$cachedSpot7";
    }

    return new Response(implode("\n", $output) . "\n", 200, ['Content-Type' => 'text/plain']);
};
