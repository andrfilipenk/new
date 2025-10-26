<?php

namespace Modules\CRM\Models;

use Core\ORM\Model;
use App\Models\User;

class Account extends Model
{
    protected string $table = 'crm_accounts';
    
    protected array $fillable = [
        'name', 'type', 'industry', 'website', 'phone', 'email',
        'billing_address', 'shipping_address', 'annual_revenue',
        'employees', 'owner_id', 'description'
    ];
    
    protected array $casts = [
        'annual_revenue' => 'float',
        'employees' => 'integer',
    ];
    
    protected bool $timestamps = true;
    protected bool $softDeletes = true;
    
    public function contacts(): array
    {
        if (isset($this->relations['contacts'])) {
            return $this->relations['contacts'];
        }
        
        $rows = \Core\Database\DB::table('crm_contacts')
            ->where('account_id', '=', $this->id)
            ->get();
        
        $this->relations['contacts'] = array_map(fn($row) => Contact::hydrate($row), $rows);
        return $this->relations['contacts'];
    }
    
    public function opportunities(): array
    {
        if (isset($this->relations['opportunities'])) {
            return $this->relations['opportunities'];
        }
        
        $rows = \Core\Database\DB::table('crm_opportunities')
            ->where('account_id', '=', $this->id)
            ->get();
        
        $this->relations['opportunities'] = array_map(fn($row) => Opportunity::hydrate($row), $rows);
        return $this->relations['opportunities'];
    }
    
    public function owner(): ?User
    {
        if (isset($this->relations['owner'])) {
            return $this->relations['owner'];
        }
        
        $this->relations['owner'] = User::find($this->owner_id);
        return $this->relations['owner'];
    }
}
