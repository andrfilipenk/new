<?php

namespace Modules\CRM\Models;

use Core\ORM\Model;
use App\Models\User;

class Lead extends Model
{
    protected string $table = 'crm_leads';
    
    protected array $fillable = [
        'first_name', 'last_name', 'company', 'title', 'email',
        'phone', 'source', 'status', 'score', 'owner_id', 'notes',
        'converted_account_id', 'converted_contact_id', 'converted_at'
    ];
    
    protected array $casts = [
        'score' => 'integer',
        'converted_at' => 'datetime',
    ];
    
    protected bool $timestamps = true;
    protected bool $softDeletes = true;
    
    public function owner(): ?User
    {
        return User::find($this->owner_id);
    }
    
    public function convert(int $accountId, int $contactId): void
    {
        $this->update([
            'status' => 'converted',
            'converted_account_id' => $accountId,
            'converted_contact_id' => $contactId,
            'converted_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    public function isConverted(): bool
    {
        return $this->status === 'converted' && $this->converted_at !== null;
    }
}
