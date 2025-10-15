<?php

namespace Project\Service;

use Project\Model\Order;
use Project\Model\OrderPhase;
use Project\Model\Material;
use Project\Model\EmployeeActivity;
use Project\Model\Comment;

/**
 * OrderService
 * Business logic for order operations
 */
class OrderService
{
    /**
     * Get complete order details with all related data
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderDetail(int $orderId): array
    {
        $order = Order::findOrFail($orderId);
        
        return [
            'order' => $order,
            'project' => $order->project,
            'positions' => $order->positions()->get(),
            'phases' => $order->phases()->orderBy('sequence_order')->get(),
            'materials' => $this->getMaterialUsage($orderId),
            'activities' => $this->getEmployeeActivities($orderId),
            'comments' => $this->getCommentThread($orderId),
            'progress' => $this->calculateOrderProgress($orderId),
        ];
    }

    /**
     * Get phase timeline visualization data
     *
     * @param int $orderId
     * @return array
     */
    public function getPhaseTimeline(int $orderId): array
    {
        $order = Order::findOrFail($orderId);
        $phases = $order->phases()->orderBy('sequence_order')->get();
        
        $orderStart = strtotime($order->start_date);
        $orderEnd = strtotime($order->end_date);
        $orderDuration = $orderEnd - $orderStart;
        
        $timeline = [];
        foreach ($phases as $phase) {
            $phaseStart = strtotime($phase->start_date);
            $phaseEnd = strtotime($phase->end_date);
            $phaseDuration = $phaseEnd - $phaseStart;
            
            // Calculate position percentage
            $startOffset = (($phaseStart - $orderStart) / $orderDuration) * 100;
            $width = ($phaseDuration / $orderDuration) * 100;
            
            $timeline[] = [
                'id' => $phase->id,
                'name' => $phase->name,
                'start_date' => $phase->start_date,
                'end_date' => $phase->end_date,
                'status' => $phase->status,
                'completion' => $phase->completion_percentage,
                'is_delayed' => $phase->isDelayed(),
                'position' => [
                    'left' => round($startOffset, 2),
                    'width' => round($width, 2),
                ],
            ];
        }
        
        return [
            'order_start' => $order->start_date,
            'order_end' => $order->end_date,
            'phases' => $timeline,
        ];
    }

    /**
     * Calculate order progress percentage
     *
     * @param int $orderId
     * @return float
     */
    public function calculateOrderProgress(int $orderId): float
    {
        $order = Order::findOrFail($orderId);
        return $order->getProgressPercentage();
    }

    /**
     * Get aggregated material usage data
     *
     * @param int $orderId
     * @return array
     */
    public function getMaterialUsage(int $orderId): array
    {
        $materials = Material::where('order_id', $orderId)->get();
        
        $materialsByPosition = [];
        $totalCost = 0;
        
        foreach ($materials as $material) {
            $positionId = $material->position_id ?? 'unassigned';
            
            if (!isset($materialsByPosition[$positionId])) {
                $materialsByPosition[$positionId] = [
                    'position' => $material->position,
                    'materials' => [],
                    'total_cost' => 0,
                ];
            }
            
            $materialsByPosition[$positionId]['materials'][] = $material;
            $materialsByPosition[$positionId]['total_cost'] += (float) $material->total_cost;
            $totalCost += (float) $material->total_cost;
        }
        
        return [
            'by_position' => $materialsByPosition,
            'total_cost' => $totalCost,
            'all_materials' => $materials,
        ];
    }

    /**
     * Get employee activities for order
     *
     * @param int $orderId
     * @return \Illuminate\Support\Collection
     */
    public function getEmployeeActivities(int $orderId)
    {
        return EmployeeActivity::where('order_id', $orderId)
            ->orderBy('activity_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get comment thread with replies
     *
     * @param int $orderId
     * @return array
     */
    protected function getCommentThread(int $orderId): array
    {
        $comments = Comment::where('commentable_type', Order::class)
            ->where('commentable_id', $orderId)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $thread = [];
        foreach ($comments as $comment) {
            $thread[] = [
                'comment' => $comment,
                'user' => $comment->user,
                'replies' => $this->getReplies($comment->id),
            ];
        }
        
        return $thread;
    }

    /**
     * Get replies for a comment
     *
     * @param int $commentId
     * @return array
     */
    protected function getReplies(int $commentId): array
    {
        $replies = Comment::where('parent_id', $commentId)
            ->orderBy('created_at', 'asc')
            ->get();
        
        $result = [];
        foreach ($replies as $reply) {
            $result[] = [
                'comment' => $reply,
                'user' => $reply->user,
            ];
        }
        
        return $result;
    }

    /**
     * Add comment to order
     *
     * @param int $orderId
     * @param int $userId
     * @param string $content
     * @param int|null $parentId
     * @return Comment
     */
    public function addComment(int $orderId, int $userId, string $content, ?int $parentId = null): Comment
    {
        $comment = new Comment([
            'commentable_type' => Order::class,
            'commentable_id' => $orderId,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'content' => $content,
        ]);
        
        $comment->save();
        
        return $comment;
    }

    /**
     * Update order phase
     *
     * @param int $phaseId
     * @param array $data
     * @return OrderPhase
     */
    public function updatePhase(int $phaseId, array $data): OrderPhase
    {
        $phase = OrderPhase::findOrFail($phaseId);
        $phase->fill($data);
        $phase->save();
        
        return $phase;
    }
}
