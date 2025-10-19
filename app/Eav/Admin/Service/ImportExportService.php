<?php

namespace Eav\Admin\Service;

use Eav\Admin\Models\ImportJob;
use Eav\Admin\Models\ExportJob;
use Eav\Services\EntityService;
use Eav\Repositories\EntityTypeRepository;
use Eav\Repositories\AttributeRepository;

class ImportExportService
{
    private EntityService $entityService;
    private EntityTypeRepository $entityTypeRepo;
    private AttributeRepository $attributeRepo;
    private ValidationService $validationService;
    private int $maxFileSize;
    private array $allowedFormats;
    private int $batchSize;
    
    public function __construct(
        EntityService $entityService,
        EntityTypeRepository $entityTypeRepo,
        AttributeRepository $attributeRepo,
        ValidationService $validationService,
        array $config = []
    ) {
        $this->entityService = $entityService;
        $this->entityTypeRepo = $entityTypeRepo;
        $this->attributeRepo = $attributeRepo;
        $this->validationService = $validationService;
        $this->maxFileSize = ($config['max_file_size_mb'] ?? 50) * 1024 * 1024;
        $this->allowedFormats = $config['allowed_formats'] ?? ['csv', 'xlsx', 'json'];
        $this->batchSize = $config['batch_size'] ?? 500;
    }
    
    /**
     * Start import job
     */
    public function importFile(
        string $entityTypeCode,
        string $filePath,
        int $userId,
        array $fieldMapping = []
    ): ImportJob {
        $entityType = $this->entityTypeRepo->findByCode($entityTypeCode);
        
        if (!$entityType) {
            throw new \Exception("Entity type '{$entityTypeCode}' not found");
        }
        
        // Validate file
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }
        
