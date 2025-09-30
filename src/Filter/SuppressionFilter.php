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
            foreach ($entries as $entry => $value) {
                if (\is_string($entry) === false || $entry === '') {
                    continue;
                }

                if ($this->isPattern($entry)) {
                    $this->patterns[$metricName] = $this->patterns[$metricName] ?? [];
                    $this->patterns[$metricName][$this->compilePattern($entry)] = false;
                }

                if ($this->isPattern($entry) === false) {
                    $this->exact[$metricName] = $this->exact[$metricName] ?? [];
                    $this->exact[$metricName][$entry] = false;
                }
            }
        }
    }

    /**
     * @param array<int, \Hal\Metric\Metric> $matchedSearches
     *
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
            if (\array_key_exists($entityName, $exact)) {
                unset($matchedSearches[$key]);
                $this->exact[$searchName][$entityName] = true; // Mark as used

                continue;
            }
            foreach ($patterns as $regex => $value) {
                if (\preg_match($regex, $entityName) === 1) {
                    unset($matchedSearches[$key]);
                    $this->patterns[$searchName][$regex] = true; // Mark as used

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
        $pattern = \str_replace(['\\','*'], ['\\\\', '.*'], $pattern);

        return '~^' . $pattern . '$~u';
    }

    public function getUnusedSkips(): array
    {
        $unusedSkips = [];
        foreach($this->exact as $metricName => $entries) {
            foreach ($entries as $entry => $value) {
                if ($value === false) {
                    $unusedSkips[$metricName][] = $entry;
                }
            }
        }
        foreach ($this->patterns as $metricName => $patterns) {
            foreach ($patterns as $regex => $value) {
                if ($value === false) {
                    $unusedSkips[$metricName][] = $regex;
                }
            }
        }

        return $unusedSkips;
    }
}
