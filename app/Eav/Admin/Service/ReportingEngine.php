<?php

namespace Eav\Admin\Service;

use Eav\Admin\Models\Report;
use Eav\Services\EntityService;
use Eav\Repositories\EntityTypeRepository;
use Eav\Repositories\AttributeRepository;

class ReportingEngine
{
    private EntityService $entityService;
    private EntityTypeRepository $entityTypeRepo;
    private AttributeRepository $attributeRepo;
    
    public function __construct(
        EntityService $entityService,
        EntityTypeRepository $entityTypeRepo,
        AttributeRepository $attributeRepo
    ) {
        $this->entityService = $entityService;
        $this->entityTypeRepo = $entityTypeRepo;
        $this->attributeRepo = $attributeRepo;
    }
    
    /**
     * Create new report definition
     */
    public function createReport(array $data, int $userId): Report
    {
        $report = new Report();
        $report->report_name = $data['name'];
        $report->report_type = $data['type'] ?? Report::TYPE_CUSTOM;
        $report->entity_type_id = $data['entity_type_id'] ?? null;
        $report->configuration = $data['configuration'];
        $report->created_by = $userId;
        $report->is_scheduled = $data['is_scheduled'] ?? false;
        $report->schedule_config = $data['schedule_config'] ?? null;
        $report->created_at = date('Y-m-d H:i:s');
        $report->updated_at = date('Y-m-d H:i:s');
        
        $report->save();
        
        return $report;
    }
    
    /**
     * Execute report
     */
    public function executeReport(int $reportId): array
    {
        $report = Report::find($reportId);
        
        if (!$report) {
            throw new \Exception("Report not found");
        }
        
        $config = $report->configuration;
        
        // Execute based on report type
        switch ($report->report_type) {
            case Report::TYPE_SUMMARY:
                $result = $this->executeSummaryReport($config);
                break;
            case Report::TYPE_ANALYTICAL:
                $result = $this->executeAnalyticalReport($config);
                break;
            case Report::TYPE_CUSTOM:
                $result = $this->executeCustomReport($config);
                break;
            default:
                throw new \Exception("Unknown report type: {$report->report_type}");
        }
        
        // Update last run time
        $report->markAsRun();
        
        return $result;
    }
    
