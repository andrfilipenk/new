<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;

class Tag extends Model
{
    protected string $table = 'cms_tags';
    
    protected array $fillable = [
        'name', 'slug', 'description', 'post_count'
    ];
    
    protected array $casts = [
        'post_count' => 'integer'
    ];
    
    /**
     * Get posts with this tag
     */
    public function posts(): array
    {
        $pivots = Model::query()
            ->table('cms_post_tag')
            ->where('tag_id', $this->id)
            ->get();
        
        $posts = [];
        foreach ($pivots as $pivot) {
            $post = Post::find($pivot['post_id']);
            if ($post && $post->isPublished()) {
                $posts[] = $post;
            }
        }
        return $posts;
    }
    
    /**
     * Update post count
     */
    public function updatePostCount(): void
    {
        $count = Model::query()
            ->table('cms_post_tag')
            ->where('tag_id', $this->id)
            ->count();
        
        $this->post_count = $count;
        $this->save();
    }
    
    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        return '/tags/' . $this->slug;
    }
}
