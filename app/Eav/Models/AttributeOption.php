<?php
// app/Eav/Models/AttributeOption.php
namespace Eav\Models;

use Core\Database\Model;

/**
 * Attribute Option Model
 * 
 * Represents an option for select/multiselect attributes
 */
class AttributeOption extends Model
{
    protected $table = 'eav_attribute_options';
    public $timestamps = false;

    protected array $fillable = [
        'attribute_id',
        'option_value',
        'option_label',
        'sort_order'
    ];

    protected array $casts = [
        'sort_order' => 'integer'
    ];

    /**
     * Get the attribute this option belongs to
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
