<?php
declare(strict_types=1);

namespace Eruban\PhpMetricsCoupling\Config;

use Eruban\PhpMetricsCoupling\Search\SearchesFactory;
use Hal\Application\Config\Config;
use InvalidArgumentException;

final class ConfigFileReader
{
    public function read(Config $config, string $fileName): void
    {
        $jsonText = \file_get_contents($fileName);

        if ($jsonText === false) {
            throw new InvalidArgumentException("Cannot read configuration file '$fileName'");
        }

        /** @var array $jsonData */
        $jsonData = \json_decode(json: $jsonText, associative: true, flags: \JSON_THROW_ON_ERROR);

        $this->parseJson($config, $jsonData, $fileName);
    }

    protected function parseJson(Config $config, array $jsonData, string $fileName): void
    {
        $pathsKeyData = $jsonData['paths'] ?? null;
        if ($pathsKeyData !== null) {
            $files = [];
            foreach ($pathsKeyData as $path) {
                $path = $this->resolvePath($path, $fileName);
                $files[] = $path;
            }
            $config->set('files', $files);
        }

        $config->set('extensions', 'php');
        $config->set('composer', false);
        $config->set('exclude', []);

        $metrics = $jsonData['metrics'] ?? [];
        $config->set('searches', (new SearchesFactory())->factory($metrics));

        $config->set('suppressions', []);
        foreach ($metrics as $metricName => $metric) {
            $skipList = $metric['skip'] ?? null;
            if (\is_array($skipList) === false) {
                continue;
            }

            $config->set(
                'suppressions',
                \array_merge((array)$config->get('suppressions'), [$metricName => \array_flip($metric['skip'])])
            );
        }
    }

    private function resolvePath(string $path, string $fileName): string
    {
        if ($path[0] !== \DIRECTORY_SEPARATOR) {
            $path = \dirname($fileName) . \DIRECTORY_SEPARATOR . $path;
        }

        return $path;
    }
}
