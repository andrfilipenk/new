<?php

namespace App\Eav\Schema;

/**
 * Difference Set
 * 
 * Collection of schema differences with classification and risk assessment.
 */
class DifferenceSet
{
    private string $entityTypeCode;
    private array $differences = [];
    private int $totalRiskScore = 0;

    public function __construct(string $entityTypeCode)
    {
        $this->entityTypeCode = $entityTypeCode;
    }

    public function getEntityTypeCode(): string
    {
        return $this->entityTypeCode;
    }

    public function addDifference(SchemaDifference $difference): void
    {
        $this->differences[] = $difference;
        $this->totalRiskScore += $difference->getRiskScore();
    }

    public function getDifferences(): array
    {
        return $this->differences;
    }

    public function hasDifferences(): bool
    {
        return !empty($this->differences);
    }

    public function getTotalRiskScore(): int
    {
        return min(100, $this->totalRiskScore);
    }

    public function getDestructiveDifferences(): array
    {
        return array_filter(
            $this->differences,
            fn($d) => $d->isDestructive()
        );
    }

    public function hasDestructiveDifferences(): bool
    {
        return !empty($this->getDestructiveDifferences());
    }

    public function getDifferencesByAction(string $action): array
    {
        return array_filter(
            $this->differences,
            fn($d) => $d->getAction() === $action
        );
    }

    public function getDifferencesBySeverity(string $severity): array
    {
        return array_filter(
            $this->differences,
            fn($d) => $d->getSeverity() === $severity
        );
    }

    public function getCriticalDifferences(): array
    {
        return $this->getDifferencesBySeverity(SchemaDifference::SEVERITY_CRITICAL);
    }

    public function count(): int
    {
        return count($this->differences);
    }

    public function toArray(): array
    {
        return [
            'entity_type_code' => $this->entityTypeCode,
            'total_differences' => $this->count(),
            'total_risk_score' => $this->getTotalRiskScore(),
            'has_destructive' => $this->hasDestructiveDifferences(),
            'differences' => array_map(fn($d) => $d->toArray(), $this->differences),
        ];
    }
}
