<?php

namespace App\Eav\Schema;

/**
 * Schema Difference
 * 
 * Represents a single difference between expected and actual schema.
 */
class SchemaDifference
{
    // Difference types
    public const TYPE_MISSING_TABLE = 'missing_table';
    public const TYPE_MISSING_COLUMN = 'missing_column';
    public const TYPE_MISSING_INDEX = 'missing_index';
    public const TYPE_TYPE_MISMATCH = 'type_mismatch';
    public const TYPE_ORPHANED_COLUMN = 'orphaned_column';
    public const TYPE_ORPHANED_TABLE = 'orphaned_table';
    public const TYPE_CONSTRAINT_MISMATCH = 'constraint_mismatch';
    public const TYPE_DEFAULT_MISMATCH = 'default_mismatch';
    
    // Severity levels
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_INFO = 'info';
    
    // Action types
    public const ACTION_ADD = 'add';
    public const ACTION_MODIFY = 'modify';
    public const ACTION_DROP = 'drop';
    public const ACTION_RECREATE = 'recreate';

    private string $entityTypeCode;
    private string $type;
    private string $severity;
    private string $action;
    private string $description;
    private array $metadata;
    private ?string $tableName;
    private ?string $columnName;

    public function __construct(
        string $entityTypeCode,
        string $type,
        string $severity,
        string $action,
        string $description,
        array $metadata = [],
        ?string $tableName = null,
        ?string $columnName = null
    ) {
        $this->entityTypeCode = $entityTypeCode;
        $this->type = $type;
        $this->severity = $severity;
        $this->action = $action;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->tableName = $tableName;
        $this->columnName = $columnName;
    }

    public function getEntityTypeCode(): string
    {
        return $this->entityTypeCode;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function getColumnName(): ?string
    {
        return $this->columnName;
    }

    public function getRiskScore(): int
    {
        $baseScore = match($this->severity) {
            self::SEVERITY_CRITICAL => 40,
            self::SEVERITY_HIGH => 30,
            self::SEVERITY_MEDIUM => 20,
            self::SEVERITY_LOW => 10,
            self::SEVERITY_INFO => 0,
            default => 0,
        };

        // Add risk for destructive operations
        if ($this->action === self::ACTION_DROP) {
            $baseScore += 30;
        } elseif ($this->action === self::ACTION_RECREATE) {
            $baseScore += 40;
        } elseif ($this->action === self::ACTION_MODIFY) {
            $baseScore += 15;
        }

        return min(100, $baseScore);
    }

    public function isDestructive(): bool
    {
        return in_array($this->action, [self::ACTION_DROP, self::ACTION_RECREATE]);
    }

    public function toArray(): array
    {
        return [
            'entity_type_code' => $this->entityTypeCode,
            'type' => $this->type,
            'severity' => $this->severity,
            'action' => $this->action,
            'description' => $this->description,
            'table_name' => $this->tableName,
            'column_name' => $this->columnName,
            'metadata' => $this->metadata,
            'risk_score' => $this->getRiskScore(),
            'is_destructive' => $this->isDestructive(),
        ];
    }
}