        $fileSize = filesize($filePath);
        if ($fileSize > $this->maxFileSize) {
            throw new \Exception("File size exceeds maximum allowed size");
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedFormats)) {
            throw new \Exception("File format not supported: {$extension}");
        }
        
        // Create import job
        $job = new ImportJob();
        $job->entity_type_id = $entityType->entity_type_id;
        $job->user_id = $userId;
        $job->file_name = basename($filePath);
        $job->file_path = $filePath;
        $job->status = ImportJob::STATUS_PENDING;
        $job->created_at = date('Y-m-d H:i:s');
        $job->save();
        
        // Process import
        $this->processImport($job, $entityTypeCode, $fieldMapping);
        
        return $job;
    }
    
    /**
     * Process import job
     */
    private function processImport(ImportJob $job, string $entityTypeCode, array $fieldMapping): void
    {
        $job->markAsStarted();
        
        try {
            $extension = strtolower(pathinfo($job->file_path, PATHINFO_EXTENSION));
            
            // Parse file based on format
            switch ($extension) {
                case 'csv':
                    $data = $this->parseCsv($job->file_path);
                    break;
                case 'json':
                    $data = $this->parseJson($job->file_path);
                    break;
                default:
                    throw new \Exception("Unsupported format: {$extension}");
            }
            
            $job->total_rows = count($data);
            $job->save();
            
            // Process in batches
            $errors = [];
            $processed = 0;
            $successful = 0;
            $failed = 0;
            
            foreach (array_chunk($data, $this->batchSize) as $batch) {
                foreach ($batch as $rowIndex => $row) {
                    $processed++;
                    
                    // Apply field mapping
                    $mappedData = $this->applyFieldMapping($row, $fieldMapping);
                    
                    // Validate
                    $validation = $this->validationService->validateEntityData($entityTypeCode, $mappedData);
                    
                    if (!$validation['valid']) {
                        $failed++;
                        $errors[] = [
                            'row' => $rowIndex + 2, // +2 for header and 0-index
                            'errors' => $validation['errors']
                        ];
                        continue;
                    }
                    
                    // Create entity
                    try {
                        $this->entityService->create($entityTypeCode, $mappedData);
                        $successful++;
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = [
                            'row' => $rowIndex + 2,
                            'error' => $e->getMessage()
                        ];
                    }
                    
                    // Update progress every 100 rows
                    if ($processed % 100 === 0) {
                        $job->updateProgress($processed, $successful, $failed);
                    }
                }
            }
            
            // Final update
            $job->updateProgress($processed, $successful, $failed);
            
            if (!empty($errors)) {
                $job->error_details = array_slice($errors, 0, 1000); // Limit error storage
                $job->save();
            }
            
            $job->markAsCompleted();
            
        } catch (\Exception $e) {
            $job->markAsFailed([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Parse CSV file
     */
    private function parseCsv(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception("Unable to open file: {$filePath}");
        }
        
        // Read header
        $header = fgetcsv($handle);
        
        if ($header === false) {
            fclose($handle);
            throw new \Exception("Invalid CSV file: no header row");
        }
        
        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                continue; // Skip malformed rows
            }
            
            $data[] = array_combine($header, $row);
        }
        
        fclose($handle);
        
        return $data;
    }
    
    /**
     * Parse JSON file
     */
    private function parseJson(string $filePath): array
    {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new \Exception("Unable to read file: {$filePath}");
        }
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON: " . json_last_error_msg());
        }
        
        if (!is_array($data)) {
            throw new \Exception("JSON must contain an array of objects");
        }
        
        return $data;
    }
    
    /**
     * Apply field mapping
     */
    private function applyFieldMapping(array $row, array $mapping): array
    {
        if (empty($mapping)) {
            return $row; // No mapping, use as-is
        }
        
        $mapped = [];
        
        foreach ($mapping as $fileField => $attributeCode) {
            if (isset($row[$fileField])) {
                $mapped[$attributeCode] = $row[$fileField];
            }
        }
        
        return $mapped;
    }
    
    /**
     * Export entities to file
     */
    public function exportEntities(
        string $entityTypeCode,
        string $format,
        int $userId,
        array $filters = [],
        ?array $columns = null
    ): ExportJob {
        $entityType = $this->entityTypeRepo->findByCode($entityTypeCode);
        
        if (!$entityType) {
            throw new \Exception("Entity type '{$entityTypeCode}' not found");
        }
        
        if (!in_array($format, $this->allowedFormats)) {
            throw new \Exception("Export format not supported: {$format}");
        }
        
        // Create export job
        $job = new ExportJob();
        $job->entity_type_id = $entityType->entity_type_id;
        $job->user_id = $userId;
        $job->export_name = "export_{$entityTypeCode}_" . date('YmdHis');
        $job->format = $format;
        $job->filter_config = $filters;
        $job->column_config = $columns;
        $job->status = ExportJob::STATUS_PENDING;
        $job->created_at = date('Y-m-d H:i:s');
        $job->save();
        
        // Process export
        $this->processExport($job, $entityTypeCode, $format, $filters, $columns);
        
        return $job;
    }
    
    /**
     * Process export job
     */
    private function processExport(
        ExportJob $job,
        string $entityTypeCode,
        string $format,
        array $filters,
        ?array $columns
    ): void {
        $job->markAsStarted();
        
        try {
            // Build query
            $query = $this->entityService->query($entityTypeCode);
            
            // Apply filters
            foreach ($filters as $attribute => $condition) {
                if (isset($condition['operator']) && isset($condition['value'])) {
                    $query->where($attribute, $condition['operator'], $condition['value']);
                }
            }
            
            // Get entities
            $entities = $query->get();
            $totalRows = count($entities);
            
            // Prepare data
            $data = [];
            foreach ($entities as $entity) {
                $row = [];
                
                // Filter columns if specified
                foreach ($entity->attributes as $code => $value) {
                    if ($columns === null || in_array($code, $columns)) {
                        $row[$code] = $value;
                    }
                }
                
                $data[] = $row;
            }
            
            // Generate file
            $exportDir = sys_get_temp_dir() . '/eav_exports';
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }
            
            $fileName = $job->export_name . '.' . $format;
            $filePath = $exportDir . '/' . $fileName;
            
            switch ($format) {
                case 'csv':
                    $this->exportToCsv($data, $filePath);
                    break;
                case 'json':
                    $this->exportToJson($data, $filePath);
                    break;
                default:
                    throw new \Exception("Export format not implemented: {$format}");
            }
            
            $job->markAsCompleted($filePath, $totalRows);
            
        } catch (\Exception $e) {
            $job->markAsFailed();
            throw $e;
        }
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
     * Get import job status
     */
    public function getImportJobStatus(int $jobId): ?ImportJob
    {
        return ImportJob::find($jobId);
    }
    
    /**
     * Get export file
     */
    public function getExportFile(int $jobId): array
    {
        $job = ExportJob::find($jobId);
        
        if (!$job || $job->status !== ExportJob::STATUS_COMPLETED) {
            throw new \Exception("Export job not found or not completed");
        }
        
        if (!file_exists($job->file_path)) {
            throw new \Exception("Export file not found");
        }
        
        return [
            'path' => $job->file_path,
            'name' => basename($job->file_path),
            'size' => filesize($job->file_path),
            'mime' => $this->getMimeType($job->format)
        ];
    }
    
    /**
     * Get MIME type for format
     */
    private function getMimeType(string $format): string
    {
        $mimeTypes = [
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'application/xml'
        ];
        
        return $mimeTypes[$format] ?? 'application/octet-stream';
    }
}
