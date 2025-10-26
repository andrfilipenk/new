<?php

namespace Modules\CRM\Services;

use Modules\CRM\Models\Opportunity;
use Modules\CRM\Models\Activity;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * Opportunity Management Service
 */
class OpportunityService
{
    /**
     * Create opportunity
     */
    public function create(array $data, ?int $userId = null): Opportunity
    {
        $opportunity = Opportunity::create($data);
        
        AuditService::logCreate('Opportunity', $opportunity->id, $data, $userId);
        
        $this->logActivity($opportunity->id, 'created', 'Opportunity created', $userId);
        
        if (isset($data['assigned_to']) && $data['assigned_to']) {
            NotificationService::send(
                $data['assigned_to'],
                'New Opportunity Assigned',
                "A new opportunity '{$opportunity->name}' has been assigned to you.",
                'info'
            );
        }
        
        return $opportunity;
    }
    
    /**
     * Update opportunity
     */
    public function update(int $opportunityId, array $data, ?int $userId = null): Opportunity
    {
        $opportunity = Opportunity::findOrFail($opportunityId);
        $oldData = $opportunity->toArray();
        
        $opportunity->update($data);
        
        AuditService::logUpdate('Opportunity', $opportunityId, $oldData, $data, $userId);
        
        $this->logActivity($opportunityId, 'updated', 'Opportunity updated', $userId);
        
        // Notify on stage change
        if (isset($data['stage']) && $data['stage'] != $oldData['stage']) {
            $this->notifyStageChange($opportunity, $oldData['stage'], $data['stage']);
        }
        
        return $opportunity;
    }
    
    /**
     * Move to next stage
     */
    public function moveToNextStage(int $opportunityId, ?int $userId = null): Opportunity
    {
        $opportunity = Opportunity::findOrFail($opportunityId);
        
        $stages = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won'];
        $currentIndex = array_search($opportunity->stage, $stages);
        
        if ($currentIndex !== false && $currentIndex < count($stages) - 1) {
            $newStage = $stages[$currentIndex + 1];
            $opportunity->stage = $newStage;
            $opportunity->save();
            
            $this->logActivity($opportunityId, 'stage_changed', "Stage changed to {$newStage}", $userId);
            $this->notifyStageChange($opportunity, $stages[$currentIndex], $newStage);
        }
        
        return $opportunity;
    }
    
    /**
     * Mark as won
     */
    public function markAsWon(int $opportunityId, ?int $userId = null): Opportunity
    {
        $opportunity = Opportunity::findOrFail($opportunityId);
        
        $opportunity->stage = 'closed_won';
        $opportunity->close_date = date('Y-m-d');
        $opportunity->probability = 100;
        $opportunity->save();
        
        $this->logActivity($opportunityId, 'won', 'Opportunity won', $userId);
        
        if ($opportunity->owner_id) {
            NotificationService::send(
                $opportunity->owner_id,
                'Opportunity Won!',
                "Congratulations! Opportunity '{$opportunity->name}' has been won!",
                'success'
            );
        }
        
        return $opportunity;
    }
    
    /**
     * Mark as lost
     */
    public function markAsLost(int $opportunityId, ?string $reason = null, ?int $userId = null): Opportunity
    {
        $opportunity = Opportunity::findOrFail($opportunityId);
        
        $opportunity->stage = 'closed_lost';
        $opportunity->close_date = date('Y-m-d');
        $opportunity->probability = 0;
        if ($reason) {
            $opportunity->loss_reason = $reason;
        }
        $opportunity->save();
        
        $this->logActivity($opportunityId, 'lost', "Opportunity lost" . ($reason ? ": {$reason}" : ''), $userId);
        
        return $opportunity;
    }
    
    /**
     * Update stage
     */
    public function updateStage(int $opportunityId, string $stage, ?int $probability = null, ?int $userId = null): Opportunity
    {
        $opportunity = Opportunity::findOrFail($opportunityId);
        $oldStage = $opportunity->stage;
        
        $opportunity->stage = $stage;
        
        if ($probability !== null) {
            $opportunity->probability = $probability;
        } else {
            // Auto-update probability based on stage
            $probabilities = [
                'prospecting' => 10,
                'qualification' => 25,
                'needs_analysis' => 40,
                'proposal' => 50,
                'negotiation' => 75,
                'closed_won' => 100,
                'closed_lost' => 0,
            ];
            $opportunity->probability = $probabilities[$stage] ?? 50;
        }
        
        $opportunity->save();
        
        AuditService::logUpdate('Opportunity', $opportunityId, ['stage' => $oldStage], ['stage' => $stage], $userId);
        $this->logActivity($opportunityId, 'stage_changed', "Stage changed from {$oldStage} to {$stage}", $userId);
        $this->notifyStageChange($opportunity, $oldStage, $stage);
        
        return $opportunity;
    }
    
    /**
     * Calculate win probability based on stage
     */
    public function updateProbability(int $opportunityId): Opportunity
    {
        $opportunity = Opportunity::findOrFail($opportunityId);
        
        $probabilities = [
            'prospecting' => 10,
            'qualification' => 25,
            'proposal' => 50,
            'negotiation' => 75,
            'closed_won' => 100,
            'closed_lost' => 0,
        ];
        
        $opportunity->probability = $probabilities[$opportunity->stage] ?? 50;
        $opportunity->save();
        
        return $opportunity;
    }
    
    /**
     * Get pipeline statistics
     */
    public function getStatistics(): array
    {
        return $this->getPipelineStatistics();
    }
    
    /**
     * Get pipeline statistics
     */
    public function getPipelineStatistics(): array
    {
        $opportunities = Opportunity::all();
        
        $stats = [
            'total_count' => 0,
            'total_value' => 0,
            'weighted_value' => 0,
            'by_stage' => [],
            'win_rate' => 0,
        ];
        
        $stageValues = [];
        $totalWon = 0;
        $totalClosed = 0;
        
        foreach ($opportunities as $opp) {
            $stats['total_count']++;
            $stats['total_value'] += $opp->amount;
            $stats['weighted_value'] += ($opp->amount * $opp->probability / 100);
            
            $stage = $opp->stage;
            if (!isset($stageValues[$stage])) {
                $stageValues[$stage] = ['count' => 0, 'value' => 0];
            }
            $stageValues[$stage]['count']++;
            $stageValues[$stage]['value'] += $opp->amount;
            
            if ($stage === 'closed_won') {
                $totalWon++;
                $totalClosed++;
            } elseif ($stage === 'closed_lost') {
                $totalClosed++;
            }
        }
        
        $stats['by_stage'] = $stageValues;
        $stats['win_rate'] = $totalClosed > 0 ? ($totalWon / $totalClosed) * 100 : 0;
        
        return $stats;
    }
    
    /**
     * Notify on stage change
     */
    private function notifyStageChange(Opportunity $opportunity, string $oldStage, string $newStage): void
    {
        if ($opportunity->owner_id) {
            NotificationService::send(
                $opportunity->owner_id,
                'Opportunity Stage Changed',
                "Opportunity '{$opportunity->name}' moved from {$oldStage} to {$newStage}.",
                'info'
            );
        }
    }
    
    /**
     * Log activity
     */
    private function logActivity(int $opportunityId, string $type, string $description, ?int $userId = null): void
    {
        Activity::create([
            'type' => $type,
            'subject' => $description,
            'description' => $description,
            'related_to_type' => 'opportunity',
            'related_to_id' => $opportunityId,
            'created_by_id' => $userId,
            'status' => 'completed',
            'priority' => 'normal',
            'due_date' => date('Y-m-d H:i:s'),
        ]);
    }
}
