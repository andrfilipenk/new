<?php

namespace Eav\Admin\Service;

use Eav\Admin\Models\EntityVersion;
use Eav\Services\EntityService;

class VersioningService
{
    private EntityService $entityService;
    private bool $enabled = true;
    private int $retentionDays = 365;
    
    public function __construct(EntityService $entityService, array $config = [])
    {
        $this->entityService = $entityService;
        $this->enabled = $config['enabled'] ?? true;
        $this->retentionDays = $config['retention_days'] ?? 365;
    }
    
    /**
     * Create a new version snapshot
     */
    public function createVersion(
        int $entityId,
        int $entityTypeId,
        array $attributeSnapshots,
        ?array $changedAttributes = null,
        ?int $userId = null,
        ?string $description = null
    ): EntityVersion {
        if (!$this->enabled) {
            throw new \Exception('Versioning is disabled');
        }
        
        // Get current version number
        $currentVersion = EntityVersion::where('entity_id', $entityId)
            ->where('entity_type_id', $entityTypeId)
            ->orderBy('version_number', 'DESC')
            ->first();
        
        $versionNumber = $currentVersion ? $currentVersion->version_number + 1 : 1;
        
        // Create new version
        $version = new EntityVersion();
        $version->entity_id = $entityId;
        $version->entity_type_id = $entityTypeId;
        $version->version_number = $versionNumber;
        $version->attribute_snapshots = $attributeSnapshots;
        $version->changed_attributes = $changedAttributes;
        $version->user_id = $userId;
        $version->change_description = $description;
        $version->created_at = date('Y-m-d H:i:s');
        
        $version->save();
        
        return $version;
    }
    
    /**
     * Get all versions for an entity
     */
    public function getVersions(int $entityId, int $entityTypeId, int $limit = 50): array
    {
        return EntityVersion::where('entity_id', $entityId)
            ->where('entity_type_id', $entityTypeId)
            ->orderBy('version_number', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get specific version
     */
    public function getVersion(int $entityId, int $entityTypeId, int $versionNumber): ?EntityVersion
    {
        return EntityVersion::where('entity_id', $entityId)
            ->where('entity_type_id', $entityTypeId)
            ->where('version_number', $versionNumber)
            ->first();
    }
    
    /**
     * Compare two versions
     */
    public function compareVersions(int $entityId, int $entityTypeId, int $fromVersion, int $toVersion): array
    {
        $from = $this->getVersion($entityId, $entityTypeId, $fromVersion);
        $to = $this->getVersion($entityId, $entityTypeId, $toVersion);
        
        if (!$from || !$to) {
            throw new \Exception('Version not found');
        }
        
        $changes = $to->getDiffWith($from);
        
        return [
            'entity_id' => $entityId,
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'from_date' => $from->created_at,
            'to_date' => $to->created_at,
            'from_user' => $from->user_id,
            'to_user' => $to->user_id,
            'changes' => $changes
        ];
    }
    
    /**
     * Restore entity to specific version
     */
    public function restoreVersion(
        int $entityId,
        string $entityTypeCode,
        int $versionNumber,
        int $userId
    ): object {
        $entityType = \Eav\Repositories\EntityTypeRepository::findByCode($entityTypeCode);
        
        $version = $this->getVersion($entityId, $entityType->entity_type_id, $versionNumber);
        
        if (!$version) {
            throw new \Exception("Version {$versionNumber} not found");
        }
        
        // Update entity with snapshot data
        $restoredEntity = $this->entityService->update(
            $entityId,
            $entityTypeCode,
            $version->attribute_snapshots
        );
        
        // Create a new version for the restore operation
        $this->createVersion(
            $entityId,
            $entityType->entity_type_id,
            $version->attribute_snapshots,
            array_keys($version->attribute_snapshots),
            $userId,
            "Restored from version {$versionNumber}"
        );
        
        return $restoredEntity;
    }
    
    /**
     * Get version history timeline
     */
    public function getTimeline(int $entityId, int $entityTypeId): array
    {
        $versions = EntityVersion::where('entity_id', $entityId)
            ->where('entity_type_id', $entityTypeId)
            ->orderBy('version_number', 'DESC')
            ->get();
        
        $timeline = [];
        
        foreach ($versions as $version) {
            $timeline[] = [
                'version_number' => $version->version_number,
                'created_at' => $version->created_at,
                'user_id' => $version->user_id,
                'description' => $version->change_description,
                'changed_attributes' => $version->changed_attributes ?? [],
                'attribute_count' => count($version->attribute_snapshots ?? [])
            ];
        }
        
        return $timeline;
    }
    
    /**
     * Clean old versions based on retention policy
     */
    public function cleanOldVersions(): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays} days"));
        
        return \Core\Database\DB::table('eav_entity_versions')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
    
    /**
     * Get version statistics
     */
    public function getStatistics(): array
    {
        $totalVersions = \Core\Database\DB::table('eav_entity_versions')->count();
        
        $versionsByType = \Core\Database\DB::table('eav_entity_versions')
            ->select('entity_type_id', \Core\Database\DB::raw('COUNT(*) as count'))
            ->groupBy('entity_type_id')
            ->get();
        
        $avgVersionsPerEntity = \Core\Database\DB::table('eav_entity_versions')
            ->select(\Core\Database\DB::raw('AVG(version_count) as avg'))
            ->from(\Core\Database\DB::raw('(SELECT entity_id, COUNT(*) as version_count FROM eav_entity_versions GROUP BY entity_id) as subquery'))
            ->first();
        
        return [
            'total_versions' => $totalVersions,
            'versions_by_type' => $versionsByType,
            'avg_versions_per_entity' => $avgVersionsPerEntity->avg ?? 0
        ];
    }
}
