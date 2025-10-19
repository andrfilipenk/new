<?php
// app/Eav/Services/BatchManager.php
namespace Eav\Services;

use Core\Database\Database;
use Eav\Repositories\ValueRepository;

/**
 * Batch Manager
 * 
 * Batch insert/update/delete operations for performance optimization
 */
class BatchManager
{
    private Database $db;
    private ValueRepository $valueRepository;
    private int $chunkSize = 1000;
    private int $maxBatchSize = 5000;

    public function __construct(Database $db, ValueRepository $valueRepository, int $chunkSize = 1000)
    {
        $this->db = $db;
        $this->valueRepository = $valueRepository;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Batch create entities
     */
    public function batchCreate(int $entityTypeId, array $entitiesData): array
    {
        if (count($entitiesData) > $this->maxBatchSize) {
            throw new \InvalidArgumentException(
                "Batch size exceeds maximum allowed ({$this->maxBatchSize})"
            );
        }

        $this->db->beginTransaction();

        try {
            $createdEntities = [];

            // Process in chunks
            $chunks = array_chunk($entitiesData, $this->chunkSize);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $data) {
                    $entityData = [
                        'entity_type_id' => $entityTypeId,
                        'parent_id' => $data['parent_id'] ?? null,
                        'entity_code' => $data['entity_code'] ?? null,
                        'is_active' => $data['is_active'] ?? true,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $entityId = $this->db->table('eav_entities')->insert($entityData);
                    
                    if ($entityId) {
                        $createdEntities[] = $entityId;
                    }
                }
            }

            $this->db->commit();

            return $createdEntities;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Batch update entity values
     */
    public function batchUpdateValues(array $updates): bool
    {
        if (count($updates) > $this->maxBatchSize) {
            throw new \InvalidArgumentException(
                "Batch size exceeds maximum allowed ({$this->maxBatchSize})"
            );
        }

        $this->db->beginTransaction();

        try {
            $chunks = array_chunk($updates, $this->chunkSize);

            foreach ($chunks as $chunk) {
                $this->valueRepository->batchUpdateValues($chunk);
            }

            $this->db->commit();

            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Batch delete entities
     */
    public function batchDelete(array $entityIds, bool $soft = true): int
    {
        if (count($entityIds) > $this->maxBatchSize) {
            throw new \InvalidArgumentException(
                "Batch size exceeds maximum allowed ({$this->maxBatchSize})"
            );
        }

        $this->db->beginTransaction();

        try {
            $deletedCount = 0;

            if ($soft) {
                // Soft delete
                $affected = $this->db->table('eav_entities')
                    ->whereIn('id', $entityIds)
                    ->update([
                        'deleted_at' => date('Y-m-d H:i:s')
                    ]);
                
                $deletedCount = $affected;
            } else {
                // Hard delete - remove values first
                $chunks = array_chunk($entityIds, $this->chunkSize);

                foreach ($chunks as $chunk) {
                    // Delete from all value tables
                    $valueTables = [
                        'eav_values_varchar',
                        'eav_values_int',
                        'eav_values_decimal',
                        'eav_values_text',
                        'eav_values_datetime'
                    ];

                    foreach ($valueTables as $table) {
                        $this->db->table($table)
                            ->whereIn('entity_id', $chunk)
                            ->delete();
                    }

                    // Delete entities
                    $affected = $this->db->table('eav_entities')
                        ->whereIn('id', $chunk)
                        ->delete();

                    $deletedCount += $affected;
                }
            }

            $this->db->commit();

            return $deletedCount;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Batch insert values for multiple entities
     */
    public function batchInsertValues(string $backendType, array $values): bool
    {
        if (count($values) > $this->maxBatchSize) {
            throw new \InvalidArgumentException(
                "Batch size exceeds maximum allowed ({$this->maxBatchSize})"
            );
        }

        $tableName = $this->getTableName($backendType);

        $this->db->beginTransaction();

        try {
            $chunks = array_chunk($values, $this->chunkSize);

            foreach ($chunks as $chunk) {
                // Build batch insert
                $sql = "INSERT INTO {$tableName} (entity_id, attribute_id, value) VALUES ";
                $placeholders = [];
                $bindings = [];

                foreach ($chunk as $value) {
                    $placeholders[] = "(?, ?, ?)";
                    $bindings[] = $value['entity_id'];
                    $bindings[] = $value['attribute_id'];
                    $bindings[] = $value['value'];
                }

                $sql .= implode(', ', $placeholders);
                $sql .= " ON DUPLICATE KEY UPDATE value = VALUES(value)";

                $this->db->execute($sql, $bindings);
            }

            $this->db->commit();

            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Batch copy entities
     */
    public function batchCopy(array $sourceEntityIds, ?int $newParentId = null): array
    {
        $this->db->beginTransaction();

        try {
            $copiedEntities = [];

            foreach ($sourceEntityIds as $sourceId) {
                // Get source entity
                $source = $this->db->table('eav_entities')
                    ->where('id', $sourceId)
                    ->first();

                if (!$source) {
                    continue;
                }

                // Create copy
                $copyData = [
                    'entity_type_id' => $source['entity_type_id'],
                    'parent_id' => $newParentId ?? $source['parent_id'],
                    'entity_code' => $source['entity_code'] ? $source['entity_code'] . '_copy' : null,
                    'is_active' => $source['is_active'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $newEntityId = $this->db->table('eav_entities')->insert($copyData);

                if ($newEntityId) {
                    // Copy values from all value tables
                    $this->copyEntityValues($sourceId, $newEntityId);
                    $copiedEntities[$sourceId] = $newEntityId;
                }
            }

            $this->db->commit();

            return $copiedEntities;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Copy entity values
     */
    private function copyEntityValues(int $sourceId, int $targetId): void
    {
        $valueTables = [
            'eav_values_varchar',
            'eav_values_int',
            'eav_values_decimal',
            'eav_values_text',
            'eav_values_datetime'
        ];

        foreach ($valueTables as $table) {
            $sql = "INSERT INTO {$table} (entity_id, attribute_id, value) 
                    SELECT ?, attribute_id, value 
                    FROM {$table} 
                    WHERE entity_id = ?";

            $this->db->execute($sql, [$targetId, $sourceId]);
        }
    }

    /**
     * Get table name for backend type
     */
    private function getTableName(string $backendType): string
    {
        return "eav_values_{$backendType}";
    }

    /**
     * Set chunk size
     */
    public function setChunkSize(int $size): void
    {
        $this->chunkSize = $size;
    }

    /**
     * Set max batch size
     */
    public function setMaxBatchSize(int $size): void
    {
        $this->maxBatchSize = $size;
    }

    /**
     * Get batch statistics
     */
    public function getStats(): array
    {
        return [
            'chunk_size' => $this->chunkSize,
            'max_batch_size' => $this->maxBatchSize,
        ];
    }
}
