<?php
// app/Intern/Service/NotificationService.php
namespace Intern\Service;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Intern\Model\Task;
use Intern\Model\TaskStatus;
use Admin\Model\User;

class NotificationService
{
    use Injectable, EventAware;

    /**
     * Send task assignment notification
     * 
     * @param int $taskId
     * @param int $assignedToId
     * @param int $assignedById
     * @return bool
     */
    public function sendTaskAssignmentNotification(int $taskId, int $assignedToId, int $assignedById): bool
    {
        try {
            $task = Task::with(['assigned', 'creator'])->find($taskId);
            $assignedBy = User::find($assignedById);
            
            if (!$task || !$assignedBy) {
                return false;
            }
            
            // Here you would implement actual notification sending:
            // - Email notifications
            // - In-app notifications
            // - Slack/Teams webhooks
            // - Push notifications
            
            $this->logNotification([
                'type' => 'task_assigned',
                'task_id' => $taskId,
                'recipient_id' => $assignedToId,
                'sender_id' => $assignedById,
                'message' => sprintf(
                    'You have been assigned to task: %s by %s',
                    $task->title,
                    $assignedBy->name ?? 'Unknown User'
                )
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            error_log('Notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send task status change notification
     * 
     * @param int $taskId
     * @param int $fromStatusId
     * @param int $toStatusId
     * @param int $changedById
     * @return bool
     */
    public function sendTaskStatusChangeNotification(int $taskId, int $fromStatusId, int $toStatusId, int $changedById): bool
    {
        try {
            $task = Task::with(['assigned', 'creator'])->find($taskId);
            $fromStatus = TaskStatus::find($fromStatusId);
            $toStatus = TaskStatus::find($toStatusId);
            $changedBy = User::find($changedById);
            
            if (!$task || !$fromStatus || !$toStatus) {
                return false;
            }
            
            // Determine who should be notified
            $recipients = [];
            
            // Notify assignee if not the one making the change
            if ($task->assigned_to && $task->assigned_to !== $changedById) {
                $recipients[] = $task->assigned_to;
            }
            
            // Notify creator if not the one making the change and not already in list
            if ($task->created_by && $task->created_by !== $changedById && !in_array($task->created_by, $recipients)) {
                $recipients[] = $task->created_by;
            }
            
            $message = sprintf(
                'Task \"%s\" moved from \"%s\" to \"%s\" by %s',
                $task->title,
                $fromStatus->title,
                $toStatus->title,
                $changedBy->name ?? 'Unknown User'
            );
            
            foreach ($recipients as $recipientId) {
                $this->logNotification([
                    'type' => 'task_status_changed',
                    'task_id' => $taskId,
                    'recipient_id' => $recipientId,
                    'sender_id' => $changedById,
                    'message' => $message,
                    'metadata' => [
                        'from_status' => $fromStatus->title,
                        'to_status' => $toStatus->title
                    ]
                ]);
            }
            
            return true;
            
        } catch (\\Exception $e) {
            error_log('Notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send task update notification
     * 
     * @param int $taskId
     * @param array $updatedFields
     * @param int $updatedById
     * @return bool
     */
    public function sendTaskUpdateNotification(int $taskId, array $updatedFields, int $updatedById): bool
    {
        try {
            $task = Task::with(['assigned', 'creator'])->find($taskId);
            $updatedBy = User::find($updatedById);
            
            if (!$task) {
                return false;
            }
            
            // Only send notifications for significant field changes
            $significantFields = ['assigned_to', 'priority_id', 'end_date', 'title'];
            $changedSignificantFields = array_intersect($updatedFields, $significantFields);
            
            if (empty($changedSignificantFields)) {
                return true; // No significant changes, skip notification
            }
            
            $recipients = [];
            
            // Notify assignee if not the one making the change
            if ($task->assigned_to && $task->assigned_to !== $updatedById) {
                $recipients[] = $task->assigned_to;
            }
            
            // Notify creator if not the one making the change and not already in list
            if ($task->created_by && $task->created_by !== $updatedById && !in_array($task->created_by, $recipients)) {
                $recipients[] = $task->created_by;
            }
            
            $message = sprintf(
                'Task \"%s\" updated (%s) by %s',
                $task->title,
                implode(', ', $changedSignificantFields),
                $updatedBy->name ?? 'Unknown User'
            );
            
            foreach ($recipients as $recipientId) {
                $this->logNotification([
                    'type' => 'task_updated',
                    'task_id' => $taskId,
                    'recipient_id' => $recipientId,
                    'sender_id' => $updatedById,
                    'message' => $message,
                    'metadata' => [
                        'updated_fields' => $changedSignificantFields
                    ]
                ]);
            }
            
            return true;
            
        } catch (\\Exception $e) {
            error_log('Notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log notification for debugging/audit purposes
     * 
     * @param array $notificationData
     * @return void
     */
    protected function logNotification(array $notificationData): void
    {
        // In a real implementation, you might:
        // 1. Store notifications in a database table
        // 2. Queue them for background processing
        // 3. Send via email/SMS/push notification services
        // 4. Integrate with Slack, Teams, etc.
        
        // For now, just log to file for debugging
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'notification' => $notificationData
        ];
        
        // This could be replaced with proper notification storage/sending
        error_log('KANBAN_NOTIFICATION: ' . json_encode($logEntry));
        
        // Fire event for other handlers
        $this->fireEvent('notification.sent', $notificationData);
    }

    /**
     * Get user preferences for notifications
     * 
     * @param int $userId
     * @return array
     */
    protected function getUserNotificationPreferences(int $userId): array
    {
        // This would typically load from user preferences table
        // For now, return default preferences
        return [
            'email_notifications' => true,
            'in_app_notifications' => true,
            'task_assignments' => true,
            'task_status_changes' => true,
            'task_updates' => false, // Only major updates
        ];
    }

    /**
     * Send email notification (placeholder)
     * 
     * @param int $userId
     * @param string $subject
     * @param string $message
     * @return bool
     */
    protected function sendEmailNotification(int $userId, string $subject, string $message): bool
    {
        // Placeholder for email sending implementation
        // Could integrate with:
        // - PHPMailer
        // - SwiftMailer
        // - SendGrid
        // - Amazon SES
        // - etc.
        
        return true;
    }

    /**
     * Send in-app notification (placeholder)
     * 
     * @param int $userId
     * @param string $message
     * @param array $metadata
     * @return bool
     */
    protected function sendInAppNotification(int $userId, string $message, array $metadata = []): bool
    {
        // Placeholder for in-app notification implementation
        // Could store in database table for user's notification center
        
        return true;
    }
}