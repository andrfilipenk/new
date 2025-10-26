<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;
use App\Models\User;

class Revision extends Model
{
    protected string $table = 'cms_revisions';
    
    protected array $fillable = [
        'revisionable_type', 'revisionable_id', 'title', 'content',
        'excerpt', 'metadata', 'user_id', 'revision_type'
    ];
    
    protected array $casts = [
        'revisionable_id' => 'integer'
    ];
    
    /**
     * Get the user who created this revision
     */
    public function user(): ?User
    {
        if (!$this->user_id) {
            return null;
        }
        return User::find($this->user_id);
    }
    
    /**
     * Get the revisioned item (Page or Post)
     */
    public function revisionable()
    {
        if ($this->revisionable_type === 'Page') {
            return Page::find($this->revisionable_id);
        }
        
        if ($this->revisionable_type === 'Post') {
            return Post::find($this->revisionable_id);
        }
        
        return null;
    }
    
    /**
     * Get metadata as array
     */
    public function getMetadata(): array
    {
        if (empty($this->metadata)) {
            return [];
        }
        return json_decode($this->metadata, true) ?? [];
    }
    
    /**
     * Restore this revision to the original item
     */
    public function restore(): bool
    {
        $item = $this->revisionable();
        if (!$item) {
            return false;
        }
        
        $item->title = $this->title;
        $item->content = $this->content;
        $item->excerpt = $this->excerpt;
        
        $metadata = $this->getMetadata();
        foreach ($metadata as $key => $value) {
            if (property_exists($item, $key)) {
                $item->$key = $value;
            }
        }
        
        return $item->save();
    }
}
