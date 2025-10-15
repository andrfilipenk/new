<?php

namespace Project\Model;

use Core\Database\Model;

/**
 * Comment Model
 * Represents collaboration notes and discussions
 * Uses polymorphic relationship to attach to any entity
 */
class Comment extends Model
{
    protected static $table = 'comments';
    protected static $primaryKey = 'id';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'content',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the parent commentable model (Order, Position, Project, etc.)
     *
     * @return \Core\Database\Relationship\MorphTo
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who wrote this comment
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\User\Model\User::class, 'user_id');
    }

    /**
     * Get the parent comment if this is a reply
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get all replies to this comment
     *
     * @return \Core\Database\Relationship\HasMany
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Check if this is a reply to another comment
     *
     * @return bool
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get formatted content with mentions highlighted
     *
     * @return string
     */
    public function getFormattedContent(): string
    {
        // Simple mention highlighting (@username)
        $content = htmlspecialchars($this->content);
        $content = preg_replace('/@(\w+)/', '<span class="mention">@$1</span>', $content);
        
        return $content;
    }
}
