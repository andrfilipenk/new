<?php

namespace Modules\CRM\Services;

use Modules\CRM\Models\Account;
use Modules\CRM\Models\Activity;
use App\Services\AuditService;

/**
 * Account Management Service
 */
class AccountService
{
    /**
     * Create account
     */
    public function create(array $data, ?int $userId = null): Account
    {
        $account = Account::create($data);
        
        AuditService::logCreate('Account', $account->id, $data, $userId);
        
        $this->logActivity($account->id, 'created', 'Account created', $userId);
        
        return $account;
    }
    
    /**
     * Update account
     */
    public function update(int $accountId, array $data, ?int $userId = null): Account
    {
        $account = Account::findOrFail($accountId);
        $oldData = $account->toArray();
        
        $account->update($data);
        
        AuditService::logUpdate('Account', $accountId, $oldData, $data, $userId);
        
        $this->logActivity($accountId, 'updated', 'Account updated', $userId);
        
        return $account;
    }
    
    /**
     * Get account revenue summary
     */
    public function getRevenueSummary(int $accountId): array
    {
        $opportunities = \Modules\CRM\Models\Opportunity::query()
            ->where('account_id', '=', $accountId)
            ->get();
        
        $summary = [
            'total_opportunities' => count($opportunities),
            'won_count' => 0,
            'lost_count' => 0,
            'open_count' => 0,
            'total_won_value' => 0,
            'total_pipeline_value' => 0,
        ];
        
        foreach ($opportunities as $opp) {
            if ($opp->stage === 'closed_won') {
                $summary['won_count']++;
                $summary['total_won_value'] += $opp->amount;
            } elseif ($opp->stage === 'closed_lost') {
                $summary['lost_count']++;
            } else {
                $summary['open_count']++;
                $summary['total_pipeline_value'] += $opp->amount;
            }
        }
        
        return $summary;
    }
    
    /**
     * Assign account to user
     */
    public function assign(int $accountId, int $ownerId, ?int $userId = null): Account
    {
        $account = Account::findOrFail($accountId);
        $oldOwnerId = $account->owner_id;
        
        $account->owner_id = $ownerId;
        $account->save();
        
        AuditService::logUpdate('Account', $accountId, ['owner_id' => $oldOwnerId], ['owner_id' => $ownerId], $userId);
        
        $this->logActivity($accountId, 'assigned', "Account assigned to user #{$ownerId}", $userId);
        
        return $account;
    }
    
    /**
     * Get account statistics
     */
    public function getStatistics(): array
    {
        $accounts = Account::all();
        
        $stats = [
            'total' => count($accounts),
            'by_type' => [],
            'by_industry' => [],
            'total_revenue' => 0,
        ];
        
        foreach ($accounts as $account) {
            // Count by type
            $type = $account->type ?? 'other';
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;
            
            // Count by industry
            $industry = $account->industry ?? 'Unknown';
            if (!isset($stats['by_industry'][$industry])) {
                $stats['by_industry'][$industry] = 0;
            }
            $stats['by_industry'][$industry]++;
            
            // Sum revenue
            if ($account->annual_revenue) {
                $stats['total_revenue'] += $account->annual_revenue;
            }
        }
        
        return $stats;
    }
    
    /**
     * Log activity
     */
    private function logActivity(int $accountId, string $type, string $description, ?int $userId = null): void
    {
        Activity::create([
            'type' => $type,
            'subject' => $description,
            'description' => $description,
            'related_to_type' => 'account',
            'related_to_id' => $accountId,
            'created_by_id' => $userId,
            'status' => 'completed',
            'priority' => 'normal',
            'due_date' => date('Y-m-d H:i:s'),
        ]);
    }
}
