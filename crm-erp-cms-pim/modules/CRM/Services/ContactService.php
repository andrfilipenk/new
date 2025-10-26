<?php

namespace Modules\CRM\Services;

use Modules\CRM\Models\Contact;
use Modules\CRM\Models\Activity;
use App\Services\AuditService;

/**
 * Contact Management Service
 */
class ContactService
{
    /**
     * Create contact
     */
    public function create(array $data, ?int $userId = null): Contact
    {
        $contact = Contact::create($data);
        
        AuditService::logCreate('Contact', $contact->id, $data, $userId);
        
        $this->logActivity($contact->id, 'created', 'Contact created', $userId);
        
        return $contact;
    }
    
    /**
     * Update contact
     */
    public function update(int $contactId, array $data, ?int $userId = null): Contact
    {
        $contact = Contact::findOrFail($contactId);
        $oldData = $contact->toArray();
        
        $contact->update($data);
        
        AuditService::logUpdate('Contact', $contactId, $oldData, $data, $userId);
        
        $this->logActivity($contactId, 'updated', 'Contact updated', $userId);
        
        return $contact;
    }
    
    /**
     * Merge contacts
     */
    public function merge(int $primaryContactId, int $secondaryContactId, ?int $userId = null): Contact
    {
        $primary = Contact::findOrFail($primaryContactId);
        $secondary = Contact::findOrFail($secondaryContactId);
        
        // Merge data (keep non-null values from both)
        foreach ($secondary->toArray() as $key => $value) {
            if ($value && !$primary->$key) {
                $primary->$key = $value;
            }
        }
        
        $primary->save();
        
        // Transfer related records
        \Core\Database\DB::table('crm_activities')
            ->where('related_type', '=', 'Contact')
            ->where('related_id', '=', $secondaryContactId)
            ->update(['related_id' => $primaryContactId]);
        
        // Delete secondary contact
        $secondary->delete();
        
        $this->logActivity($primaryContactId, 'merged', "Merged with contact #{$secondaryContactId}", $userId);
        
        return $primary;
    }
    
    /**
     * Assign contact to user
     */
    public function assign(int $contactId, int $ownerId, ?int $userId = null): Contact
    {
        $contact = Contact::findOrFail($contactId);
        $oldOwnerId = $contact->owner_id;
        
        $contact->owner_id = $ownerId;
        $contact->save();
        
        AuditService::logUpdate('Contact', $contactId, ['owner_id' => $oldOwnerId], ['owner_id' => $ownerId], $userId);
        
        $this->logActivity($contactId, 'assigned', "Contact assigned to user #{$ownerId}", $userId);
        
        return $contact;
    }
    
    /**
     * Link contact to account
     */
    public function linkToAccount(int $contactId, int $accountId, ?int $userId = null): Contact
    {
        $contact = Contact::findOrFail($contactId);
        $oldAccountId = $contact->account_id;
        
        $contact->account_id = $accountId;
        $contact->save();
        
        AuditService::logUpdate('Contact', $contactId, ['account_id' => $oldAccountId], ['account_id' => $accountId], $userId);
        
        $this->logActivity($contactId, 'linked', "Contact linked to account #{$accountId}", $userId);
        
        return $contact;
    }
    
    /**
     * Log activity
     */
    private function logActivity(int $contactId, string $type, string $description, ?int $userId = null): void
    {
        Activity::create([
            'type' => $type,
            'subject' => $description,
            'description' => $description,
            'related_to_type' => 'contact',
            'related_to_id' => $contactId,
            'created_by_id' => $userId,
            'status' => 'completed',
            'priority' => 'normal',
            'due_date' => date('Y-m-d H:i:s'),
        ]);
    }
}
