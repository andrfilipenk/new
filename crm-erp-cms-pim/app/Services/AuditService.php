<?php

namespace App\Services;

use Core\ORM\Model;
use Core\Database\DB;

class AuditService
{
    /**
     * Log an audit event
     */
    public static function log(string $action, string $model, int $modelId, ?int $userId = null, ?array $oldData = null, ?array $newData = null): void
    {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        
        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_data' => $oldData ? json_encode($oldData) : null,
            'new_data' => $newData ? json_encode($newData) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Log creation event
     */
    public static function logCreate(string $model, int $modelId, array $data, ?int $userId = null): void
    {
        self::log('create', $model, $modelId, $userId, null, $data);
    }
    
    /**
     * Log update event
     */
    public static function logUpdate(string $model, int $modelId, array $oldData, array $newData, ?int $userId = null): void
    {
        self::log('update', $model, $modelId, $userId, $oldData, $newData);
    }
    
    /**
     * Log deletion event
     */
    public static function logDelete(string $model, int $modelId, array $data, ?int $userId = null): void
    {
        self::log('delete', $model, $modelId, $userId, $data, null);
    }
    
    /**
     * Get audit logs for a model
     */
    public static function getLogsForModel(string $model, int $modelId, int $limit = 50): array
    {
        return DB::table('audit_logs')
            ->where('model', $model)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get audit logs for a user
     */
    public static function getLogsForUser(int $userId, int $limit = 50): array
    {
        return DB::table('audit_logs')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get recent audit logs
     */
    public static function getRecentLogs(int $limit = 100): array
    {
        return DB::table('audit_logs')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
}
