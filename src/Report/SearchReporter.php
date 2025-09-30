<?php
declare(strict_types=1);

namespace Eruban\PhpMetricsCoupling\Report;

use Eruban\PhpMetricsCoupling\Metric\AbstractMetric;
use Hal\Application\Config\Config;
use Hal\Component\Output\Output;
use Hal\Metric\Metrics;
use UnexpectedValueException;

final class SearchReporter
{
    public function __construct(private readonly Config $config, private readonly Output $output)
    {
    }

    public function generate(Metrics $metrics, array $unusedSkips): void
    {
        $searches = $metrics->get('searches');
        if ($searches === null) {
            return;
        }

        foreach ($searches->all() as $name => $search) {
            if ($name === 'name' || \is_array($search) === false) {
                continue;
            }

            if (\is_array($search)) {
                $this->displayCliReport($name, $search);
            }

            if ($unusedSkips[$name] !== []) {
                $this->displayUnusedSkipsNotification($unusedSkips[$name], $name);
            }
        }
    }

    private function displayCliReport(string $searchName, array $foundSearch): void
    {
        $title = \sprintf(
            '<info>Found %d occurrences for metric "%s"</info>',
            \count($foundSearch),
            $searchName
        );

        $config = $this->config->get('searches')->get($searchName)->getConfig();
        $metricName = AbstractMetric::SEARCH_NAME_TO_METRIC_NAME_MAPPING[$searchName]
            ?? throw new UnexpectedValueException(\sprintf('Metric name for search "%s" not found.', $searchName));
        if (isset($config->failIfFound) && $config->failIfFound && \count($foundSearch) > 0) {
            $title = \sprintf(
                '<error>[ERR] Found %d occurrences for metric "%s". Maximum allowed value is %d.</error>',
                \count($foundSearch),
                $searchName,
                \filter_var($config->$metricName, \FILTER_SANITIZE_NUMBER_INT)
            );
        }

        $this->output->writeln($title);
        foreach ($foundSearch as $found) {
            $this->output->writeln(\sprintf('- %s (%d)', $found->getName(), $found->get($metricName)));
        }
        $this->output->writeln(\PHP_EOL);
    }

    private function displayUnusedSkipsNotification(array $unusedSkips, string $metricName): void
    {
        $this->output->writeln(
            sprintf('<warning>Unused skip entries for metric "%s":</warning>', $metricName)
        );
        foreach ($unusedSkips as $unusedSkip) {
            $this->output->writeln("- $unusedSkip");
        }

        $this->output->writeln(\PHP_EOL);
    }
}
