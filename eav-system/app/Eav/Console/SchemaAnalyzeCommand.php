<?php

namespace App\Eav\Console;

use App\Eav\Schema\Analysis\SchemaAnalyzer;

/**
 * Schema Analyze CLI Command
 * 
 * Usage: php cli.php eav:schema:analyze [entity-type] [--all] [--verbose] [--json]
 */
class SchemaAnalyzeCommand
{
    private SchemaAnalyzer $analyzer;

    public function __construct(SchemaAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Execute command
     */
    public function execute(array $args = []): int
    {
        $entityType = $args['entity-type'] ?? null;
        $all = isset($args['--all']);
        $verbose = isset($args['--verbose']);
        $json = isset($args['--json']);

        try {
            if ($all || !$entityType) {
                $reports = $this->analyzer->analyzeAll();
                $this->displayMultipleReports($reports, $verbose, $json);
            } else {
                $report = $this->analyzer->analyze($entityType);
                $this->displayReport($report, $verbose, $json);
            }

            return 0; // Success
        } catch (\Exception $e) {
            echo "Error: {$e->getMessage()}\n";
            return 1; // Failure
        }
    }

    /**
     * Display single report
     */
    private function displayReport($report, bool $verbose, bool $json): void
    {
        if ($json) {
            echo json_encode($report->toArray(), JSON_PRETTY_PRINT) . "\n";
            return;
        }

        echo "\n=== Schema Analysis Report ===\n\n";
        echo "Entity Type: {$report->getEntityTypeCode()}\n";
        echo "Status: {$report->getStatus()}\n";
        echo "Risk Score: {$report->getRiskScore()} ({$report->getRiskLevel()})\n";
        echo "Differences: " . count($report->getDifferences()) . "\n";
        echo "Analyzed At: {$report->getAnalyzedAt()->format('Y-m-d H:i:s')}\n\n";

        if ($report->hasDifferences()) {
            echo "Differences Found:\n";
            echo str_repeat('-', 80) . "\n";

            foreach ($report->getDifferences() as $diff) {
                $severity = strtoupper($diff->getSeverity());
                $action = strtoupper($diff->getAction());
                echo "[$severity][$action] {$diff->getDescription()}\n";

                if ($verbose) {
                    $metadata = $diff->getMetadata();
                    if (!empty($metadata)) {
                        echo "  Metadata: " . json_encode($metadata) . "\n";
                    }
                }
            }
            echo "\n";
        }

        if (!empty($report->getRecommendations())) {
            echo "Recommendations:\n";
            echo str_repeat('-', 80) . "\n";
            foreach ($report->getRecommendations() as $rec) {
                echo "• $rec\n";
            }
        }

        echo "\n";
    }

    /**
     * Display multiple reports
     */
    private function displayMultipleReports(array $reports, bool $verbose, bool $json): void
    {
        if ($json) {
            $output = array_map(fn($r) => $r->toArray(), $reports);
            echo json_encode($output, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        echo "\n=== Schema Analysis (All Entity Types) ===\n\n";

        foreach ($reports as $entityType => $report) {
            $status = $report->getStatus();
            $risk = $report->getRiskLevel();
            $diffCount = count($report->getDifferences());

            echo sprintf(
                "%-20s | %-15s | %-10s | %d differences\n",
                $entityType,
                $status,
                $risk,
                $diffCount
            );

            if ($verbose && $report->hasDifferences()) {
                foreach ($report->getDifferences() as $diff) {
                    echo "  - [{$diff->getSeverity()}] {$diff->getDescription()}\n";
                }
            }
        }

        echo "\n";
    }

    /**
     * Get command help
     */
    public static function getHelp(): string
    {
        return <<<HELP
Usage: php cli.php eav:schema:analyze [entity-type] [options]

Analyze schema differences between configuration and database.

Arguments:
  entity-type         Entity type code to analyze (optional if --all used)

Options:
  --all              Analyze all entity types
  --verbose          Show detailed differences
  --json             Output as JSON

Examples:
  php cli.php eav:schema:analyze customer
  php cli.php eav:schema:analyze --all
  php cli.php eav:schema:analyze customer --verbose
  php cli.php eav:schema:analyze --all --json

HELP;
    }
}
