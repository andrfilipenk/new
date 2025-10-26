<?php

namespace Modules\ERP\Models\HR;

use Core\ORM\Model;

class LeaveType extends Model
{
    protected string $table = 'erp_leave_types';
    
    protected array $fillable = [
        'name', 'code', 'days_per_year', 'is_paid', 'is_active', 'description'
    ];
    
    protected array $casts = [
        'days_per_year' => 'integer',
        'is_paid' => 'boolean',
        'is_active' => 'boolean'
    ];
    
    /**
     * Get leave requests of this type
     */
    public function leaves(): array
    {
        return Leave::where('leave_type_id', $this->id)->get();
    }
}
