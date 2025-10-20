<?php

namespace App\Eav\Schema;

/**
 * Schema Analysis Report
 * 
 * Contains the results of schema analysis including differences,
 * risk assessment, and recommended actions.
 */
class SchemaAnalysisReport
{
    private string $entityTypeCode;
    private array $differences;
    private int $riskScore;
    private string $status;
    private array $recommendations;
    private \DateTime $analyzedAt;

    public function __construct(
        string $entityTypeCode,
        array $differences = [],
        int $riskScore = 0,
        string $status = 'in_sync'
    ) {
        $this->entityTypeCode = $entityTypeCode;
        $this->differences = $differences;
        $this->riskScore = $riskScore;
        $this->status = $status;
        $this->recommendations = [];
        $this->analyzedAt = new \DateTime();
    }

    public function getEntityTypeCode(): string
    {
        return $this->entityTypeCode;
    }

    public function getDifferences(): array
    {
        return $this->differences;
    }

    public function addDifference(SchemaDifference $difference): void
    {
        $this->differences[] = $difference;
    }

    public function hasDifferences(): bool
    {
        return !empty($this->differences);
    }

    public function getRiskScore(): int
    {
        return $this->riskScore;
    }

    public function setRiskScore(int $score): void
    {
        $this->riskScore = max(0, min(100, $score));
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getRecommendations(): array
    {
        return $this->recommendations;
    }

    public function addRecommendation(string $recommendation): void
    {
        $this->recommendations[] = $recommendation;
    }

    public function getAnalyzedAt(): \DateTime
    {
        return $this->analyzedAt;
    }

    public function getRiskLevel(): string
    {
        if ($this->riskScore <= 20) return 'safe';
        if ($this->riskScore <= 40) return 'low';
        if ($this->riskScore <= 70) return 'medium';
        if ($this->riskScore <= 90) return 'high';
        return 'dangerous';
    }

    public function toArray(): array
    {
        return [
            'entity_type_code' => $this->entityTypeCode,
            'differences' => array_map(fn($d) => $d->toArray(), $this->differences),
            'risk_score' => $this->riskScore,
            'risk_level' => $this->getRiskLevel(),
            'status' => $this->status,
            'recommendations' => $this->recommendations,
            'analyzed_at' => $this->analyzedAt->format('Y-m-d H:i:s'),
        ];
    }
}
