<?php

namespace Modules\CRM\Services;

use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Activity;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * Lead Management Service
 * 
 * Handles business logic for lead operations
 */
class LeadService
{
    /**
     * Create a new lead
     */
    public function create(array $data, ?int $userId = null): Lead
    {
        $lead = Lead::create($data);
        
        // Log audit trail
        AuditService::logCreate('Lead', $lead->id, $data, $userId);
        
        // Create activity
        $this->logActivity($lead->id, 'created', 'Lead created', $userId);
        
        // Notify assigned user if any
        if (isset($data['assigned_to']) && $data['assigned_to']) {
            NotificationService::send(
                $data['assigned_to'],
                'New Lead Assigned',
                "A new lead '{$lead->company}' has been assigned to you.",
                'info'
            );
        }
        
        return $lead;
    }
    
    /**
     * Update lead
     */
    public function update(int $leadId, array $data, ?int $userId = null): Lead
    {
        $lead = Lead::findOrFail($leadId);
        $oldData = $lead->toArray();
        
        $lead->update($data);
        
        // Log audit trail
        AuditService::logUpdate('Lead', $leadId, $oldData, $data, $userId);
        
        // Log activity
        $this->logActivity($leadId, 'updated', 'Lead updated', $userId);
        
        // Notify if assigned user changed
        if (isset($data['assigned_to']) && $data['assigned_to'] != $oldData['assigned_to']) {
            NotificationService::send(
                $data['assigned_to'],
                'Lead Reassigned',
                "Lead '{$lead->company}' has been assigned to you.",
                'info'
            );
        }
        
        return $lead;
    }
    
    /**
     * Convert lead to opportunity
     */
    public function convertToOpportunity(int $leadId, array $opportunityData, ?int $userId = null): array
    {
        $lead = Lead::findOrFail($leadId);
        
        // Create contact from lead
        $contact = \Modules\CRM\Models\Contact::create([
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'mobile' => $lead->mobile,
            'position' => $lead->position,
            'assigned_to' => $lead->assigned_to,
        ]);
        
        // Create account if company provided
        $account = null;
        if ($lead->company) {
            $account = \Modules\CRM\Models\Account::create([
                'name' => $lead->company,
                'industry' => $lead->industry,
                'website' => $lead->website,
                'assigned_to' => $lead->assigned_to,
            ]);
            
            // Link contact to account
            $contact->account_id = $account->id;
            $contact->save();
        }
        
        // Create opportunity
        $opportunity = \Modules\CRM\Models\Opportunity::create(array_merge($opportunityData, [
            'contact_id' => $contact->id,
            'account_id' => $account ? $account->id : null,
            'source' => $lead->source,
            'assigned_to' => $lead->assigned_to,
        ]));
        
        // Update lead status
        $lead->status = 'converted';
        $lead->save();
        
        // Log activity
        $this->logActivity($leadId, 'converted', 'Lead converted to opportunity', $userId);
        
        // Notify assigned user
        if ($lead->assigned_to) {
            NotificationService::send(
                $lead->assigned_to,
                'Lead Converted',
                "Lead '{$lead->company}' has been successfully converted.",
                'success'
            );
        }
        
        return [
            'lead' => $lead,
            'contact' => $contact,
            'account' => $account,
            'opportunity' => $opportunity,
        ];
    }
    
    /**
     * Qualify lead
     */
    public function qualify(int $leadId, string $qualification, ?int $userId = null): Lead
    {
        $lead = Lead::findOrFail($leadId);
        
        $lead->status = $qualification;
        $lead->qualified_at = date('Y-m-d H:i:s');
        $lead->save();
        
        $this->logActivity($leadId, 'qualified', "Lead qualified as {$qualification}", $userId);
        
        return $lead;
    }
    
    /**
     * Disqualify lead
     */
    public function disqualify(int $leadId, string $reason, ?int $userId = null): Lead
    {
        $lead = Lead::findOrFail($leadId);
        
        $lead->status = 'disqualified';
        $lead->disqualification_reason = $reason;
        $lead->save();
        
        $this->logActivity($leadId, 'disqualified', "Lead disqualified: {$reason}", $userId);
        
        return $lead;
    }
    
    /**
     * Assign lead to user
     */
    public function assign(int $leadId, int $assignedTo, ?int $userId = null): Lead
    {
        $lead = Lead::findOrFail($leadId);
        $oldAssignee = $lead->assigned_to;
        
        $lead->assigned_to = $assignedTo;
        $lead->save();
        
        $this->logActivity($leadId, 'assigned', "Lead assigned to user #{$assignedTo}", $userId);
        
        // Notify new assignee
        NotificationService::send(
            $assignedTo,
            'Lead Assigned',
            "Lead '{$lead->company}' has been assigned to you.",
            'info'
        );
        
        return $lead;
    }
    
    /**
     * Log activity for lead
     */
    private function logActivity(int $leadId, string $type, string $description, ?int $userId = null): void
    {
        Activity::create([
            'type' => $type,
            'subject' => $description,
            'description' => $description,
            'related_type' => 'Lead',
            'related_id' => $leadId,
            'user_id' => $userId,
            'activity_date' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Get lead statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Lead::query()->count(),
            'new' => Lead::query()->where('status', '=', 'new')->count(),
            'contacted' => Lead::query()->where('status', '=', 'contacted')->count(),
            'qualified' => Lead::query()->where('status', '=', 'qualified')->count(),
            'converted' => Lead::query()->where('status', '=', 'converted')->count(),
            'disqualified' => Lead::query()->where('status', '=', 'disqualified')->count(),
            'by_source' => $this->getLeadsBySource(),
        ];
    }
    
    /**
     * Get leads by source
     */
    private function getLeadsBySource(): array
    {
        $leads = Lead::all();
        $bySource = [];
        
        foreach ($leads as $lead) {
            $source = $lead->source ?? 'Unknown';
            if (!isset($bySource[$source])) {
                $bySource[$source] = 0;
            }
            $bySource[$source]++;
        }
        
        return $bySource;
    }
}
