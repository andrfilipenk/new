<?php

namespace Eav\Admin\Service;

use Eav\Admin\Models\AuditLog;

class AuditLoggingService
{
    private bool $enabled = true;
    private bool $logReadOperations = false;
    
    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? true;
        $this->logReadOperations = $config['log_read_operations'] ?? false;
    }
    
    /**
     * Log an event
     */
    public function log(
        string $eventType,
        ?string $entityType = null,
        ?int $entityId = null,
        ?int $userId = null,
        ?array $requestData = null,
        ?int $responseStatus = null,
        ?int $executionTime = null
    ): void {
        if (!$this->enabled) {
            return;
        }
        
        // Skip read operations if configured
        if (!$this->logReadOperations && $this->isReadOperation($eventType)) {
            return;
        }
        
        $auditLog = new AuditLog();
        $auditLog->event_type = $eventType;
        $auditLog->entity_type = $entityType;
        $auditLog->entity_id = $entityId;
        $auditLog->user_id = $userId;
        $auditLog->ip_address = $this->getClientIp();
        $auditLog->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $auditLog->request_data = $this->sanitizeRequestData($requestData);
        $auditLog->response_status = $responseStatus;
        $auditLog->execution_time = $executionTime;
        $auditLog->created_at = date('Y-m-d H:i:s');
        
        $auditLog->save();
    }
    
    /**
     * Get audit logs with filtering
     */
    public function getLogs(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $query = AuditLog::query();
        
        // Apply filters
        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }
        
        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }
        
        if (isset($filters['status'])) {
            $query->where('response_status', $filters['status']);
        }
        
        if (isset($filters['failed']) && $filters['failed']) {
            $query->where('response_status', '>=', 400);
        }
        
        // Get total count
        $total = $query->count();
        
        // Apply pagination and ordering
        $offset = ($page - 1) * $limit;
        $logs = $query->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get();
        
        return [
            'data' => $logs,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Get event statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = \Core\Database\DB::table('eav_audit_log');
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }
        
        // Total events
        $totalEvents = $query->count();
        
        // Events by type
        $eventsByType = \Core\Database\DB::table('eav_audit_log')
            ->select('event_type', \Core\Database\DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();
        
        // Failed events
        $failedEvents = \Core\Database\DB::table('eav_audit_log')
            ->where('response_status', '>=', 400)
            ->count();
        
        // Most active users
        $activeUsers = \Core\Database\DB::table('eav_audit_log')
            ->select('user_id', \Core\Database\DB::raw('COUNT(*) as count'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();
        
        return [
            'total_events' => $totalEvents,
            'failed_events' => $failedEvents,
            'success_rate' => $totalEvents > 0 ? (($totalEvents - $failedEvents) / $totalEvents) * 100 : 0,
            'events_by_type' => $eventsByType,
            'active_users' => $activeUsers
        ];
    }
    
    /**
     * Clean old logs based on retention policy
     */
    public function cleanOldLogs(int $retentionDays = 730): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        
        return \Core\Database\DB::table('eav_audit_log')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
    
    /**
     * Check if event type is a read operation
     */
    private function isReadOperation(string $eventType): bool
    {
        $readOperations = ['entity.read', 'entity.list', 'entity.search', 'entity_type.read', 'attribute.read'];
        return in_array($eventType, $readOperations);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp(): ?string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        }
        
        return $ip;
    }
    
    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeRequestData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }
        
        $sensitiveKeys = ['password', 'token', 'secret', 'api_key', 'credit_card', 'ssn'];
        
        foreach ($data as $key => $value) {
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $data[$key] = '[REDACTED]';
                }
            }
            
            if (is_array($value)) {
                $data[$key] = $this->sanitizeRequestData($value);
            }
        }
        
        return $data;
    }
}