    /**
     * Execute summary report
     */
    private function executeSummaryReport(array $config): array
    {
        $entityTypeCode = $config['entity_type'] ?? null;
        
        if (!$entityTypeCode) {
            throw new \Exception("Entity type is required for summary report");
        }
        
        $entityType = $this->entityTypeRepo->findByCode($entityTypeCode);
        
        // Get entity count
        $totalEntities = \Core\Database\DB::table($entityType->entity_table)->count();
        
        // Get attribute count
        $attributes = $this->attributeRepo->getByEntityType($entityType->entity_type_id);
        $totalAttributes = count($attributes);
        
        // Get entities by status (if status attribute exists)
        $statusCounts = [];
        foreach ($attributes as $attr) {
            if ($attr->attribute_code === 'status') {
                $statusCounts = $this->getValueDistribution($entityTypeCode, 'status');
                break;
            }
        }
        
        // Get recent activity
        $recentEntities = \Core\Database\DB::table($entityType->entity_table)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
        
        return [
            'entity_type' => $entityTypeCode,
            'total_entities' => $totalEntities,
            'total_attributes' => $totalAttributes,
            'status_distribution' => $statusCounts,
            'recent_entities' => $recentEntities,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Execute analytical report
     */
    private function executeAnalyticalReport(array $config): array
    {
        $entityTypeCode = $config['entity_type'] ?? null;
        $groupBy = $config['group_by'] ?? [];
        $aggregations = $config['aggregations'] ?? [];
        $filters = $config['filters'] ?? [];
        
        if (!$entityTypeCode) {
            throw new \Exception("Entity type is required for analytical report");
        }
        
        // Build query
        $query = $this->entityService->query($entityTypeCode);
        
        // Apply filters
        foreach ($filters as $attribute => $condition) {
            if (isset($condition['operator']) && isset($condition['value'])) {
                $query->where($attribute, $condition['operator'], $condition['value']);
            }
        }
        
        // Get all entities
        $entities = $query->get();
        
        // Process aggregations
        $results = [];
        
        if (!empty($groupBy)) {
            // Group data
            $grouped = [];
            foreach ($entities as $entity) {
                $groupKey = $entity->attributes[$groupBy[0]] ?? 'unknown';
                
                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [];
                }
                
                $grouped[$groupKey][] = $entity;
            }
            
            // Apply aggregations to each group
            foreach ($grouped as $groupKey => $groupEntities) {
                $row = ['group' => $groupKey];
                
                foreach ($aggregations as $agg) {
                    $field = $agg['field'];
                    $function = $agg['function'];
                    $alias = $agg['alias'] ?? "{$function}_{$field}";
                    
                    $row[$alias] = $this->applyAggregation($groupEntities, $field, $function);
                }
                
                $results[] = $row;
            }
        } else {
            // Single aggregation result
            $row = [];
            
            foreach ($aggregations as $agg) {
                $field = $agg['field'];
                $function = $agg['function'];
                $alias = $agg['alias'] ?? "{$function}_{$field}";
                
                $row[$alias] = $this->applyAggregation($entities, $field, $function);
            }
            
            $results[] = $row;
        }
        
        return [
            'entity_type' => $entityTypeCode,
            'data' => $results,
            'total_records' => count($entities),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Execute custom report
     */
    private function executeCustomReport(array $config): array
    {
        $entityTypeCode = $config['entity_type'] ?? null;
        $select = $config['select'] ?? [];
        $filters = $config['filters'] ?? [];
        $sort = $config['sort'] ?? [];
        $limit = $config['limit'] ?? 1000;
        
        if (!$entityTypeCode) {
            throw new \Exception("Entity type is required for custom report");
        }
        
        // Build query
        $query = $this->entityService->query($entityTypeCode);
        
        // Apply filters
        foreach ($filters as $attribute => $condition) {
            if (isset($condition['operator']) && isset($condition['value'])) {
                $query->where($attribute, $condition['operator'], $condition['value']);
            }
        }
        
        // Apply sorting
        foreach ($sort as $sortRule) {
            $query->orderBy($sortRule['field'], $sortRule['direction'] ?? 'asc');
        }
        
        // Apply limit
        $query->limit($limit);
        
        // Get entities
        $entities = $query->get();
        
        // Format results
        $data = [];
        foreach ($entities as $entity) {
            $row = ['id' => $entity->entity_id];
            
            // Select specific columns or all
            if (empty($select)) {
                $row = array_merge($row, $entity->attributes);
            } else {
                foreach ($select as $attribute) {
                    $row[$attribute] = $entity->attributes[$attribute] ?? null;
                }
            }
            
            $data[] = $row;
        }
        
        return [
            'entity_type' => $entityTypeCode,
            'data' => $data,
            'total_records' => count($data),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Apply aggregation function
     */
    private function applyAggregation(array $entities, string $field, string $function)
    {
        $values = array_map(function($entity) use ($field) {
            return $entity->attributes[$field] ?? null;
        }, $entities);
        
        // Filter out null values
        $values = array_filter($values, function($v) { return $v !== null; });
        
        switch (strtolower($function)) {
            case 'sum':
                return array_sum($values);
            case 'avg':
            case 'average':
                return !empty($values) ? array_sum($values) / count($values) : 0;
            case 'min':
                return !empty($values) ? min($values) : null;
            case 'max':
                return !empty($values) ? max($values) : null;
            case 'count':
                return count($values);
            case 'count_distinct':
                return count(array_unique($values));
            default:
                throw new \Exception("Unknown aggregation function: {$function}");
        }
    }
    
    /**
     * Get value distribution for an attribute
     */
    private function getValueDistribution(string $entityTypeCode, string $attributeCode): array
    {
        $entities = $this->entityService->query($entityTypeCode)->get();
        
        $distribution = [];
        
        foreach ($entities as $entity) {
            $value = $entity->attributes[$attributeCode] ?? 'unknown';
            
            if (!isset($distribution[$value])) {
                $distribution[$value] = 0;
            }
            
            $distribution[$value]++;
        }
        
        arsort($distribution);
        
        return $distribution;
    }
    
    /**
     * Export report to format
     */
    public function exportReport(int $reportId, string $format = 'csv'): array
    {
        $result = $this->executeReport($reportId);
        
        $exportDir = sys_get_temp_dir() . '/eav_reports';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $fileName = "report_{$reportId}_" . date('YmdHis') . ".{$format}";
        $filePath = $exportDir . '/' . $fileName;
        
        switch ($format) {
            case 'csv':
                $this->exportToCsv($result['data'] ?? [], $filePath);
                break;
            case 'json':
                $this->exportToJson($result, $filePath);
                break;
            default:
                throw new \Exception("Export format not supported: {$format}");
        }
        
        return [
            'path' => $filePath,
            'name' => $fileName,
            'size' => filesize($filePath)
        ];
    }
    
    /**
     * Export to CSV
     */
    private function exportToCsv(array $data, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        
        if ($handle === false) {
            throw new \Exception("Unable to create file: {$filePath}");
        }
        
        // Write header
        if (!empty($data)) {
            fputcsv($handle, array_keys($data[0]));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
    }
    
    /**
     * Export to JSON
     */
    private function exportToJson(array $data, string $filePath): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        if ($json === false) {
            throw new \Exception("Unable to encode JSON");
        }
        
        if (file_put_contents($filePath, $json) === false) {
            throw new \Exception("Unable to write file: {$filePath}");
        }
    }
    
    /**
     * Get dashboard metrics
     */
    public function getDashboardMetrics(): array
    {
        // Entity counts by type
        $entityTypes = $this->entityTypeRepo->getAll();
        $entityCounts = [];
        
        foreach ($entityTypes as $type) {
            $count = \Core\Database\DB::table($type->entity_table)->count();
            $entityCounts[$type->entity_type_code] = $count;
        }
        
        // Total entities
        $totalEntities = array_sum($entityCounts);
        
        // Total attributes
        $totalAttributes = \Core\Database\DB::table('eav_attribute')->count();
        
        // Recent activity (from audit log)
        $recentActivity = \Core\Database\DB::table('eav_audit_log')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
        
        return [
            'total_entities' => $totalEntities,
            'total_entity_types' => count($entityTypes),
            'total_attributes' => $totalAttributes,
            'entity_counts_by_type' => $entityCounts,
            'recent_activity' => $recentActivity
        ];
    }
    
    /**
     * Get scheduled reports that need to run
     */
    public function getScheduledReports(): array
    {
        return Report::where('is_scheduled', true)
            ->get()
            ->filter(function($report) {
                return $report->shouldRun();
            })
            ->all();
    }
}
