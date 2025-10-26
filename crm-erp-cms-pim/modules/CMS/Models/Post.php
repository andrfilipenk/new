<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;
use App\Models\User;

class Post extends Model
{
    protected string $table = 'cms_posts';
    
    protected array $fillable = [
        'title', 'slug', 'content', 'excerpt', 'status', 'comment_status',
        'author_id', 'meta_title', 'meta_description', 'meta_keywords',
        'featured_image', 'is_featured', 'is_sticky', 'view_count',
        'comment_count', 'published_at'
    ];
    
    protected array $casts = [
        'is_featured' => 'boolean',
        'is_sticky' => 'boolean',
        'view_count' => 'integer',
        'comment_count' => 'integer',
        'published_at' => 'datetime'
    ];
    
    protected bool $useSoftDeletes = true;
    
    /**
     * Get the author of the post
     */
    public function author(): ?User
    {
        if (!$this->author_id) {
            return null;
        }
        return User::find($this->author_id);
    }
    
    /**
     * Get post categories
     */
    public function categories(): array
    {
        $pivots = Model::query()
            ->table('cms_post_category')
            ->where('post_id', $this->id)
            ->get();
        
        $categories = [];
        foreach ($pivots as $pivot) {
            $category = Category::find($pivot['category_id']);
            if ($category) {
                $categories[] = $category;
            }
        }
        return $categories;
    }
    
    /**
     * Get post tags
     */
    public function tags(): array
    {
        $pivots = Model::query()
            ->table('cms_post_tag')
            ->where('post_id', $this->id)
            ->get();
        
        $tags = [];
        foreach ($pivots as $pivot) {
            $tag = Tag::find($pivot['tag_id']);
            if ($tag) {
                $tags[] = $tag;
            }
        }
        return $tags;
    }
    
    /**
     * Attach a category
     */
    public function attachCategory(int $categoryId): void
    {
        $exists = Model::query()
            ->table('cms_post_category')
            ->where('post_id', $this->id)
            ->where('category_id', $categoryId)
            ->first();
        
        if (!$exists) {
            Model::query()
                ->table('cms_post_category')
                ->insert([
                    'post_id' => $this->id,
                    'category_id' => $categoryId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }
    }
    
    /**
     * Attach a tag
     */
    public function attachTag(int $tagId): void
    {
        $exists = Model::query()
            ->table('cms_post_tag')
            ->where('post_id', $this->id)
            ->where('tag_id', $tagId)
            ->first();
        
        if (!$exists) {
            Model::query()
                ->table('cms_post_tag')
                ->insert([
                    'post_id' => $this->id,
                    'tag_id' => $tagId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }
    }
    
    /**
     * Sync categories
     */
    public function syncCategories(array $categoryIds): void
    {
        // Delete existing
        Model::query()
            ->table('cms_post_category')
            ->where('post_id', $this->id)
            ->delete();
        
        // Insert new
        foreach ($categoryIds as $categoryId) {
            $this->attachCategory($categoryId);
        }
    }
    
    /**
     * Sync tags
     */
    public function syncTags(array $tagIds): void
    {
        // Delete existing
        Model::query()
            ->table('cms_post_tag')
            ->where('post_id', $this->id)
            ->delete();
        
        // Insert new
        foreach ($tagIds as $tagId) {
            $this->attachTag($tagId);
        }
    }
    
    /**
     * Check if post is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               (!$this->published_at || strtotime($this->published_at) <= time());
    }
    
    /**
     * Check if post is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
    
    /**
     * Check if comments are allowed
     */
    public function commentsAllowed(): bool
    {
        return $this->comment_status === 'open';
    }
    
    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->view_count = ($this->view_count ?? 0) + 1;
        $this->save();
    }
    
    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        return '/posts/' . $this->slug;
    }
    
    /**
     * Get comments
     */
    public function comments(): array
    {
        return Comment::where('post_id', $this->id)
            ->where('parent_id', null)
            ->orderBy('created_at', 'DESC')
            ->get();
    }
    
    /**
     * Create a revision
     */
    public function createRevision(int $userId, string $type = 'auto'): void
    {
        Revision::create([
            'revisionable_type' => 'Post',
            'revisionable_id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'metadata' => json_encode([
                'status' => $this->status,
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description
            ]),
            'user_id' => $userId,
            'revision_type' => $type
        ]);
    }
    
    /**
     * Get revisions
     */
    public function revisions(): array
    {
        return Revision::where('revisionable_type', 'Post')
            ->where('revisionable_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }
}
