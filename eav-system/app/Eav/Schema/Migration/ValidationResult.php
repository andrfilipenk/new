<?php

namespace App\Eav\Schema\Migration;

/**
 * Validation Result
 */
class ValidationResult
{
    private string $migrationName;
    private array $errors = [];
    private array $warnings = [];
    private int $riskScore = 0;
    private string $riskLevel = 'safe';
    private bool $autoApprove = true;

    public function __construct(string $migrationName)
    {
        $this->migrationName = $migrationName;
    }

    public function getMigrationName(): string
    {
        return $this->migrationName;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function getRiskScore(): int
    {
        return $this->riskScore;
    }

    public function setRiskScore(int $score): void
    {
        $this->riskScore = $score;
    }

    public function getRiskLevel(): string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(string $level): void
    {
        $this->riskLevel = $level;
    }

    public function isAutoApprove(): bool
    {
        return $this->autoApprove;
    }

    public function setAutoApprove(bool $autoApprove): void
    {
        $this->autoApprove = $autoApprove;
    }

    public function isValid(): bool
    {
        return !$this->hasErrors();
    }

    public function toArray(): array
    {
        return [
            'migration_name' => $this->migrationName,
            'is_valid' => $this->isValid(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'risk_score' => $this->riskScore,
            'risk_level' => $this->riskLevel,
            'auto_approve' => $this->autoApprove,
        ];
    }
}
