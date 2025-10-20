<?php

namespace App\Eav\Schema\Migration;

use App\Eav\Schema\DifferenceSet;

/**
 * Migration Validator
 * 
 * Ensures generated migrations are safe and correct before execution.
 */
class MigrationValidator
{
    /**
     * Validate migration
     */
    public function validate(Migration $migration, DifferenceSet $differences): ValidationResult
    {
        $result = new ValidationResult($migration->getName());

        // Check 1: Syntax validation
        $this->validateSyntax($migration, $result);

        // Check 2: Risk assessment
        $riskScore = $this->calculateRiskScore($differences);
        $result->setRiskScore($riskScore);
        $result->setRiskLevel($this->getRiskLevel($riskScore));

        // Check 3: Reversibility check
        $this->validateReversibility($migration, $result);

        // Check 4: Data compatibility
        $this->validateDataCompatibility($differences, $result);

        // Determine if auto-approve
        $result->setAutoApprove($riskScore <= 40 && !$result->hasErrors());

        return $result;
    }

    /**
     * Validate PHP syntax
     */
    private function validateSyntax(Migration $migration, ValidationResult $result): void
    {
        $code = $migration->getCode();
        
        // Simple syntax check using php -l would be ideal
        // For now, check for basic structure
        if (strpos($code, 'class ') === false) {
            $result->addError('Migration code does not contain a class definition');
        }

        if (strpos($code, 'public function up()') === false) {
            $result->addError('Migration code missing up() method');
        }

        if (strpos($code, 'public function down()') === false) {
            $result->addError('Migration code missing down() method');
        }
    }

    /**
     * Calculate risk score
     */
    private function calculateRiskScore(DifferenceSet $differences): int
    {
        $score = 0;

        foreach ($differences->getDifferences() as $difference) {
            $score += $difference->getRiskScore();
        }

        return min(100, $score);
    }

    /**
     * Get risk level from score
     */
    private function getRiskLevel(int $score): string
    {
        if ($score <= 20) return 'safe';
        if ($score <= 40) return 'low';
        if ($score <= 70) return 'medium';
        if ($score <= 90) return 'high';
        return 'dangerous';
    }

    /**
     * Validate reversibility
     */
    private function validateReversibility(Migration $migration, ValidationResult $result): void
    {
        $code = $migration->getCode();
        
        // Check if down() method is implemented
        if (strpos($code, '// No changes to reverse') !== false) {
            $result->addWarning('Migration may not be fully reversible');
        }

        if (strpos($code, 'TODO') !== false) {
            $result->addWarning('Migration contains TODO items - manual review required');
        }
    }

    /**
     * Validate data compatibility
     */
    private function validateDataCompatibility(DifferenceSet $differences, ValidationResult $result): void
    {
        foreach ($differences->getDifferences() as $difference) {
            if ($difference->getType() === \App\Eav\Schema\SchemaDifference::TYPE_TYPE_MISMATCH) {
                $result->addWarning('Type mismatch detected - data migration may be required');
            }

            if ($difference->isDestructive()) {
                $result->addError('Destructive operation detected - backup required before execution');
            }
        }
    }
}
