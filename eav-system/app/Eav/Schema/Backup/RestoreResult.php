<?php

namespace App\Eav\Schema\Backup;

/**
 * Restore Result
 */
class RestoreResult
{
    private int $backupId;
    private bool $success;
    private string $status;
    private array $restoredTables = [];
    private array $errors = [];
    private float $executionTime = 0.0;

    public function __construct(int $backupId, bool $success = true, string $status = 'completed')
    {
        $this->backupId = $backupId;
        $this->success = $success;
        $this->status = $status;
    }

    public function getBackupId(): int
    {
        return $this->backupId;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function addRestoredTable(string $tableName): void
    {
        $this->restoredTables[] = $tableName;
    }

    public function getRestoredTables(): array
    {
        return $this->restoredTables;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function setExecutionTime(float $time): void
    {
        $this->executionTime = $time;
    }

    public function toArray(): array
    {
        return [
            'backup_id' => $this->backupId,
            'success' => $this->success,
            'status' => $this->status,
            'restored_tables' => $this->restoredTables,
            'errors' => $this->errors,
            'execution_time' => $this->executionTime,
        ];
    }
}
