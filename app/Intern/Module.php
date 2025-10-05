<?php
// app/Intern/Module.php
namespace Intern;

use Core\Mvc\AbstractModule;
use Core\Events\Manager as EventsManager;
use Intern\Service\KanbanService;
use Intern\Service\NotificationService;
use Intern\Model\TaskLog;

class Module extends AbstractModule 
{
    public function afterBootstrap($di)
    {
        parent::afterBootstrap($di);
        
        // Register Kanban Service
        $di->set(KanbanService::class, function() use ($di) {
            $service = new KanbanService();
            $service->setDI($di);
            if ($di->has('eventsManager')) {
                $service->setEventsManager($di->get('eventsManager'));
            }
            return $service;
        });
        
        // Register Notification Service
        $di->set(NotificationService::class, function() use ($di) {
            $service = new NotificationService();
            $service->setDI($di);
            if ($di->has('eventsManager')) {
                $service->setEventsManager($di->get('eventsManager'));
            }
            return $service;
        });
        
        // Register event listeners
        if ($di->has('eventsManager')) {
            $eventsManager = $di->get('eventsManager');
            $this->registerKanbanEventHandlers($eventsManager);
        }
    }

    /**
     * Register Kanban event handlers
     * 
     * @param EventsManager $eventsManager
     * @return void
     */
    protected function registerKanbanEventHandlers(EventsManager $eventsManager)
    {
        // Task movement event handler
        $eventsManager->attach('kanban.taskMoved', function($event) {
            $data = $event->getData();
            
            // Enhanced logging for task movement
            TaskLog::logKanbanMovement(
                $data['task_id'],
                $data['old_status_id'],
                $data['new_status_id'],
                $data['old_position'],
                $data['new_position'],
                $this->getCurrentUserId()
            );
            
            // Send notifications for status changes
            if ($data['old_status_id'] !== $data['new_status_id']) {
                $notificationService = $this->getDI()->get(NotificationService::class);
                $notificationService->sendTaskStatusChangeNotification(
                    $data['task_id'],
                    $data['old_status_id'],
                    $data['new_status_id'],
                    $this->getCurrentUserId() ?? 0
                );
            }
            
            // Here you could add additional handlers:
            // - Update project analytics
            // - Log to external systems
            // - Trigger webhooks
        });

        // Task creation event handler
        $eventsManager->attach('kanban.taskCreated', function($event) {
            $data = $event->getData();
            
            TaskLog::logTaskCreation(
                $data['task_id'],
                $data['created_by'],
                $data['assigned_to']
            );
            
            // Send assignment notification
            if ($data['assigned_to'] && $data['assigned_to'] !== $data['created_by']) {
                $notificationService = $this->getDI()->get(NotificationService::class);
                $notificationService->sendTaskAssignmentNotification(
                    $data['task_id'],
                    $data['assigned_to'],
                    $data['created_by']
                );
            }
            
            // Additional creation handlers:
            // - Update team workload metrics
            // - Create calendar entries
        });

        // Task update event handler
        $eventsManager->attach('kanban.taskUpdated', function($event) {
            $data = $event->getData();
            
            TaskLog::logTaskUpdate(
                $data['task_id'],
                $data['updated_fields'],
                $data['updated_by']
            );
            
            // Send update notifications
            if ($data['updated_by']) {
                $notificationService = $this->getDI()->get(NotificationService::class);
                $notificationService->sendTaskUpdateNotification(
                    $data['task_id'],
                    $data['updated_fields'],
                    $data['updated_by']
                );
            }
            
            // Additional update handlers:
            // - Update search indexes
            // - Sync with external tools
        });

        // Board refresh event handler for analytics
        $eventsManager->attach('kanban.boardRefreshed', function($event) {
            $data = $event->getData();
            
            // Log board access for analytics
            // Track user engagement
            // Update usage statistics
        });
    }

    /**
     * Get current user ID from session
     * 
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        $di = $this->getDI();
        if ($di->has('session')) {
            $session = $di->get('session');
            return $session->get('user_id');
        }
        return null;
    }
}