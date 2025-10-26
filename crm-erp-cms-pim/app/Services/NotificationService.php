<?php

namespace App\Services;

use Core\Database\DB;

class NotificationService
{
    /**
     * Send a notification to a user
     */
    public static function send(int $userId, string $title, string $message, string $type = 'info', ?array $data = null): int
    {
        return DB::table('notifications')->insert([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data ? json_encode($data) : null,
            'read' => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Send notification to multiple users
     */
    public static function sendToMultiple(array $userIds, string $title, string $message, string $type = 'info', ?array $data = null): void
    {
        foreach ($userIds as $userId) {
            self::send($userId, $title, $message, $type, $data);
        }
    }
    
    /**
     * Mark notification as read
     */
    public static function markAsRead(int $notificationId): void
    {
        DB::table('notifications')
            ->where('id', $notificationId)
            ->update([
                'read' => true,
                'read_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead(int $userId): void
    {
        DB::table('notifications')
            ->where('user_id', $userId)
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Get unread notifications for a user
     */
    public static function getUnread(int $userId, int $limit = 10): array
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('read', false)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get all notifications for a user
     */
    public static function getAll(int $userId, int $limit = 50): array
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get unread count
     */
    public static function getUnreadCount(int $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('read', false)
            ->count();
    }
    
    /**
     * Delete notification
     */
    public static function delete(int $notificationId): void
    {
        DB::table('notifications')
            ->where('id', $notificationId)
            ->delete();
    }
    
    /**
     * Delete old read notifications
     */
    public static function deleteOldRead(int $daysOld = 30): int
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        return DB::table('notifications')
            ->where('read', true)
            ->where('read_at', '<', $date)
            ->delete();
    }
}
