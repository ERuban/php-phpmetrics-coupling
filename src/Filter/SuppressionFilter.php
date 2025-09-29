<?php
declare(strict_types=1);

namespace Eruban\PhpMetricsCoupling\Filter;

final class SuppressionFilter
{
    /** @var array<string, string[]> */
    private array $exact = [];

    /** @var array<string, string[]> */
    private array $patterns = [];

    public function __construct(array $rawSuppressions)
    {
        foreach ($rawSuppressions as $metricName => $entries) {
            foreach ($entries as $entry) {
                if (\is_string($entry) === false || $entry === '') {
                    continue;
                }
                if ($this->isPattern($entry)) {
                    $this->patterns[$metricName] = $this->patterns[$metricName] ?? [];
                    $this->patterns[$metricName][] = $this->compilePattern($entry);
                } else {
                    $this->exact[$metricName] = $this->exact[$metricName] ?? [];
                    $this->exact[$metricName][] = $entry;
                }
            }

            if (isset($this->patterns[$metricName])) {
                $this->patterns[$metricName] = \array_values(\array_unique($this->patterns[$metricName]));
            }
        }
    }

    /**
     * @param array<int, \Hal\Metric\Metric> $matchedSearches
     * @return array<int, \Hal\Metric\Metric>
     */
    public function filter(string $searchName, array $matchedSearches): array
    {
        if ($matchedSearches === []) {
            return $matchedSearches;
        }

        $exact = $this->exact[$searchName] ?? [];
        $patterns = $this->patterns[$searchName] ?? [];
        if ($exact === [] && $patterns === []) {
            return $matchedSearches;
        }

        foreach ($matchedSearches as $key => $metric) {
            $entityName = $metric->getName();
            if (\in_array($entityName, $exact, true)) {
                unset($matchedSearches[$key]);
                continue;
            }
            foreach ($patterns as $regex) {
                if (\preg_match($regex, $entityName) === 1) {
                    unset($matchedSearches[$key]);

                    break;
                }
            }
        }

        return \array_values($matchedSearches);
    }

    private function isPattern(string $value): bool
    {
        return \str_contains($value, '*') && \str_contains($value, '/') === false;
    }

    private function compilePattern(string $pattern): string
    {
        $pattern = \str_replace('*', '(.*)', $pattern);

        return '~^' . $pattern . '$~u';
    }
}
