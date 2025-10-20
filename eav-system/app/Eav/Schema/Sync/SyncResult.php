<?php

namespace App\Eav\Schema\Sync;

/**
 * Sync Result
 * 
 * Result of schema synchronization operation.
 */
class SyncResult
{
    private string $entityTypeCode;
    private bool $success;
    private string $status;
    private array $appliedChanges;
    private array $errors;
    private ?int $backupId;
    private float $executionTime;
    private array $metadata;

    public function __construct(
        string $entityTypeCode,
        bool $success = true,
        string $status = 'completed'
    ) {
        $this->entityTypeCode = $entityTypeCode;
        $this->success = $success;
        $this->status = $status;
        $this->appliedChanges = [];
        $this->errors = [];
        $this->backupId = null;
        $this->executionTime = 0.0;
        $this->metadata = [];
    }

    public function getEntityTypeCode(): string
    {
        return $this->entityTypeCode;
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

    public function getAppliedChanges(): array
    {
        return $this->appliedChanges;
    }

    public function addAppliedChange(string $description, array $metadata = []): void
    {
        $this->appliedChanges[] = [
            'description' => $description,
            'metadata' => $metadata,
            'applied_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $error, ?\Throwable $exception = null): void
    {
        $this->errors[] = [
            'message' => $error,
            'exception' => $exception ? get_class($exception) : null,
            'details' => $exception ? $exception->getMessage() : null,
        ];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getBackupId(): ?int
    {
        return $this->backupId;
    }

    public function setBackupId(int $backupId): void
    {
        $this->backupId = $backupId;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function setExecutionTime(float $time): void
    {
        $this->executionTime = $time;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function toArray(): array
    {
        return [
            'entity_type_code' => $this->entityTypeCode,
            'success' => $this->success,
            'status' => $this->status,
            'applied_changes' => $this->appliedChanges,
            'errors' => $this->errors,
            'backup_id' => $this->backupId,
            'execution_time' => $this->executionTime,
            'metadata' => $this->metadata,
        ];
    }
}
