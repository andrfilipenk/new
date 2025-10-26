<?php

namespace Modules\CRM\Models;

use Core\ORM\Model;
use App\Models\User;

class Opportunity extends Model
{
    protected string $table = 'crm_opportunities';
    
    protected array $fillable = [
        'account_id', 'contact_id', 'name', 'amount', 'close_date',
        'stage', 'probability', 'type', 'lead_source', 'owner_id',
        'description', 'next_steps'
    ];
    
    protected array $casts = [
        'amount' => 'float',
        'probability' => 'integer',
        'close_date' => 'date',
    ];
    
    protected bool $timestamps = true;
    protected bool $softDeletes = true;
    
    public function account(): ?Account
    {
        return Account::find($this->account_id);
    }
    
    public function contact(): ?Contact
    {
        return $this->contact_id ? Contact::find($this->contact_id) : null;
    }
    
    public function owner(): ?User
    {
        return User::find($this->owner_id);
    }
    
    public function isWon(): bool
    {
        return $this->stage === 'closed_won';
    }
    
    public function isLost(): bool
    {
        return $this->stage === 'closed_lost';
    }
    
    public function isClosed(): bool
    {
        return $this->isWon() || $this->isLost();
    }
}
