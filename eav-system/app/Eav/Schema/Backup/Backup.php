<?php

namespace App\Eav\Schema\Backup;

/**
 * Backup
 */
class Backup
{
    private int $id;
    private string $entityTypeCode;
    private string $type;
    private string $storagePath;
    private int $fileSize;
    private \DateTime $createdAt;
    private array $metadata;

    public function __construct(
        int $id,
        string $entityTypeCode,
        string $type,
        string $storagePath,
        int $fileSize = 0
    ) {
        $this->id = $id;
        $this->entityTypeCode = $entityTypeCode;
        $this->type = $type;
        $this->storagePath = $storagePath;
        $this->fileSize = $fileSize;
        $this->createdAt = new \DateTime();
        $this->metadata = [];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEntityTypeCode(): string
    {
        return $this->entityTypeCode;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $size): void
    {
        $this->fileSize = $size;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'entity_type_code' => $this->entityTypeCode,
            'type' => $this->type,
            'storage_path' => $this->storagePath,
            'file_size' => $this->fileSize,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
