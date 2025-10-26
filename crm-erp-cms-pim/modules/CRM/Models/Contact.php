<?php

namespace Modules\CRM\Models;

use Core\ORM\Model;
use App\Models\User;

class Contact extends Model
{
    protected string $table = 'crm_contacts';
    
    protected array $fillable = [
        'account_id', 'first_name', 'last_name', 'title', 'email',
        'phone', 'mobile', 'address', 'linkedin', 'owner_id', 'notes'
    ];
    
    protected bool $timestamps = true;
    protected bool $softDeletes = true;
    
    public function account(): ?Account
    {
        if (isset($this->relations['account'])) {
            return $this->relations['account'];
        }
        
        $this->relations['account'] = $this->account_id ? Account::find($this->account_id) : null;
        return $this->relations['account'];
    }
    
    public function owner(): ?User
    {
        if (isset($this->relations['owner'])) {
            return $this->relations['owner'];
        }
        
        $this->relations['owner'] = User::find($this->owner_id);
        return $this->relations['owner'];
    }
    
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
