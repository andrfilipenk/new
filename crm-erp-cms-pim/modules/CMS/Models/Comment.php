<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;
use App\Models\User;

class Comment extends Model
{
    protected string $table = 'cms_comments';
    
    protected array $fillable = [
        'post_id', 'parent_id', 'user_id', 'author_name', 'author_email',
        'author_ip', 'content', 'status', 'upvotes', 'downvotes'
    ];
    
    protected array $casts = [
        'upvotes' => 'integer',
        'downvotes' => 'integer'
    ];
    
    /**
     * Get the post this comment belongs to
     */
    public function post(): ?Post
    {
        if (!$this->post_id) {
            return null;
        }
        return Post::find($this->post_id);
    }
    
    /**
     * Get the user who made the comment
     */
    public function user(): ?User
    {
        if (!$this->user_id) {
            return null;
        }
        return User::find($this->user_id);
    }
    
    /**
     * Get parent comment
     */
    public function parent(): ?self
    {
        if (!$this->parent_id) {
            return null;
        }
        return self::find($this->parent_id);
    }
    
    /**
     * Get child comments (replies)
     */
    public function replies(): array
    {
        return self::where('parent_id', $this->id)
            ->where('status', 'approved')
            ->orderBy('created_at', 'ASC')
            ->get();
    }
    
    /**
     * Get author name
     */
    public function getAuthorName(): string
    {
        if ($this->user_id) {
            $user = $this->user();
            return $user ? $user->name : 'Anonymous';
        }
        return $this->author_name ?? 'Anonymous';
    }
    
    /**
     * Check if comment is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
    
    /**
     * Approve comment
     */
    public function approve(): void
    {
        $this->status = 'approved';
        $this->save();
    }
    
    /**
     * Mark as spam
     */
    public function markAsSpam(): void
    {
        $this->status = 'spam';
        $this->save();
    }
    
    /**
     * Move to trash
     */
    public function trash(): void
    {
        $this->status = 'trash';
        $this->save();
    }
    
    /**
     * Upvote comment
     */
    public function upvote(): void
    {
        $this->upvotes = ($this->upvotes ?? 0) + 1;
        $this->save();
    }
    
    /**
     * Downvote comment
     */
    public function downvote(): void
    {
        $this->downvotes = ($this->downvotes ?? 0) + 1;
        $this->save();
    }
    
    /**
     * Get vote score
     */
    public function getScore(): int
    {
        return ($this->upvotes ?? 0) - ($this->downvotes ?? 0);
    }
}
